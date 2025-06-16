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
    //Obtener valores del Usuario
    $nombreUsuario = $_SESSION['usuario']["nombre"];
    $tipoUsuario = $_SESSION['usuario']["tipoUsuario"];
    $correo = $_SESSION['usuario']["correo"];

    //$mostrarModal = isset($_SESSION['empresa']) ? false : true;

    //$empresa = $_SESSION['empresa']['razonSocial'];

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
        <div id="cambLib">
            <section id="content">
                <!-- NAVBAR -->
                <?php include 'navbar.php'; ?>
                <!-- MAIN -->
                <main class="text-center ">
                    <!-- Botones para cambiar las secciones -->
                    <div class="btn-group mb-4" role="group">
                        <!-- Boton para cambiar a la seccion de informacion de empresa -->
                        <button type="button" class="btn btn-primary" id="btnCam">Campos Libres</button>
                        <!-- Boton para cambiar a la seccion de datos fiscales -->
                        <button type="button" class="btn btn-primary" id="btnAdmin">Configuracion de Administradores</button>
                        <!-- Boton para cambiar a la seccion de datos fiscales -->
                        <button type="button" class="btn btn-primary" id="btnVend">Configuracion de Vendedores</button>
                    </div>
                    <div class="card-body">
                        <div class="container-fluid mt-10">
                            <div class="head-title">
                                <div class="left">
                                    <h1>Parametros del Sistema</h1>
                                    <ul class="breadcrumb">
                                        <li>
                                            <a href="Dashboard.php">Inicio</a>
                                        </li>
                                        <li><i class='bx bx-chevron-right'></i></li>
                                        <li>
                                            <a href="#">Campos Libres</a>
                                        </li>
                                    </ul>
                                </div>
                                <button class="btn btn-success" id="btnAgregarCampo">
                                    <i class='bx bxs-briefcase'></i> Seleccionar Campo Libre
                                </button>
                            </div>
                            <!-- Tabla de correos -->
                            <div class="table-data">
                                <div class="order">
                                    <input type="hidden" id="idDocumentoClimb">
                                    <div class="head">
                                        <table id="tablaParametros">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Tabla</th>
                                                    <th scope="col">Campo Usado</th>
                                                    <th scope="col">Descripcion</th>
                                                </tr>
                                            </thead>
                                            <tbody id="parametros">
                                                <!-- Los correos se agregarán aquí dinámicamente -->
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
        <div id="admin" style="display:none">
            <section id="content">
                <!-- NAVBAR -->
                <?php include 'navbar.php'; ?>
                <!-- MAIN -->
                <main class="text-center ">
                    <!-- Botones para cambiar las secciones -->
                    <div class="btn-group mb-4" role="group">
                        <button type="button" class="btn btn-primary" id="btnCamAdmin">Campos Libres</button>
                        <!-- Boton para cambiar a la seccion de datos fiscales -->
                        <button type="button" class="btn btn-primary" id="btnAdminAdmin">Configuracion de Administradores</button>
                        <!-- Boton para cambiar a la seccion de datos fiscales -->
                        <button type="button" class="btn btn-primary" id="btnVendAdmin">Configuracion de Vendedores</button>
                    </div>
                    <div class="card-body">
                        <div class="container-fluid mt-10">
                            <div class="head-title">
                                <div class="left">
                                    <h1>Parametros del Sistema</h1>
                                    <ul class="breadcrumb">
                                        <li>
                                            <a href="Dashboard.php">Inicio</a>
                                        </li>
                                        <li><i class='bx bx-chevron-right'></i></li>
                                        <li>
                                            <a href="#">Administrador</a>
                                        </li>
                                    </ul>
                                </div>
                                <button class="btn btn-success" id="btnAgregar">
                                    <i class='bx bxs-briefcase'></i> Agregar
                                </button>
                            </div>
                            <!-- Tabla de correos -->
                            <div class="table-data">
                                <div class="order">
                                    <input type="hidden" id="idDocumentoAdmin">
                                    <div class="head">
                                        <table id="tablaParametros">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Administradores</th>
                                                    <th scope="col">Clave</th>
                                                    <th scope="col">Editar</th>
                                                </tr>
                                            </thead>
                                            <tbody id="parametros">
                                                <!-- Los correos se agregarán aquí dinámicamente -->
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
        <div id="vend" style="display:none">
            <section id="content">
                <!-- NAVBAR -->
                <?php include 'navbar.php'; ?>
                <!-- MAIN -->
                <main class="text-center ">
                    <!-- Botones para cambiar las secciones -->
                    <div class="btn-group mb-4" role="group">
                        <button type="button" class="btn btn-primary" id="btnCamVend">Campos Libres</button>
                        <!-- Boton para cambiar a la seccion de datos fiscales -->
                        <button type="button" class="btn btn-primary" id="btnAdminVend">Configuracion de Administradores</button>
                        <!-- Boton para cambiar a la seccion de datos fiscales -->
                        <button type="button" class="btn btn-primary" id="btnVendVend">Configuracion de Vendedores</button>
                    </div>
                    <div class="card-body">
                        <div class="container-fluid mt-10">
                            <div class="head-title">
                                <div class="left">
                                    <h1>Parametros del Sistema</h1>
                                    <ul class="breadcrumb">
                                        <li>
                                            <a href="Dashboard.php">Inicio</a>
                                        </li>
                                        <li><i class='bx bx-chevron-right'></i></li>
                                        <li>
                                            <a href="#">Vendedores</a>
                                        </li>
                                    </ul>
                                </div>
                                <button class="btn btn-success" id="btnAgregar">
                                    <i class='bx bxs-briefcase'></i> Agregar
                                </button>
                            </div>
                            <!-- Tabla de correos -->
                            <div class="table-data">
                                <div class="order">
                                    <input type="hidden" id="idDocumentoVend">
                                    <div class="head">
                                        <table id="tablaParametros">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Vendedor</th>
                                                    <th scope="col">Clave</th>
                                                    <th scope="col">Editar</th>
                                                </tr>
                                            </thead>
                                            <tbody id="parametros">
                                                <!-- Los correos se agregarán aquí dinámicamente -->
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

        <div id="seleccionarCampoLibre" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Seleccionar un Campo Libre</h5>
                    <button type="button" class="btn-close custom-close" data-dismiss="modal"
                        id="cerrarModalAsociasionHeader" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="container">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Seleccionar Tabla</h6>
                                <select id="selectTabla" class="form-select">
                                    <option selected disabled>Seleccione una Tabla</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <h6>Seleccionar Campo</h6>
                                <select id="selectCampo" class="form-select">
                                    <option selected disabled>Seleccione un Campo</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label for="descripcion">Descripcion</label>
                                <input type="text" name="descripcion" id="descripcion">
                            </div>
                            <div id="camposSeleccionados" class="mt-4">
                                <h6>Campos Seleccionados</h6>
                                <ul id="listaCamposSeleccionado" class="list-group">
                                    <!-- Las empresas asociadas se cargarán dinámicamente aquí -->
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cerrarModalCamposLibres"
                        data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary custom-blue" id="btnGuardarCamposLibres">Guardar
                        Campo Libre</button>
                </div>
            </div>
        </div>
    </div>
        <!-- CONTENT -->
    </div>
    </section>

    <!-- JS Para el funcionamiento del sistema -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/menu.js"></script>
    <script src="JS/app.js"></script>
    <script src="JS/parametros.js"></script>
    <script>
        $(function() {
            // al hacer clic en "Información General"
            $("#btnCam").click(function() {
                $("#admin").hide();
                $("#vend").hide();
                $("#cambLib").show();
                $("#btnAdmin, #btnVend").removeClass("active");
                $(this).addClass("active");
            });
            // al hacer clic en "Datos de Facturación"
            $("#btnAdmin").click(function() {
                $("#cambLib").hide();
                $("#vend").hide();
                $("#admin").show();
                $("#btnCam, #btnVend").removeClass("active");
                $("#btnAdminAdmin").addClass("active");
            });
            $("#btnVend").click(function() {
                $("#cambLib").hide();
                $("#admin").hide();
                $("#vend").show();
                $("#btnCam, #btnAdmin").removeClass("active");
                $("#btnVendVend").addClass("active");
            });
            // inicia con primer botón activo
            $("#btnCam").addClass("active");
        });
    </script>
    <script>
        $(function() {
            $("#btnCamAdmin").click(function() {
                $("#admin").hide();
                $("#vend").hide();
                $("#cambLib").show();
                $("#btnAdmin, #btnVend").removeClass("active");
                $("#btnCam").addClass("active");
            });
            // al hacer clic en "Datos de Facturación"
            $("#btnAdminAdmin").click(function() {
                $("#cambLib").hide();
                $("#vend").hide();
                $("#admin").show();
                $("#btnCam, #btnVend").removeClass("active");
                $(this).addClass("active");
            });
            $("#btnVendAdmin").click(function() {
                $("#cambLib").hide();
                $("#admin").hide();
                $("#vend").show();
                $("#btnCam, #btnAdmin").removeClass("active");
                $("#btnVendVend").addClass("active");
            });
            // inicia con primer botón activo
            //$("#btnInfo").addClass("active");
        });
    </script>
    <script>
        $(function() {
            // al hacer clic en "Información General"
            $("#btnCamVend").click(function() {
                $("#admin").hide();
                $("#vend").hide();
                $("#cambLib").show();
                $("#btnAdmin, #btnVend").removeClass("active");
                $("#btnCam").addClass("active");
            });
            // al hacer clic en "Datos de Facturación"
            $("#btnAdminVend").click(function() {
                $("#cambLib").hide();
                $("#vend").hide();
                $("#admin").show();
                $("#btnCam, #btnVend").removeClass("active");
                $("#btnAdminAdmin").addClass("active");
            });
            $("#btnVendVend").click(function() {
                $("#cambLib").hide();
                $("#admin").hide();
                $("#vend").show();
                $("#btnCam, #btnAdmin").removeClass("active");
                $(this).addClass("active");
            });
            // inicia con primer botón activo
            //$("#btnInfo").addClass("active");
        });
    </script>
</body>

</html>