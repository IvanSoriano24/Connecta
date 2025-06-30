<?php
session_start();
if (isset($_SESSION['usuario'])) {
    if ($_SESSION['usuario']['tipoUsuario'] == 'CLIENTE') {
        header('Location:Menu.php');
        exit();
    }
    $nombreUsuario = $_SESSION['usuario']["nombre"];
    $tipoUsuario = $_SESSION['usuario']["tipoUsuario"];
    $correo = $_SESSION['usuario']["correo"];
    /*if ($_SESSION['usuario']['tipoUsuario'] == 'ADMINISTRADOR') {
        header('Location:Dashboard.php');
        exit();
    }*/

    $mostrarModal = isset($_SESSION['empresa']) ? false : true;

    //$empresa = $_SESSION['empresa']['razonSocial'];
    if (isset($_SESSION['empresa'])) {
        $empresa = $_SESSION['empresa']['razonSocial'];
        $idEmpresa = $_SESSION['empresa']['id'];
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveUsuario = $_SESSION['empresa']['claveUsuario'] ?? null;
        $contrasena = $_SESSION['empresa']['contrasena'] ?? null;
        $claveSae = $_SESSION['empresa']['claveSae'] ?? null;
    }
    $csrf_token  = $_SESSION['csrf_token'];
} else {
    header('Location:../index.php');
}
/*
session_unset();
session_destroy(); */
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Asegúrate de tener FontAwesome incluido en tu <head> -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <!-- Bootsstrap  -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <!-- My CSS -->
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/selec.css">
    <title>MDConnecta</title>
    <link rel="icon" href="SRC/logoMDConecta.png" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body.modal-open .hero_area {
            filter: blur(5px);
            /* Difumina el fondo mientras un modal está abierto */
        }
        /* From Uiverse.io by barisdogansutcu */
        svg {
            width: 3.25em;
            transform-origin: center;
            animation: rotate4 2s linear infinite;
        }

        circle {
            fill: none;
            stroke: hsl(214, 97%, 59%);
            stroke-width: 2;
            stroke-dasharray: 1, 200;
            stroke-dashoffset: 0;
            stroke-linecap: round;
            animation: dash4 1.5s ease-in-out infinite;
        }

        @keyframes rotate4 {
            100% {
                transform: rotate(360deg);
            }
        }

        @keyframes dash4 {
            0% {
                stroke-dasharray: 1, 200;
                stroke-dashoffset: 0;
            }

            50% {
                stroke-dasharray: 90, 200;
                stroke-dashoffset: -35px;
            }

            100% {
                stroke-dashoffset: -125px;
            }
        }

        /* BOTON MOSTRAR MAS */
        /* From Uiverse.io by felipesntr */
        button {
            border: 2px solid #24b4fb;
            background-color: #24b4fb;
            border-radius: 0.9em;
            cursor: pointer;
            padding: 0.8em 1.2em 0.8em 1em;
            transition: all ease-in-out 0.2s;
            font-size: 16px;
        }

        button span {
            display: flex;
            justify-content: center;
            align-items: center;
            color: #fff;
            font-weight: 600;
        }

        button:hover {
            background-color: #0071e2;
        }
        .search-head h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
        }
    </style>
</head>
<body>
<div class="hero_area">
    <!-- SIDEBAR -->
    <?php include 'sidebar.php'; ?>
    <!-- CONTENT -->
    <section id="content">
        <!-- NAVBAR -->
        <?php include 'navbar.php'; ?>
        <!-- MAIN -->
        <main class="text-center">
            <div class="head-title">
                <div class="left">
                    <h1>Reportes</h1>
                    <ul class="breadcrumb">
                        <li>
                            <a href="#">Inicio</a>
                        </li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li>
                            <a class="" href="Reportes.php">Reportes</a>
                        </li>
                    </ul>
                </div>
                <div class="menu-reportes-container">
                    <!-- Ventas -->
                    <div class="menu-reportes-group">
                        <div class="menu-reportes-title">
                            <span>Ventas</span>
                        </div>
                        <div class="menu-reportes-btns">
                            <a href="ventasCantidadesR.php" class="menu-reportes-btn">
                                <span>Cantidades Ventas</span>
                                <i class="fa-solid fa-chart-line"></i>
                            </a>
                        </div>
                    </div>
                    <!-- Cuentas por Cobrar -->
                    <div class="menu-reportes-group">
                        <div class="menu-reportes-title">
                            <span>Cuentas por cobrar</span>
                        </div>
                        <div class="menu-reportes-btns">
                            <a href="estadosCuentasGeneralR.php" class="menu-reportes-btn">
                                <span>Estados de Cuenta General</span>
                                <i class="fa-solid fa-file-invoice-dollar"></i>
                            </a>
                            <a href="estadosCuentasDetalladoR.php" class="menu-reportes-btn">
                                <span>Estados de Cuenta Detallado</span>
                                <i class="fa-solid fa-file-invoice-dollar"></i>
                            </a>
                            <a href="cobranzaGeneralR.php" class="menu-reportes-btn">
                                <span>Cobranza General</span>
                                <i class="fa-solid fa-sack-dollar"></i>
                            </a>
                        </div>
                    </div>
                    <!-- 
                    <div class="menu-reportes-group">
                        <div class="menu-reportes-title">
                            <span>Ejemplo</span>
                        </div>
                        <div class="menu-reportes-btns">
                            <a href="reporte_ejemplo.php" class="menu-reportes-btn">
                                <span>Ejemplo</span>
                                <i class="fa-solid fa-lightbulb"></i>
                            </a>
                        </div>
                    </div>
                     -->
                </div>
            </div>
        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->
</div>
<!-- CONTENT -->
<!-- JS Para la confirmacion empresa -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
<script src="JS/menu.js"></script>
<script src="JS/app.js"></script>
<script src="JS/script.js"></script>
</body>
</html>