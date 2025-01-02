<?php
session_start();
if (isset($_SESSION["usuario"])) {
    if($_SESSION['usuario']['tipoUsuario'] == 'CLIENTE'){
        header('Location:Menu.php');
        exit();
    }
    header('Location:Dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/login.css">
    <link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Lato" />
    <!--<script type="module" src="JS/Conectar.js"></script>-->
    <script src="https://www.gstatic.com/firebasejs/8.10.0/firebase-app.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.10.0/firebase-database.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Inicio</title>
</head>
<body>



<div class="containerP">
    <div id="divContenedor" class="form-container sign-up-container">
        <form id="form" method="POST" action="../Servidor/PHP/conexion.php">
            <div id="divLogo">
                <img src="SRC/imagen.png" alt="Logo" id="logo"> <br>
                <span class= "txt">
                 Grupo <br> Interzenda
                </span>
            </div>

            <h4 class="txth1">Inicio de Sesion</h4>
            <div id="divUsuario">
                <label for="text" class="txt">Nombre Usuario:  </label>
                <input type="text" name="usuario" id="usuario">
            </div>
        
            <div id="divPassword">
                <label for="text" class="txt">Contraseña</label>
                <input type="password" name="password" id="password">
                <span class="error" id="loginPasswordError"></span>
            </div>

            <div id="divButton">
                <button type="submit" id="buttonSesion" name="buttonSesion" class="txt">Ingresar</button>
                
            </div>
            <div>
                <br>
                <a  id="recuperarContrasena" class="txt">Olvide mi contraseña</a>
            </div>
            <input type="hidden" value="1" id="numFuncion", name="numFuncion">
        </form>
    </div>
</div>
<script src="JS/java.js"></script>
<!--
<video muted autoplay loop>
    <source src="./video/fondo1.mp4" type= "video/mp4">
</video>
<div class="capa"> </div>
-->
</body>
</html>



