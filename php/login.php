<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>HiJinx</title>
    <link href="https://fonts.googleapis.com/css2?family=Russo+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="../css/style_v2.css">
</head>
<body>
<button class="button" onclick="location='index.php'">Volver</button>
<div align="center">
    <br><br><br><br><br><br><br><br><br><br><br>
    <form class="formulario">
        <p class="cuenta-gratis">Inicia Sesión</p>
        <input type="email" id="email" placeholder="Email">
        <input type="password" id="password" placeholder="Password">
        <p><input type="checkbox" id="showPasswordCheckbox" name="showPasswordCheckbox"> <label for="showPasswordCheckbox">Mostrar Contraseña</label></p>
        <input type="button" id="LoginBtn" value="Login">
    </form>
</div>
<script type="module" src="LoginFireBase.js"></script>
<script>
    document.getElementById("showPasswordCheckbox").addEventListener("change", function() {
        var passwordInput = document.getElementById("password");
        passwordInput.type = this.checked ? "text" : "password";
    });
</script>
</body>
</html>
