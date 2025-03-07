import { initializeApp } from "https://www.gstatic.com/firebasejs/10.4.0/firebase-app.js";
import { getAuth, signInWithEmailAndPassword } from "https://www.gstatic.com/firebasejs/10.4.0/firebase-auth.js";
import { getDatabase, ref, update } from 'https://www.gstatic.com/firebasejs/10.4.0/firebase-database.js';


const firebaseConfig = {
    apiKey: "AIzaSyAOnRHRND19HSaN19cUlez3AepxYOhszdM",
    authDomain: "porientadainternet.firebaseapp.com",
    projectId: "porientadainternet",
    storageBucket: "porientadainternet.appspot.com",
    messagingSenderId: "112181805077",
    appId: "1:112181805077:web:a8a3ec9a7d190c42448e1c"
};

const app = initializeApp(firebaseConfig);
const auth = getAuth(app);

var btnLogin = document.getElementById("LoginBtn");
var passwordInput = document.getElementById("password");

// Agregar evento de escucha a la tecla "Enter" en el campo de contraseña
passwordInput.addEventListener("keyup", function (event) {
    if (event.key === "Enter") {
        // Llama a la función de inicio de sesión cuando se presiona la tecla "Enter"
        login();
    }
});

btnLogin.addEventListener("click", login);

async function login() {
    const email = document.getElementById("email").value;
    const password = passwordInput.value;

    try {
        const userCredential = await signInWithEmailAndPassword(auth, email, password);
        const user = userCredential.user;
        console.log("Usuario inició sesión con UID:", user.uid);

        // Actualizar el estado del usuario en la base de datos
        const db = getDatabase(app);
        const userRef = ref(db, 'usuarios/' + user.uid);

        // Usar el método update para cambiar el valor de "estado" a true
        await update(userRef, {
            estado: true
        });

        // Redirige al usuario a la página de inicio después de iniciar sesión
        const token = await user.getIdToken();
        localStorage.setItem('userToken', token);
        window.location.href = "chatver2.html"; // Cambia esto por la URL de tu página de inicio
    } catch (error) {
        console.error("Error de inicio de sesión:", error);
        if (error.code === "auth/invalid-email") {
            alert("El correo electrónico es inválido.");
        } else if (error.code === "auth/wrong-password") {
            alert("La contraseña es incorrecta.");
        } else if (error.code === "auth/user-not-found") {
            alert("El usuario no existe.");
        } else {
            alert("Hubo un error al iniciar sesión. Por favor, inténtalo de nuevo.");
        }
    }
}