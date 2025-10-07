<?php
//Iniciar sesion
session_start();
//Validar si hay una sesion
if (isset($_SESSION["usuario"])) {
    //Si la sesion iniciada es de un Cliente, redirigir al E-Commers
    if ($_SESSION['usuario']['tipoUsuario'] == 'CLIENTE') {
        header('Location:Menu.php');
        exit();
    }
    //Si existe una conexion, redirigir al Dashboard
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <!--<script type="module" src="JS/Conectar.js"></script>-->
    <!--<script src="https://www.gstatic.com/firebasejs/8.10.0/firebase-app.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.10.0/firebase-database.js"></script> -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Titulo y Logo -->
    <title>MDConnecta</title>
    <link rel="icon" href="SRC/logoMDConecta.png" />
</head>

<body>

    <div class="containerP">
        <div id="divContenedor" class="form-container sign-up-container">
            <form id="form" method="POST" action="../Servidor/PHP/conexion.php">
                <div id="divLogo">
                    <img src="SRC/imagen.png" alt="Logo" id="logo"> <br>
                    <span class="txt">
                        Grupo <br> Interzenda
                    </span>
                </div>

                <h4 class="txth1">Inicio de Sesion</h4>
                <div id="divUsuario">
                    <label for="text" class="txt">Nombre Usuario: </label>
                    <input type="text" name="usuario" id="usuario">
                </div>

                <div id="divPassword" style="position: relative;">
                    <label for="password" class="txt">Contraseña</label>
                    <input type="password" name="password" id="password">
                    <!-- Boton para mostrar/ocultar la contraseña -->
                    <button type="button" id="togglePassword" style="
                        position: absolute;
                        right: 10px;
                        top: 55%;
                        transform: translateY(-50%);
                        border: none;
                        background: none;
                        cursor: pointer;
                        outline: none;
                        box-shadow: none;
                        appearance: none;
                        -webkit-appearance: none;
                        -moz-appearance: none;
                    ">
                        <i class="bi bi-eye"></i> <!-- Icono inicial -->
                    </button>
                    <span class="error" id="loginPasswordError"></span>
                </div>
                <!-- Boton para iniciar sesion (revisar JS/menu.js) para su funcionamiento -->
                <div id="divButton">
                    <button type="submit" id="buttonSesion" name="buttonSesion" class="txt">Ingresar</button>
                </div>
                <div>
                    <br>
                </div>
                <input type="hidden" value="1" id="numFuncion" , name="numFuncion">
            </form>
        </div>
    </div>
    <script src="JS/java.js?n=1"></script>
    <script>
        //Funcion para mostrar u ocultar la contraseña
        document.getElementById("togglePassword").addEventListener("click", function() {
            let passwordInput = document.getElementById("password");
            let icon = this.querySelector("i");

            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                icon.classList.replace("bi-eye", "bi-eye-slash"); // Cambia el icono
            } else {
                passwordInput.type = "password";
                icon.classList.replace("bi-eye-slash", "bi-eye"); // Cambia el icono
            }
        });
    </script>
    <!--
<video muted autoplay loop>
    <source src="./video/fondo1.mp4" type= "video/mp4">
</video>
<div class="capa"> </div>
-->
</body>

</html>