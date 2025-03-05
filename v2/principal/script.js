let currentImageIndex = 0;
        const images = document.querySelectorAll('.carousel-images img');
        const dots = document.querySelectorAll('.dots-container .dot');
        const totalImages = images.length;

        function changeImage(index) {
            images.forEach((img, i) => {
                img.classList.remove('active');
                if (i === index) {
                    img.classList.add('active');
                }
            });

            dots.forEach((dot, i) => {
                dot.classList.remove('active');
                if (i === index) {
                    dot.classList.add('active');
                }
            });
        }

        function autoChangeImage() {
            currentImageIndex = (currentImageIndex + 1) % totalImages;
            changeImage(currentImageIndex);
        }

        dots.forEach(dot => {
            dot.addEventListener('click', () => {
                const index = parseInt(dot.getAttribute('data-index'));
                currentImageIndex = index;
                changeImage(currentImageIndex);
            });
        });

        setInterval(autoChangeImage, 5000); // Cambia de imagen cada 5 segundos


          // Función para actualizar las estrellas en función de la calificación
          function updateStars() {
            const courseRatings = document.querySelectorAll('.rating');
            
            courseRatings.forEach(rating => {
                const stars = rating.querySelectorAll('.star');
                const userRating = rating.getAttribute('data-user-rating');
                
                stars.forEach((star, index) => {
                    if (index < userRating) {
                        star.classList.add('selected');
                    } else {
                        star.classList.remove('selected');
                    }
                });

                // Evento para seleccionar las estrellas al hacer clic
                stars.forEach((star, index) => {
                    star.addEventListener('click', () => {
                        rating.setAttribute('data-user-rating', index + 1);
                        updateStars(); // Llama la función para actualizar las estrellas
                    });
                });
            });
        }

        // Llamada inicial para cargar las estrellas en la página
        updateStars();

// Script para gestionar el carrito

let cart = JSON.parse(localStorage.getItem('cart')) || [];
const cartCountElement = document.getElementById('cart-count');

function updateCartCount() {
    cartCountElement.textContent = cart.length;
}

document.querySelectorAll('.cart-add-icon').forEach(icon => {
    icon.addEventListener('click', (e) => {
        const courseId = e.target.getAttribute('data-id');
        const courseName = e.target.getAttribute('data-name');
        const courseAuthor = e.target.getAttribute('data-author');
        const coursePrice = e.target.getAttribute('data-price');

        // Verificar si el curso ya está en el carrito
        if (!cart.some(course => course.id === courseId)) {
            cart.push({
                id: courseId,
                name: courseName,
                author: courseAuthor,
                price: coursePrice
            });
            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartCount();
            alert(`${courseName} ha sido añadido al carrito`);
        } else {
            alert(`${courseName} ya está en el carrito`);
        }
    });
});

// Inicializar el contador de carrito en la página principal
if (cartCountElement) {
    updateCartCount();
}

        // boletin informativo
        const newsletterForm = document.getElementById('newsletterForm');

        newsletterForm.addEventListener('submit', function(event) {
            event.preventDefault();

            const email = document.getElementById('email').value;
            alert(`Gracias por suscribirte al boletín informativo con el correo: ${email}`);
        });


        function mostrarDiploma() {
            // Redirigir a la imagen del diploma
            window.location.href = 'https://www.canva.com/design/DAGRVIckG_4/PJvpqLuWHCLMqLQuZTTHqA/view?utm_content=DAGRVIckG_4&utm_campaign=designshare&utm_medium=link&utm_source=editor';
        }