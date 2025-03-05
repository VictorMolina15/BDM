<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/styles_chat.css">
    <title>X titulo</title>
</head>

<body>
    <header>
        <!-- Agregar NavBar Aquí -->
    </header>
    <div class="chat-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <input type="text" placeholder="Buscar..."  id="search-bar" class="search-bar">
            <hr>
            <ul id="contact-list" class="contact-list">
                <!--Aquí se llenan los contactos-->
            </ul>
        </div>

        <!-- Zona de chat -->
        <div class="chat-box">
            <div class="chat-header">
                <img id="chat-img" src="../recursos/gato2.jpeg" alt="Vendedor">
                <span id="chat-name">Selecciona un vendedor</span>
            </div>
            <div class="chat-content" id="chat-content">
                <p>Selecciona un contacto para empezar a chatear.</p>
            </div>
            <div class="chat-input">
                <button id="quote-btn">+</button>
                <input type="text" id="message-input" placeholder="Escribe un mensaje...">
                <button id="send-btn">Enviar</button>
            </div>
        </div>

    </div>
    <script src="../js/script_chat.js"></script>
</body>

</html>