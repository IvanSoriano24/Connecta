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

        /*********************/
        #content main .table-data .user-table {
            flex-grow: 1;
            flex-basis: 500px;
        }

        #content main .table-data .user-table table {
            width: 100%;
            border-collapse: collapse;
            /* Elimina espacios entre bordes */
            table-layout: fixed;
            /* Asegura que las columnas se distribuyan uniformemente */
            border-spacing: 0;
            /* Elimina cualquier espaciado entre las celdas */
        }

        #content main .table-data .user-table table th {
            padding: 12px;
            /* Padding uniforme */
            font-size: 13px;
            text-align: left;
            border-bottom: 1px solid var(--grey);
            /* Línea inferior */
            white-space: nowrap;
            /* Evita saltos de línea */
            overflow: hidden;
            /* Oculta contenido excedente */
            text-overflow: ellipsis;
            /* Agrega "..." si el contenido es demasiado largo */
        }

        #content main .table-data .user-table table td {
            padding: 12px;
            /* Padding uniforme */
            text-align: left;
            /* Alineación a la izquierda */
            vertical-align: middle;
            /* Alineación vertical */
            white-space: nowrap;
            /* Evita saltos de línea */
            overflow: hidden;
            /* Oculta contenido excedente */
            text-overflow: ellipsis;
            /* Agrega "..." si el contenido es demasiado largo */
        }

        #content main .table-data .user-table table tr td:first-child {
            display: flex;
            align-items: center;
            gap: 12px;
            /* Ajusta el espacio entre elementos */
            padding-left: 6px;
        }

        #content main .table-data .user-table table td img {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            /* Imágenes redondeadas */
            object-fit: cover;
            /* Asegura que la imagen se recorte bien */
        }

        #content main .table-data .user-table table tbody tr:hover {
            background: var(--grey);
            /* Fondo al pasar el cursor */
        }

        #content main .table-data .user-table table tr td .status {
            font-size: 10px;
            padding: 6px 16px;
            color: var(--light);
            border-radius: 20px;
            /* Bordes redondeados */
            font-weight: 700;
            white-space: nowrap;
            /* Evita saltos de línea */
        }

        #content main .table-data .user-table table tr td .status.completed {
            background: var(--blue);
            /* Fondo azul para "completado" */
        }

        #content main .table-data .user-table table tr td .status.process {
            background: var(--yellow);
            /* Fondo amarillo para "en proceso" */
        }

        #content main .table-data .user-table table tr td .status.pending {
            background: var(--orange);
            /* Fondo naranja para "pendiente" */
        }

        .custom-blue {
            background-color: #0066ff;
            border-color: #004bb5;
            color: white;
        }

        .custom-blue:hover {
            background-color: #004bb5;
            border-color: #003a8c;
        }
    </style>
</head>

<body>
    <div class="">
        <!-- SIDEBAR -->
        <?php include 'sidebar.php'; ?>
        <!-- CONTENT -->
        <section id="content">
            <!-- NAVBAR -->
            <?php include 'navbar.php'; ?>
            <!-- MAIN -->
            <main class="text-center">
                <div class="modal-header">
                    <h2 class="modal-title" id="clientes">Gestión de Usuarios</h5>
                </div>

                <!-- Botones de acciones principales -->
                <div class="d-flex justify-content-between mb-3">
                <?php if ($tipoUsuario == "ADMINISTRADOR") { ?>
                    <button class="btn btn-success" id="btnAgregar">
                        <i class='bx bxs-user-plus'></i> Agregar
                    </button>
                    
                    <button class="btn btn-info" id="btnAsociarEmpresa">
                        <i class='bx bxs-building-house'></i> Asociar Empresa
                    </button>
                    <?php } ?>
                    <!--<button class="btn btn-secondary" id="btnExportar" disabled>
                        <i class='bx bxs-export'></i> Exportar
                    </button>-->

                    <button class="btn btn-danger" id="btnSalir" onclick="window.location.href='Dashboard.php';">
                        <i class='bx bxs-exit'></i> Salir
                    </button>

                </div>
                <!-- Área para mostrar los datos de los clientes -->
                <div class="table-data">
                    <div class="user-table ">
                        <div class="head">
                            <h3></h3>
                            <i class='bx bx-search'></i>
                            <!-- <i class='bx bx-filter'></i> -->
                        </div>
                        <table class="">
                            <thead>
                                <tr>
                                    <th style="">Nombre Completo</th>
                                    <th style="">Correo</th>
                                    <th style="">Estatus</th>
                                    <th style="">Rol</th>
                                    <th style="">Editar</th>
                                    <th style="">Visualizar</th>
                                    <th style="">Asociaciones</th>
                                    <?php if ($tipoUsuario == "ADMINISTRADOR") { ?>
                                    <th style="">Dar de Baja</th>
                                    <th style="">Activar</th>
                                    <?php } ?>
                                </tr>
                            </thead>
                            <tbody id="tablaUsuarios">
                                <!-- Aquí se llenarán los datos dinámicamente con JavaScript -->
                            </tbody>
                        </table>
                    </div>

                    <!-- <div class="todo">
                        <div class="head">
                            <h3>Permisos</h3>
                          
                        </div>
                        <ul class="todo-list">

                        </ul>
                    </div> -->
                </div>
                <!-- MAIN -->
            </main>
        </section>
    </div>
    <!-- MODAL AGREGAR -->
    <div id="usuarioModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Agregar Usuario</h5>
                    <button type="button" class="btn-close custom-close" data-dismiss="modal" aria-label="Close" id="cerrarModalHeader">
                        <span aria-hidden="true"></span><!-- &times; -->
                    </button>
                </div>
                <form id="agregarUsuarioForm">
                    <div class="modal-body">
                        <!-- Formulario de usuario -->
                        <div class="container mt-8">
                            <div class="row">
                                <div class="col-md-8">
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
                                        <label for="telefonoUsuario" class="form-label">Teléfono</label>
                                        <input type="text" id="telefonoUsuario" class="form-control">
                                    </div>
                                    <div class="mb-2">
                                        <label for="rolUsuario" class="form-label">Rol</label>
                                        <select id="rolUsuario" class="form-select">
                                            <option selected disabled>Selecciona un rol</option>
                                            <option value="VENDEDOR">VENDEDOR</option>
                                            <option value="ALMACENISTA">ALMACENISTA</option>
                                            <option value="FACTURISTA">FACTURISTA</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" id="cerrarModalFooter">Cerrar</button>
                        <button class="btn btn-primary custom-blue" id="guardarDatosBtn">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL EMPRESAS -->
    <div id="asociarEmpresaModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Asociar Usuario con Empresa</h5>
                    <button type="button" class="btn-close custom-close" data-dismiss="modal" id="cerrarModalAsociasionHeader" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="container">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Seleccionar Usuario</h6>
                                <select id="selectUsuario" class="form-select">
                                    <option selected disabled>Seleccione un usuario</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <h6>Seleccionar Empresa</h6>
                                <select id="selectEmpresa" class="form-select">
                                    <option selected disabled>Seleccione una empresa</option>
                                </select>
                            </div>
                            <div id="empresasAsociadas" class="mt-4">
                                <h6>Empresas Asociadas</h6>
                                <ul id="listaEmpresasAsociadas" class="list-group">
                                    <!-- Las empresas asociadas se cargarán dinámicamente aquí -->
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cerrarModalAsociasionFooter" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary custom-blue" id="btnGuardarAsociacion">Guardar Asociación</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Observaciones -->
    <div id="verAsociacionesModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Asociaciones del Usuario</h5>
                    <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Razón Social</th>
                                <th>No. Empresa</th>
                            </tr>
                        </thead>
                        <tbody id="tablaAsociaciones">
                            <!-- Aquí se cargarán dinámicamente las asociaciones -->
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cerrarModalVisAsoFooter" data-dismiss="modal">Cerrar</button>
                </div>
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
            let tipoUsuario = '<?php echo $tipoUsuario; ?>';
            let usuario = '<?php echo $usuario; ?>';
            datosUsuarios(tipoUsuario, usuario); // Llamada a la función cuando la página de la empresa se ha cargado.
        });
    </script>
    </section>
    <script src="JS/usuarios.js"></script>
</body>

</html>

<!-- Columna derecha para select y datos de empresas -->
<!--<div class="col-md-7">
                                    <div class="mb-3">
                                        <label for="selectEmpresa" class="form-label">Seleccionar Empresa</label>
                                        <select id="selectEmpresa" class="form-select">
                                            <option selected disabled>Selecciona una empresa</option>-->
<!-- Aquí se llenará dinámicamente con las empresas -->
<!--</select>
                                        <div class="mb-1">
                                            <button id="agregarEmpresaBtn" type="button"
                                                class="btn btn-primary">Agregar</button>
                                        </div>
                                    </div>-->
<!-- Textarea para información adicional -->
<!--<div class="mb-4"> -->
<!--<label for="detallesEmpresa" class="form-label">Detalles de Empresa</label>
                                        <textarea id="detallesEmpresa" class="form-control" rows="4" disabled></textarea>-->
<!--<h6>Empresas Seleccionadas</h6>
                                        <table class="table table-bordered" id="tablaEmpresas">
                                            <thead>
                                                <tr>
                                                    <th>ID Empresa</th>
                                                    <th>Razón Social</th>
                                                    <th>Número Empresa</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody> -->
<!-- Las filas se llenarán dinámicamente -->
<!--</tbody>
                                        </table>
                                    </div>
                                </div>-->