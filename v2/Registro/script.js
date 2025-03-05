

    document.getElementById('registerForm').addEventListener('submit', function(event) {
        event.preventDefault(); // Evitar el envío del formulario

        // Limpiar mensajes de error
        document.getElementById('nameError').textContent = '';
        document.getElementById('emailError').textContent = '';
        document.getElementById('passwordError').textContent = '';
        document.getElementById('confirmPasswordError').textContent = '';

        // Obtener valores de los campos
        const name = document.getElementById('full-name').value.trim();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value.trim();
        const confirmPassword = document.getElementById('confirm-password').value.trim();

        let valid = true;

        // Validación de nombre completo
        if (name === '') {
            document.getElementById('nameError').textContent = 'El nombre completo es obligatorio.';
            valid = false;
        }

        // Validación de correo electrónico
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email)) {
            document.getElementById('emailError').textContent = 'Ingresa un correo electrónico válido.';
            valid = false;
        }

        // Validación de contraseña
        if (password.length < 6) {
            document.getElementById('passwordError').textContent = 'La contraseña debe tener al menos 6 caracteres.';
            valid = false;
        }

        // Validación de confirmación de contraseña
        if (password !== confirmPassword) {
            document.getElementById('confirmPasswordError').textContent = 'Las contraseñas no coinciden.';
            valid = false;
        }

        // Si todo es válido, se puede proceder con el envío del formulario
        if (valid) {
            alert('Registro exitoso');
            // Aquí puedes agregar la lógica para enviar los datos al servidor
        }
    });
