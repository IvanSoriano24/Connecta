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
        <div id="divContenedor" class="login-card">
            <!-- Sección izquierda - Azul -->
            <div class="login-left">
                <div class="logo-container">
                    <div class="logo-wrapper">
                        <img src="Images/mdcloud.png" alt="MDCloud Logo" class="mdcloud-logo">
                    </div>
                    <p class="logo-tagline">De datos a decisiones</p>
                </div>
                <div class="welcome-text">
                    <h2>Bienvenido</h2>
                    
                    <p>MDConnecta</p>
                </div>
            </div>

            <!-- Sección derecha - Blanca -->
            <div class="login-right">
                <form id="form" method="POST" action="../Servidor/PHP/conexion.php">
                    <h4 class="login-title">Inicio de sesión</h4>
                    
                    <div class="input-group">
                        <input type="text" name="usuario" id="usuario" placeholder="Usuario" required>
                        <i class="bi bi-person input-icon"></i>
                    </div>

                    <div class="input-group">
                        <input type="password" name="password" id="password" placeholder="Contraseña" required>
                        <button type="button" id="togglePassword" class="toggle-password">
                            <i class="bi bi-eye"></i>
                        </button>
                        <span class="error" id="loginPasswordError"></span>
                    </div>

                    <div class="forgot-password">
                        <a href="#">¿Olvidaste tu contraseña?</a>
                    </div>

                    <button type="submit" id="buttonSesion" name="buttonSesion" class="login-button">
                        Ingresar
                    </button>

                    <input type="hidden" value="1" id="numFuncion" name="numFuncion">
                </form>
            </div>
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