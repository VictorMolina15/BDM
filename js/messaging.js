document.addEventListener('DOMContentLoaded', function() {
    const homePageMessagesContainer = document.querySelector('div.messages'); // For home-page.php right sidebar
    const messageSearchInput = document.getElementById('message-search'); // Search bar in home-page.php sidebar

    const activeChatWindows = {}; // Stores data about open chat windows { chatId: {element, ...} }
    const MAX_CHAT_WINDOWS = 3; // Max number of concurrent chat popups
    const chatWindowPositions = []; // To manage positioning

    // ---- Helper Functions ----
    function getProfilePicUrl(filename) {
        const defaultPic = '../assets/profile_pics/default-profile.png';
        return filename && filename !== 'null' ? `../assets/profile_pics/${filename}` : defaultPic;
    }

    function createChatPopupElement(chatId, otherUser) {
        const popupId = `chat-popup-${chatId}`;
        if (document.getElementById(popupId)) return document.getElementById(popupId); // Already exists

        const profilePic = getProfilePicUrl(otherUser.profile_picture || otherUser.avatar); // Handle both possible key names

        const html = `
            <div class="chat-popup" id="${popupId}" data-chat-id="${chatId}">
                <div class="chat-header">
                    <img src="${profilePic}" alt="${otherUser.username}" class="chat-avatar profile-photo"/>
                    <span class="chat-user-name">${otherUser.username}</span>
                    <button class="chat-close-btn" data-chat-id="${chatId}">&times;</button>
                </div>
                <div class="chat-messages-area"></div>
                <div class="chat-input-area">
                    <textarea class="chat-message-input" placeholder="Escribe un mensaje..."></textarea>
                    <button class="chat-send-btn" data-chat-id="${chatId}">Enviar</button>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', html);
        const element = document.getElementById(popupId);

        // Add event listeners
        element.querySelector('.chat-close-btn').addEventListener('click', () => closeChatPopup(chatId));
        element.querySelector('.chat-send-btn').addEventListener('click', () => sendMessage(chatId));
        element.querySelector('.chat-message-input').addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage(chatId);
            }
        });
        element.querySelector('.chat-messages-area').addEventListener('scroll', function() {
            if (this.scrollTop === 0 && activeChatWindows[chatId] && !activeChatWindows[chatId].isLoadingMessages && activeChatWindows[chatId].hasMoreMessages) {
                loadMessages(chatId, true); // Load older messages
            }
        });

        return element;
    }

    function manageChatWindowDisplay(chatId, otherUser) {
        if (activeChatWindows[chatId]) { // If window exists, just ensure it's visible
            activeChatWindows[chatId].element.style.display = 'flex';
            return;
        }

        if (Object.keys(activeChatWindows).length >= MAX_CHAT_WINDOWS) {
            const oldestChatId = chatWindowPositions.shift(); // Get ID of the window to close
            if (activeChatWindows[oldestChatId]) {
                closeChatPopup(oldestChatId, false); // Close it without animation or complex logic
            }
        }

        const element = createChatPopupElement(chatId, otherUser);
        const newIndex = Object.keys(activeChatWindows).length; // Should be accurate before adding new one

        activeChatWindows[chatId] = {
            element: element,
            otherUser: otherUser,
            messagesArea: element.querySelector('.chat-messages-area'),
            inputField: element.querySelector('.chat-message-input'),
            isLoadingMessages: false,
            hasMoreMessages: true,
            currentPage: 0,
            messagesPerPage: 20,
            positionIndex: newIndex // Store its position index
        };
        chatWindowPositions.push(chatId);
        repositionChatWindows();
        loadMessages(chatId);
    }

    function repositionChatWindows() {
        const baseOffsetRight = 20; // px from right edge of screen
        const windowWidth = 320; // px width of a chat window
        const spacing = 10; // px between windows

        chatWindowPositions.forEach((id, index) => {
            if (activeChatWindows[id] && activeChatWindows[id].element) {
                activeChatWindows[id].element.style.right = `${baseOffsetRight + (index * (windowWidth + spacing))}px`;
                activeChatWindows[id].element.style.display = 'flex';
            }
        });
    }


    function closeChatPopup(chatId, reposition = true) {
        if (activeChatWindows[chatId]) {
            activeChatWindows[chatId].element.remove();
            delete activeChatWindows[chatId];

            const indexInPositions = chatWindowPositions.indexOf(chatId);
            if (indexInPositions > -1) {
                chatWindowPositions.splice(indexInPositions, 1);
            }
            if(reposition) repositionChatWindows();
        }
    }

     function appendMessageToPopup(chatId, msgData, isPrepending = false) {
        const chat = activeChatWindows[chatId];
        if (!chat) return;

        const receivedProfilePic = getProfilePicUrl(msgData.author_avatar); // Para mensajes recibidos
        const sentProfilePic = getProfilePicUrl(loggedInUser.avatar); // Para mensajes enviados
        const messageClass = msgData.author_id === currentLoggedInUserId ? 'msg-sent' : 'msg-received';
        const time = new Date(msgData.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        // Construir las partes de la imagen condicionalmente y luego insertarlas en el string principal
        // let receivedAvatarHtml = '';
        // if (messageClass === 'msg-received') {
        //     receivedAvatarHtml = `<img src="${receivedProfilePic}" alt="${msgData.author_username}" class="chat-bubble-avatar profile-photo"/>`;
        // }

        // let sentAvatarHtml = '';
        // if (messageClass === 'msg-sent') {
        //     sentAvatarHtml = `<img src="${sentProfilePic}" alt="${loggedInUser.username}" class="chat-bubble-avatar profile-photo"/>`;
        //}

        const msgHtml = `
            <div class="chat-bubble ${messageClass}">
                <!-- receivedAvatarHtml -->
                <div class="bubble-content">
                    ${messageClass === 'msg-received' ? `<strong>${msgData.author_username}</strong>` : ''}
                    <p>${msgData.content.replace(/\n/g, '<br>')}</p>
                    <small class="timestamp">${time}</small>
                </div>
                <!-- sentAvatarHtml -->
            </div>`;

        if (isPrepending) {
            chat.messagesArea.insertAdjacentHTML('afterbegin', msgHtml);
        } else {
            chat.messagesArea.insertAdjacentHTML('beforeend', msgHtml);
            chat.messagesArea.scrollTop = chat.messagesArea.scrollHeight;
        }
        // Remove "no messages" placeholder if it exists
        const placeholder = chat.messagesArea.querySelector('.no-messages-placeholder');
        if (placeholder) placeholder.remove();
    }


    // ---- AJAX Call Functions ----
    async function fetchApi(action, params = {}) {
        const formData = new FormData();
        formData.append('action', action);
        for (const key in params) {
            formData.append(key, params[key]);
        }
        try {
            const response = await fetch('messaging_ajax.php', { method: 'POST', body: formData }); // Adjust path if needed
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return await response.json();
        } catch (error) {
            console.error(`API call failed for action ${action}:`, error);
            // Optionally display a generic error to the user
            return { status: 'error', message: `Network or server error: ${error.message}` };
        }
    }

    async function loadUserChatsList() {
        if (!homePageMessagesContainer) return; // Only on homepage

        const dynamicListContainer = homePageMessagesContainer.querySelector('#message-list-dynamic') ||
            (() => {
                const el = document.createElement('div');
                el.id = 'message-list-dynamic';
                // Insert after the 'category' div, or at the top if 'category' isn't found
                const categoryDiv = homePageMessagesContainer.querySelector('.category');
                if(categoryDiv) categoryDiv.after(el);
                else homePageMessagesContainer.prepend(el);
                return el;
            })();
        dynamicListContainer.innerHTML = '<p class="text-muted" style="padding: 1rem;">Cargando chats...</p>';

        const data = await fetchApi('get_chats');
        if (data.status === 'success' && data.chats) {
            dynamicListContainer.innerHTML = ''; // Clear loading
            if (data.chats.length === 0) {
                dynamicListContainer.innerHTML = '<p class="text-muted" style="padding: 1rem;">No tienes conversaciones activas.</p>';
                return;
            }
            data.chats.forEach(chat => {
                const otherUser = {
                    id_name: chat.other_user_id,
                    username: chat.other_username,
                    profile_picture: chat.other_user_avatar
                };
                const profilePic = getProfilePicUrl(otherUser.profile_picture);
                const lastMsgText = chat.last_message_content ? (chat.last_message_content.length > 25 ? chat.last_message_content.substring(0, 22) + '...' : chat.last_message_content) : 'Sin mensajes.';

                const chatItemHtml = `
                    <div class="message" data-chat-id="${chat.chat_id}" data-other-user-id="${otherUser.id_name}" data-other-user-name="${otherUser.username}" data-other-user-avatar="${otherUser.profile_picture || ''}">
                        <div class="profile-photo"><img src="${profilePic}" alt="${otherUser.username}"></div>
                        <div class="message-body">
                            <h5>${otherUser.username}</h5>
                            <p class="text-muted">${lastMsgText}</p>
                        </div>
                    </div>`;
                dynamicListContainer.insertAdjacentHTML('beforeend', chatItemHtml);
            });
            // Add click listeners to newly added chat items
            dynamicListContainer.querySelectorAll('.message').forEach(item => {
                item.addEventListener('click', function() {
                    const chatId = this.dataset.chatId;
                    const otherUser = {
                        id_name: this.dataset.otherUserId,
                        username: this.dataset.otherUserName,
                        profile_picture: this.dataset.otherUserAvatar
                    };
                    manageChatWindowDisplay(chatId, otherUser);
                });
            });
        } else {
            dynamicListContainer.innerHTML = `<p class="text-muted" style="padding: 1rem;">${data.message || 'Error al cargar chats.'}</p>`;
        }
    }

    async function loadMessages(chatId, loadOlder = false) {
        const chat = activeChatWindows[chatId];
        if (!chat || chat.isLoadingMessages) return;

        chat.isLoadingMessages = true;
        if (!loadOlder) {
            chat.messagesArea.innerHTML = '<p class="text-muted no-messages-placeholder" style="text-align:center; padding:1rem;">Cargando mensajes...</p>';
            chat.currentPage = 0; // Reset for fresh load
            chat.hasMoreMessages = true; // Assume there are messages initially
        }

        const params = {
            chat_id: chatId,
            limit: chat.messagesPerPage,
            offset: chat.currentPage * chat.messagesPerPage
        };
        const data = await fetchApi('get_messages', params);

        if (data.status === 'success' && data.messages) {
            if (!loadOlder) chat.messagesArea.innerHTML = ''; // Clear loading placeholder only if it's a fresh load

            const oldScrollHeight = chat.messagesArea.scrollHeight;

            data.messages.forEach(msg => appendMessageToPopup(chatId, msg, loadOlder));

            if (data.messages.length < chat.messagesPerPage) {
                chat.hasMoreMessages = false;
                 if(data.messages.length === 0 && !loadOlder) { // No messages at all
                    chat.messagesArea.innerHTML = '<p class="text-muted no-messages-placeholder" style="text-align:center; padding:1rem;">No hay mensajes en este chat.</p>';
                } else if (loadOlder && data.messages.length > 0) { // Reached the end when loading older
                    chat.messagesArea.insertAdjacentHTML('afterbegin', '<p class="text-muted" style="text-align:center; font-size:0.8em; padding:0.5em;">Inicio de la conversación.</p>');
                }
            }
            if (loadOlder) {
                // Maintain scroll position when prepending older messages
                chat.messagesArea.scrollTop = chat.messagesArea.scrollHeight - oldScrollHeight;
            } else {
                chat.messagesArea.scrollTop = chat.messagesArea.scrollHeight; // Scroll to bottom for new load/new message
            }
            chat.currentPage++;
        } else if (!loadOlder) { // Error on initial load
            chat.messagesArea.innerHTML = `<p class="text-muted no-messages-placeholder" style="text-align:center; padding:1rem;">${data.message || 'Error al cargar mensajes.'}</p>`;
        }
        chat.isLoadingMessages = false;
    }

    async function sendMessage(chatId) {
        const chat = activeChatWindows[chatId];
        if (!chat) return;
        const content = chat.inputField.value.trim();
        if (!content) return;

        chat.inputField.disabled = true; // Prevent multi-send
        const params = { chat_id: chatId, content: content };
        const data = await fetchApi('send_message', params);

        if (data.status === 'success' && data.message_data) {
            appendMessageToPopup(chatId, data.message_data);
            chat.inputField.value = '';
             // If user sends a message in an empty chat, refresh the main chat list to show this chat with last message
            if (homePageMessagesContainer) loadUserChatsList();
        } else {
            alert(`Error al enviar mensaje: ${data.message || 'Error desconocido.'}`);
        }
        chat.inputField.disabled = false;
        chat.inputField.focus();
    }

    async function initiateOrOpenChat(otherUserId, otherUserName, otherUserAvatar) {
        if (otherUserId === currentLoggedInUserId) {
            alert("No puedes chatear contigo mismo.");
            return;
        }
        // Check if a window with this user (potentially via a different chat_id if one isn't known yet) is already open
        for (const id in activeChatWindows) {
            if (activeChatWindows[id].otherUser.id_name === otherUserId) {
                manageChatWindowDisplay(id, activeChatWindows[id].otherUser); // Just bring to front/ensure visible
                return;
            }
        }

        const params = { other_user_id: otherUserId };
        const data = await fetchApi('create_or_get_chat', params);

        if (data.status === 'success' && data.chat_id) {
            // Use other_user from the response as it's fresh from DB
            const otherUser = data.other_user || { id_name: otherUserId, username: otherUserName, profile_picture: otherUserAvatar};
            manageChatWindowDisplay(data.chat_id, otherUser);
        } else {
            alert(`Error al iniciar chat: ${data.message || 'Error desconocido.'}`);
        }
    }

    let searchDebounceTimer;
    async function searchUsersAndDisplay(searchTerm) {
        const dynamicListContainer = homePageMessagesContainer.querySelector('#message-list-dynamic');
        if (!dynamicListContainer) return;

        if (!searchTerm.trim()) {
            loadUserChatsList(); // Back to default chat list
            return;
        }
        dynamicListContainer.innerHTML = '<p class="text-muted" style="padding: 1rem;">Buscando usuarios...</p>';

        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(async () => {
            const data = await fetchApi('search_users_for_chat', { search_term: searchTerm });
            if (data.status === 'success' && data.users) {
                dynamicListContainer.innerHTML = ''; // Clear loading
                if (data.users.length === 0) {
                    dynamicListContainer.innerHTML = '<p class="text-muted" style="padding: 1rem;">No se encontraron usuarios.</p>';
                    return;
                }
                data.users.forEach(user => {
                    const profilePic = getProfilePicUrl(user.profile_picture);
                    const userItemHtml = `
                        <div class="message search-result-item" data-user-id="${user.id_name}" data-user-name="${user.username}" data-user-avatar="${user.profile_picture || ''}">
                            <div class="profile-photo"><img src="${profilePic}" alt="${user.username}"></div>
                            <div class="message-body">
                                <h5>${user.username}</h5>
                                <p class="text-muted">@${user.id_name}</p>
                            </div>
                        </div>`;
                    dynamicListContainer.insertAdjacentHTML('beforeend', userItemHtml);
                });
                // Add click listeners to search results
                dynamicListContainer.querySelectorAll('.search-result-item').forEach(item => {
                    item.addEventListener('click', function() {
                        initiateOrOpenChat(this.dataset.userId, this.dataset.userName, this.dataset.userAvatar);
                        if (messageSearchInput) messageSearchInput.value = ''; // Clear search
                        loadUserChatsList(); // Go back to chat list view
                    });
                });
            } else {
                dynamicListContainer.innerHTML = `<p class="text-muted" style="padding: 1rem;">${data.message || 'Error al buscar.'}</p>`;
            }
        }, 300); // Debounce requests
    }


    // ---- Event Listeners Setup ----
    if (homePageMessagesContainer) {
        loadUserChatsList(); // Initial load for homepage
        if (messageSearchInput) {
            messageSearchInput.addEventListener('input', (e) => searchUsersAndDisplay(e.target.value));
        }
    }

    // For "Mensaje" button on profile pages (using event delegation)
    document.body.addEventListener('click', function(event) {
        // Check if the clicked element or its parent is the "Mensaje" button
        let targetElement = event.target;
        while (targetElement != null && targetElement.id !== 'initiate-chat-btn-profile') { // Give profile page message button this ID
            targetElement = targetElement.parentElement;
        }

        if (targetElement && targetElement.id === 'initiate-chat-btn-profile') {
            const otherUserId = targetElement.dataset.otherUserId;
            const otherUserName = targetElement.dataset.otherUserName;
            const otherUserAvatar = targetElement.dataset.otherUserAvatar;
            if (otherUserId) {
                initiateOrOpenChat(otherUserId, otherUserName, otherUserAvatar);
            }
        }
    });
});