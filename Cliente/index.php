<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/login.css">
    <script type="module" src="JS/Conectar.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.10.0/firebase-app.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.10.0/firebase-database.js"></script>
    <title>Inicio</title>
</head>
<body>
<button id="checkConnection">Verificar Conexión</button> 


<div class="containerP">
    <div id="divContenedor" class="form-container sign-up-container">
        <form id="form">
            <div id="divLogo">
                <img src="SRC/imagen.png" alt="Logo" id="logo">
            </div>
            <h4>Inicio de Sesion</h4>
            <div id="divUsuario">
                <label for="text">Nombre Usuario:  </label>
                <input type="text" name="usuario" id="usuario">
            </div>
        
            <div id="divPassword">
                <label for="text">Contraseña</label>
                <input type="password" name="password" id="password">
                <span class="error" id="loginPasswordError"></span>
            </div>

            <div id="divButton">
                <input type="submit" id="buttonSesion" name="buttonSesion">
            </div>
            <div>
                <br>
                <a class="w3-text-blue" id="recuperarContrasena">He olvidado mi contraseña</a>
            </div>
        </form>
    </div>
</div>
<script type="module" src="JS/Conectar.js"></script>
<script src="JS/java.js"></script>
</body>
</html>



