<?php
//Iniciar sesion
session_start();
//Validar si hay una sesion
if (isset($_SESSION['usuario'])) {
    //Si la sesion iniciada es de un Cliente, redirigir al E-Commers
    if ($_SESSION['usuario']['tipoUsuario'] == 'CLIENTE') {
        header('Location:Menu.php');
        exit();
    }
    if ($_SESSION['usuario']['tipoUsuario'] != 'ADMINISTRADOR') {
        header('Location:Dashboard.php');
        exit();
    }
    //Obtener valores del Usuario
    $nombreUsuario = $_SESSION['usuario']["nombre"];
    $tipoUsuario = $_SESSION['usuario']["tipoUsuario"];
    $correo = $_SESSION['usuario']["correo"];

    //Obtener valores de la empresa
    if (isset($_SESSION['empresa'])) {
        $empresa = $_SESSION['empresa']['razonSocial'];
        $idEmpresa = $_SESSION['empresa']['id'];
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveUsuario = $_SESSION['empresa']['claveUsuario'] ?? null;
        $claveSae = $_SESSION['empresa']['claveSae'] ?? null;
        $contrasena = $_SESSION['empresa']['contrasena'] ?? null;
    }
    //Obtener token de seguridad
    $csrf_token  = $_SESSION['csrf_token'];
} else {
    //Si no hay una sesion iniciada, redirigir al index
    header('Location:../index.php');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootsstrap  -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <script src="JS/sideBar.js"></script>
    <!-- My CSS -->
    <link rel="stylesheet" href="CSS/style.css">

    <link rel="stylesheet" href="CSS/selec.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Titulo y Logo -->
    <title>MDConnecta</title>
    <link rel="icon" href="SRC/logoMDConecta.png" />
</head>

<body>
    <div class="hero_area">
        <?php include 'sidebar.php'; ?>

        <!-- CONTENT -->
         <section id="content">
        <!-- Seccion de los Campos Libres -->
        <div id="invetarioFisico">
            <section id="content">
                <!-- NAVBAR -->
                <?php include 'navbar.php'; ?>
                <!-- MAIN -->
                <main class="text-center ">
                    <div class="card-body">
                        <div class="container-fluid mt-10">
                            <div class="head-title">
                                <div class="left">
                                    <!-- Titulo del Modulo -->
                                    <h1>Parametros del Sistema</h1>
                                    <ul class="breadcrumb">
                                        <li>
                                            <a href="Dashboard.php">Inicio</a>
                                        </li>
                                        <li><i class='bx bx-chevron-right'></i></li>
                                        <li>
                                            <!-- Seccion del modulo -->
                                            <a href="#">Inventarios</a>
                                        </li>
                                    </ul>
                                </div>
                                <button class="btn btn-success" id="btnNuevoInventario">
                                    <i class='bx bxs-briefcase'></i> Nuevo Inventario
                                </button>
                            </div>
                            <!-- Tabla de campos -->
                            <div class="table-data">
                                <div class="order">
                                    <input type="hidden" id="idDocumentoClimb">
                                    <div class="head">
                                        <table id="tablaParametros">
                                            <thead>
                                                <tr>
                                                    <th scope="col">No Inventario</th>
                                                    <th scope="col">Fecha de Creacion</th>
                                                </tr>
                                            </thead>
                                            <tbody id="inventarios">
                                                <!-- Las tablas junto a sus campos se agregarán aquí dinámicamente -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
                <!-- MAIN -->
            </section>
        </div>
        </section>
        <!-- CONTENT -->
    </div>

    <!-- JS Para el funcionamiento del sistema -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/menu.js"></script>
    <script src="JS/app.js"></script>
    <script src="JS/script.js"></script>
    <script src="JS/parametros.js"></script>
    <!-- Script para los botones de navegacion en la seccion de los campos libres -->
    <script>
        $(function() {
            // al hacer clic en "Campos Libres"
            $("#btnCam").click(function() {
                //Se ocultan las otras secciones
                $("#admin").hide();
                $("#serie").hide();
                //Se muestra la seccion de los campos libres
                $("#cambLib").show();
                //Se remueve la clase 'active' de los botones de las otras secciones
                $("#btnAdmin, #btnSerie").removeClass("active");
                //Se añade la clase 'active' al boton de la seccion correspondiente
                $(this).addClass("active");
            });
            // al hacer clic en "Configuracion de Administradores"
            $("#btnAdmin").click(function() {
                //Se ocultan las otras secciones
                $("#cambLib").hide();
                $("#serie").hide();
                //Se muestra la seccion de la configuracion de los administradores
                $("#admin").show();
                //Se remueve la clase 'active' de los botones de las otras secciones
                $("#btnCam, #btnSerie").removeClass("active");
                //Se añade la clase 'active' al boton de la seccion correspondiente
                $("#btnAdminAdmin").addClass("active");
            });
            // al hacer clic en "Configuracion de Series"
            $("#btnSerie").click(function() {
                //Se ocultan las otras secciones
                $("#cambLib").hide();
                $("#admin").hide();
                //Se muestra la seccion de las series
                $("#serie").show();
                //Se remueve la clase 'active' de los botones de las otras secciones
                $("#btnCam, #btnAdmin").removeClass("active");
                //Se añade la clase 'active' al boton de la seccion correspondiente
                $("#btnSerieSerie").addClass("active");
            });
            // inicia con primer botón activo
            $("#btnCam").addClass("active");
        });
    </script>
    <!-- Script para los botones de navegacion en la seccion de la configuracion de los administradores -->
    <script>
        $(function() {
            // al hacer clic en "Campos Libres"
            $("#btnCamAdmin").click(function() {
                //Se ocultan las otras secciones
                $("#admin").hide();
                $("#serie").hide();
                //Se muestra la seccion de los campos libres
                $("#cambLib").show();
                //Se remueve la clase 'active' de los botones de las otras secciones
                $("#btnAdmin, #btnSerie").removeClass("active");
                //Se añade la clase 'active' al boton de la seccion correspondiente
                $("#btnCam").addClass("active");
            });
            // al hacer clic en "Configuracion de Administradores"
            $("#btnAdminAdmin").click(function() {
                //Se ocultan las otras secciones
                $("#cambLib").hide();
                $("#serie").hide();
                //Se muestra la seccion de la configuracion de los administradores
                $("#admin").show();
                //Se remueve la clase 'active' de los botones de las otras secciones
                $("#btnCam, #btnSerie").removeClass("active");
                //Se añade la clase 'active' al boton de la seccion correspondiente
                $(this).addClass("active");
            });
            // al hacer clic en "Configuracion de Series"
            $("#btnSerieAdmin").click(function() {
                //Se ocultan las otras secciones
                $("#cambLib").hide();
                $("#admin").hide();
                //Se muestra la seccion de la configuracion de las series
                $("#serie").show();
                //Se remueve la clase 'active' de los botones de las otras secciones
                $("#btnCam, #btnAdmin").removeClass("active");
                //Se añade la clase 'active' al boton de la seccion correspondiente
                $("#btnSerieSerie").addClass("active");
            });
        });
    </script>
    <!-- Script para los botones de navegacion en la seccion de las series -->
    <script>
        $(function() {
            // al hacer clic en "Campos Libres"
            $("#btnCamSerie").click(function() {
                //Se ocultan las otras secciones
                $("#admin").hide();
                $("#serie").hide();
                //Se muestra la seccion de los campos libres
                $("#cambLib").show();
                //Se remueve la clase 'active' de los botones de las otras secciones
                $("#btnAdmin, #btnSerie").removeClass("active");
                //Se añade la clase 'active' al boton de la seccion correspondiente
                $("#btnCam").addClass("active");
            });
            // al hacer clic en "Configuracion de Administradores"
            $("#btnAdminSerie").click(function() {
                //Se ocultan las otras secciones
                $("#cambLib").hide();
                $("#serie").hide();
                //Se muestra la seccion de la configuracion de los administradores
                $("#admin").show();
                //Se remueve la clase 'active' de los botones de las otras secciones
                $("#btnCam, #btnSerie").removeClass("active");
                //Se añade la clase 'active' al boton de la seccion correspondiente
                $("#btnAdminAdmin").addClass("active");
            });
            // al hacer clic en "Configuracion de Series"
            $("#btnSerieSerie").click(function() {
                //Se ocultan las otras secciones
                $("#cambLib").hide();
                $("#admin").hide();
                //Se muestra la seccion de las series
                $("#serie").show();
                //Se remueve la clase 'active' de los botones de las otras secciones
                $("#btnCam, #btnAdmin").removeClass("active");
                //Se añade la clase 'active' al boton de la seccion correspondiente
                $(this).addClass("active");
            });
            // inicia con primer botón activo
            //$("#btnInfo").addClass("active");
        });
    </script>
</body>

</html>