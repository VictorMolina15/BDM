document.addEventListener("DOMContentLoaded", () => {
    const contactList = document.getElementById("contact-list");
    const chatName = document.getElementById("chat-name");
    const chatImg = document.getElementById("chat-img");
    const chatContent = document.getElementById("chat-content");
    const messageInput = document.getElementById("message-input");
    const sendBtn = document.getElementById("send-btn");
    const searchBar = document.getElementById("search-bar");
    let currentContact = null;

    // Lista de contactos predefinidos
    const contacts = [
        { name: "Miguel Nuñez", id: "Vendedor1", img: "../recursos/gato1.jpg" },
        { name: "Juan Pérez", id: "Vendedor2", img: "../recursos/huron.jpg" }
    ];

    function openChat(contactId) {
        currentContact = contactId;
        chatContent.innerHTML = "<p>Cargando mensajes...</p>";

        setTimeout(() => {
            chatContent.innerHTML = "";
            const messages = JSON.parse(localStorage.getItem(contactId)) || [];
            messages.forEach(msg => {
                const div = document.createElement("div");
                div.classList.add(msg.sender);
                div.textContent = msg.text;
                chatContent.appendChild(div);
            });
        }, 200);
    }

    function sendMessage() {
        const text = messageInput.value.trim();
        if (!text || !currentContact) return;

        const messages = JSON.parse(localStorage.getItem(currentContact)) || [];
        messages.push({ sender: "user-msg", text });
        localStorage.setItem(currentContact, JSON.stringify(messages));

        const div = document.createElement("div");
        div.classList.add("user-msg");
        div.textContent = text;
        chatContent.appendChild(div);
        messageInput.value = "";
    }

    contactList.addEventListener("click", (e) => {
        const contactElement = e.target.closest("li");
        if (!contactElement) return;

        const contactId = contactElement.dataset.contact;
        if (!contactId) return;

        chatName.textContent = contactElement.querySelector("span").textContent;
        chatImg.src = contactElement.querySelector("img")?.src || "../recursos/default.jpg";
        openChat(contactId);
    });

    function renderContacts(searchQuery = "") {
        contactList.innerHTML = "";
        const filteredContacts = contacts.filter(contact =>
            contact.name.toLowerCase().includes(searchQuery.toLowerCase())
        );

        filteredContacts.forEach(contact => {
            const li = document.createElement("li");
            li.classList.add("contact");
            li.dataset.contact = contact.id;

            li.innerHTML = `
                <img src="${contact.img}" alt="${contact.name}">
                <span>${contact.name}</span>
            `;

            contactList.appendChild(li);
        });
    }

    searchBar.addEventListener("input", () => {
        renderContacts(searchBar.value);
    });

    sendBtn.addEventListener("click", sendMessage);
    messageInput.addEventListener("keypress", (e) => {
        if (e.key === "Enter") sendMessage();
    });

    renderContacts(); // Renderiza los contactos al cargar la página
});
