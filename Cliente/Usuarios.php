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
    <div class="">


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
                    <a href="Ventas.php">
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
                    <a href="Clientes.php">
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
            <nav style="display: flex; justify-content: flex-end;">
                <section id="navbar">
                    <a class="navbar-brand" href="#"></a>
                    <!-- Botón alineado a la derecha -->
                    <button class="btn btn-secondary" style="background-color: #49A1DF; color: white;">
                        <i class='bx bxs-user'></i>
                        <a class="brand" href="Usuarios.php" style="color: white;">Usuarios</a>
                    </button>
                </section>
            </nav>
            <!-- MAIN -->
            <main class="text-center">
                <div class="modal-header">
                    <h2 class="modal-title" id="clientes">Gestión de Usuarios</h5>
                </div>
                <div class="modal-body">
                    <!-- Botones de acciones principales -->
                    <div class="d-flex justify-content-between mb-3">
                        <button class="btn btn-success" id="btnAgregar">Agregar</button>
                        <button class="btn btn-info" id="btnAsociarEmpresa" disabled>Asociar Empresa</button>
                        <button class="btn btn-secondary" id="btnExportar" disabled>Exportar</button>
                        <button class="btn btn-danger" id="btnSalir"
                            onclick="window.location.href='Dashboard.php';">Salir</button>
                    </div>
                    <!-- Área para mostrar los datos de los clientes -->
                    <div class="table-data">
                        <div class="order">
                            <div class="head">
                                <h3></h3>
                                <i class='bx bx-search'></i>
                                <!-- <i class='bx bx-filter'></i> -->
                            </div>
                            <table class="table">
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

                        <div class="todo">
                            <div class="head">
                                <h3>Permisos</h3>
                                <!-- <i class='bx bx-plus'></i> -->
                                <!-- <i class='bx bx-filter'></i> -->
                            </div>
                            <ul class="todo-list">
                                
                            </ul>
                        </div>
                    </div>
                </div>


    </div>
    </main>
    <!-- MAIN -->
    </section>
    <!-- CONTENT -->
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Nombre Completo</th>
                                    <th>Correo</th>
                                    <th>Estatus</th>
                                    <th>Rol</th>
                                    <th>Opciones</th>
                                </tr>
                            </thead>
                            <tbody id="tablaUsuarios">
                                <!-- Aquí se llenarán los datos dinámicamente con JavaScript -->
                            </tbody>
                        </table>
                    </div>
            </main>
            <!-- MAIN -->
        </section>
        <!-- CONTENT -->
    </div>
    <div id="usuarioModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Agregar Usuario</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" id="cerrarModal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="agregarUsuarioForm">
                    <div class="modal-body">
                        <!-- Formulario de usuario -->
                        <div class="container mt-4">
                            <div class="row">
                                <!-- Columna izquierda para datos del usuario -->
                                <div class="col-md-5">
                                    <h5 class="mb-2">Datos del Usuario</h5>
                                    <div class="mb-2">
                                        <input type="text" id="idUsuario" hidden>
                                        <label for="usuario" class="form-label">Usuario</label>
                                        <input type="text" id="usuario" class="form-control">
                                    </div>
                                    <div class="mb-2">
                                        <label for="nombreUsuario" class="form-label">Nombre</label>
                                        <input type="text" id="nombreUsuario" class="form-control">
                                    </div>
                                    <div class="mb-2">
                                        <label for="apellidosUsuario" class="form-label">Apellidos</label>
                                        <input type="text" id="apellidosUsuario" class="form-control">
                                    </div>
                                    <div class="mb-2">
                                        <label for="correoUsuario" class="form-label">Correo Electrónico</label>
                                        <input type="text" id="correoUsuario" class="form-control">
                                    </div>
                                    <div class="mb-2">
                                        <label for="contrasenaUsuario" class="form-label">Contraseña</label>
                                        <input type="password" id="contrasenaUsuario" class="form-control">
                                    </div>
                                    <div class="mb-2">
                                        <label for="telefonoUsuario" class="form-label">Telefono</label>
                                        <input type="text" id="telefonoUsuario" class="form-control">
                                    </div>
                                    <div class="mb-2">
                                        <label for="rolUsuario" class="form-label">Rol</label>
                                        <select id="rolUsuario" class="form-select">
                                            <option selected disabled>Selecciona un rol</option>
                                            <option value="VENDEDOR">VENDEDOR</option>
                                            <option value="ALMACENISTA">ALMACENISTA</option>
                                            <option value="FACTURISTA">FACTURISTA</option>
                                            <option value="VENDEDOR">VENDEDOR</option>
                                            <!-- Aquí se llenará dinámicamente con los roles -->
                                        </select>
                                    </div>
                                </div>
                                <!-- Columna derecha para select y datos de empresas -->
                                <div class="col-md-7">
                                    <div class="mb-3">
                                        <label for="selectEmpresa" class="form-label">Seleccionar Empresa</label>
                                        <select id="selectEmpresa" class="form-select">
                                            <option selected disabled>Selecciona una empresa</option>
                                            <!-- Aquí se llenará dinámicamente con las empresas -->
                                        </select>
                                        <div class="mb-1">
                                            <button id="agregarEmpresaBtn" type="button" class="btn btn-primary">Agregar</button>
                                        </div>
                                    </div>
                                    <!-- Textarea para información adicional -->
                                    <div class="mb-4">
                                        <!--<label for="detallesEmpresa" class="form-label">Detalles de Empresa</label>
                                        <textarea id="detallesEmpresa" class="form-control" rows="4" disabled></textarea>-->
                                        <h6>Empresas Seleccionadas</h6>
                                        <table class="table table-bordered" id="tablaEmpresas">
                                            <thead>
                                                <tr>
                                                    <th>ID Empresa</th>
                                                    <th>Razón Social</th>
                                                    <th>Número Empresa</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Las filas se llenarán dinámicamente -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" id="cerrarModal">Cerrar</button>
                        <button type="submit" class="btn btn-primary" id="guardarDatosBtn">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- JS Para la confirmacion empresa -->
    <script src="JS/menu.js"></script>
    <script src="JS/app.js"></script>
    <script src="JS/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            var tipoUsuario = '<?php echo $tipoUsuario; ?>';
            var usuario = '<?php echo $usuario; ?>';
            datosUsuarios(tipoUsuario, usuario); // Llamada a la función cuando la página de la empresa se ha cargado.
        });
    </script>
    </section>
    <script src="JS/usuarios.js"></script>
</body>

</html>