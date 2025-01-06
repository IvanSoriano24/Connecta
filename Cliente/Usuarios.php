<?php
session_start();
if (isset($_SESSION['usuario'])) {
    if ($_SESSION['usuario']['tipoUsuario'] == 'CLIENTE') {
        header('Location:Menu.php');
        exit();
    }
    $nombreUsuario = $_SESSION['usuario']["nombre"];
    $usuario = $_SESSION['usuario']["usuario"];
    $tipoUsuario = $_SESSION['usuario']["tipoUsuario"];
    if ($_SESSION['usuario']['tipoUsuario'] == 'ADMIISTRADOR') {
        header('Location:Dashboard.php');
        exit();
    }
    $mostrarModal = isset($_SESSION['empresa']) ? false : true;

    //$empresa = $_SESSION['empresa']['razonSocial'];
    if (isset($_SESSION['empresa'])) {
        $empresa = $_SESSION['empresa']['razonSocial'];
        $idEmpresa = $_SESSION['empresa']['id'];
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    }
} else {
    header('Location:../index.php');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap  -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <!-- My CSS -->
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/selec.css">
    <title>AdminHub</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body.modal-open .hero_area {
            filter: blur(5px);
        }

        /* Aquí eliminamos el subrayado que tiene el botón por la etiqueta <a> */
        .btn a {
            text-decoration: none;
            color: inherit;
        }
    </style>
</head>

<body>
    <div class="hero_area">
        <!-- Navbar -->
        <section id="navbar">
            <nav class="navbar navbar-light bg-light">
                <a class="navbar-brand" href="#"></a>
                <!-- Botón alineado a la derecha -->
                <button class="btn btn-secondary"
                    <?php echo isset($_SESSION['usuario']) ? 'disabled' : ''; ?>>
                    Usuarios
                </button>
            </nav>
        </section>
        <!-- SIDEBAR -->
        <section id="sidebar">
            <a href="#" class="brand">
                <i class='bx bxs-cloud'></i>
                <span class="text">MDCloud</span>
            </a>
            <ul class="side-menu top">
                <li class="active">
                    <a href="Dashboard.php">
                        <i class='bx bxs-dashboard'></i>
                        <span class="text">Inicio</span>
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class='bx bxs-shopping-bag-alt'></i>
                        <span class="text">Ventas</span>
                    </a>
                </li>
                <li>
                    <a href="Productos.php">
                        <i class='bx bxs-package'></i>
                        <span class="text">Productos</span>
                    </a>
                </li>
                <li>
                    <a href="Usuarios.php">
                        <i class='bx bxs-user'></i>
                        <span class="text">Mis Clientes</span>
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class='bx bxs-message-dots'></i>
                        <span class="text">Mensajes</span>
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class='bx bxs-file'></i>
                        <span class="text">Reportes</span>
                    </a>
                </li>
            </ul>
            <ul class="side-menu">
                <?php
                if ($tipoUsuario == "ADMINISTRADOR") { ?>
                    <li>
                        <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class='bx bxs-cog'></i>
                            <span class="text">Configuración</span>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="infoEmpresa.php" id="informaEmpresa">Información Empresa</a>
                            </li>
                            <li><a class="dropdown-item" href="ConexioSAE.php" id="infoSae">Conexión SAE</a></li>
                        </ul>
                    </li>
                <?php }
                ?>
                <li>
                    <a href="" class="logout" id="cerrarSesion">
                        <i class='bx bxs-log-out-circle'></i>
                        <span class="text">Cerrar Sesion</span>
                    </a>
                </li>
            </ul>
        </section>
        <!-- SIDEBAR -->
        <!-- CONTENT -->
        <section id="content">
            <!-- NAVBAR -->
            <nav>
            </nav>
            <!-- MAIN -->
            <main class="text-center my-5 hero_area">
                <div class="modal-header">
                    <h2 class="modal-title" id="clientes">Gestión de Usuarios</h5>
                </div>
                <div class="modal-body">
                    <!-- Botones de acciones principales -->
                    <div class="d-flex justify-content-between mb-3">
                        <button class="btn btn-success" id="btnAgregar">Agregar</button>
                        <button class="btn btn-warning" id="btnEditar">Editar</button>
                        <button class="btn btn-info" id="btnAsociarEmpresa" disabled>Asociar Empresa</button>
                        <button class="btn btn-secondary" id="btnExportar" disabled>Exportar</button>
                        <button class="btn btn-danger" id="btnSalir" onclick="window.location.href='Dashboard.php';">Salir</button>
                    </div>
                    <!-- Área para mostrar los datos de los clientes -->
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Nombre Completo</th>
                                    <th>Correo</th>
                                    <th>Estatus</th>
                                    <th>Rol</th>
                                </tr>
                            </thead>
                            <tbody id="tablaUsuarios">
                                <!-- Aquí se llenarán los datos dinámicamente con JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
            <!-- MAIN -->
        </section>
        <!-- CONTENT -->
    </div>
    <!-- JS Para la confirmacion empresa -->
    <script src="JS/menu.js"></script>
    <script src="JS/app.js"></script>
    <script src="JS/script.js"></script>
    <script src="JS/clientes.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            var tipoUsuario = '<?php echo $tipoUsuario; ?>';
            var usuario = '<?php echo $usuario; ?>';
            datosUsuarios(tipoUsuario, usuario); // Llamada a la función cuando la página de la empresa se ha cargado.
        });
    </script>
	</section>

</body>
</html>