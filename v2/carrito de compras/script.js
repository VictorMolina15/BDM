let cartItems = [];
const cartIcon = document.querySelector('.cart-icon');
const cartContainer = document.getElementById('cart-items');
const courseCount = document.getElementById('course-count');

cartIcon.addEventListener('click', () => {
    addItemToCart({
        imgSrc: 'https://via.placeholder.com/80',
        title: 'Curso de Programación',
        author: 'John Doe',
        rating: 4.5,
        price: '$49.99'
    });
});

function addItemToCart(item) {
    cartItems.push(item);
    renderCartItems();
    updateCourseCount();
}

function removeItemFromCart(index) {
    cartItems.splice(index, 1);
    renderCartItems();
    updateCourseCount();
}

function renderCartItems() {
    if (cartItems.length === 0) {
        cartContainer.innerHTML = '<p>No hay cursos en el carrito.</p>';
        return;
    }

    cartContainer.innerHTML = cartItems.map((item, index) => `
        <div class="cart-item">
            <img src="${item.imgSrc}" alt="${item.title}">
            <div class="cart-item-details">
                <h4>${item.title}</h4>
                <p>${item.author}</p>
                <div class="rating">⭐ ${item.rating}</div>
            </div>
            <div>
                <span class="remove" onclick="removeItemFromCart(${index})">Eliminar</span>
            </div>
            <div class="price">${item.price}</div>
        </div>
    `).join('');
}

function updateCourseCount() {
    courseCount.textContent = cartItems.length;
}
