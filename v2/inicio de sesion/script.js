
const loginForm = document.getElementById('loginForm');
const errorDiv = document.getElementById('error');

loginForm.addEventListener('submit', function(event) {
    event.preventDefault();

    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;

    if (username === "admin" && password === "1234") {
        alert('Inicio de sesión exitoso');
    } else {
        errorDiv.style.display = 'block';
    }
});

const newsletterForm = document.getElementById('newsletterForm');

newsletterForm.addEventListener('submit', function(event) {
    event.preventDefault();

    const email = document.getElementById('email').value;
    alert(`Gracias por suscribirte al boletín informativo con el correo: ${email}`);
});
