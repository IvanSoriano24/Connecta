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
        <!-- Seccion de los Campos Libres -->
        <div id="cambLib">
            <section id="content">
                <!-- NAVBAR -->
                <?php include 'navbar.php'; ?>
                <!-- MAIN -->
                <main class="text-center ">
                    <!-- Botones para cambiar las secciones -->
                    <div class="btn-group mb-4" role="group">
                        <!-- Boton para cambiar a la seccion de campos libres -->
                        <button type="button" class="btn btn-primary" id="btnCam">Campos Libres</button>
                        <!-- Boton para cambiar a la seccion de configuracion de administrador -->
                        <button type="button" class="btn btn-primary" id="btnAdmin">Configuracion de Administradores</button>
                        <!-- Boton para cambiar a la seccion de series de folio -->
                        <button type="button" class="btn btn-primary" id="btnSerie">Configuracion de Series</button>
                    </div>
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
                                            <a href="#">Campos Libres</a>
                                        </li>
                                    </ul>
                                </div>
                                <button class="btn btn-success" id="btnAgregarCampo">
                                    <i class='bx bxs-briefcase'></i> Seleccionar Campo Libre
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
                                                    <th scope="col">Tabla</th>
                                                    <th scope="col">Campo Usado</th>
                                                    <th scope="col">Descripcion</th>
                                                </tr>
                                            </thead>
                                            <tbody id="parametros">
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
        <!-- Seccion de los los Administradores -->
        <div id="admin" style="display:none">
            <section id="content">
                <!-- NAVBAR -->
                <?php include 'navbar.php'; ?>
                <!-- MAIN -->
                <main class="text-center ">
                    <!-- Botones para cambiar las secciones -->
                    <div class="btn-group mb-4" role="group">
                        <!-- Boton para cambiar a la seccion de los campos libres -->
                        <button type="button" class="btn btn-primary" id="btnCamAdmin">Campos Libres</button>
                        <!-- Boton para cambiar a la seccion de la configuracion de administrador -->
                        <button type="button" class="btn btn-primary" id="btnAdminAdmin">Configuracion de Administradores</button>
                        <!-- Boton para cambiar a la seccion de series de folios -->
                        <button type="button" class="btn btn-primary" id="btnSerieAdmin">Configuracion de Series</button>
                    </div>
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
                                            <!-- Seccion del Modulo -->
                                            <a href="#">Administrador</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <!-- Tabla de administradores -->
                            <div class="table-data">
                                <div class="order">
                                    <input type="hidden" id="idDocumentoAdmin">
                                    <div class="head">
                                        <table id="tablaAdministradores">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Administradores</th>
                                                    <th scope="col">Clave</th>
                                                    <th scope="col">Editar</th>
                                                </tr>
                                            </thead>
                                            <tbody id="administradores">
                                                <!-- Los administradores se agregarán aquí dinámicamente -->
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
        <!-- Seccion de las Series de los Folios -->
        <div id="serie" style="display:none">
            <section id="content">
                <!-- NAVBAR -->
                <?php include 'navbar.php'; ?>
                <!-- MAIN -->
                <main class="text-center ">
                    <!-- Botones para cambiar las secciones -->
                    <div class="btn-group mb-4" role="group">
                        <!-- Boton para cambiar a la seccion de los campos libres -->
                        <button type="button" class="btn btn-primary" id="btnCamSerie">Campos Libres</button>
                        <!-- Boton para cambiar a la seccion de los administradores -->
                        <button type="button" class="btn btn-primary" id="btnAdminSerie">Configuracion de Administradores</button>
                        <!-- Boton para cambiar a la seccion de las series de los folios -->
                        <button type="button" class="btn btn-primary" id="btnSerieSerie">Configuracion de Series</button>
                    </div>
                    <div class="card-body">
                        <div class="container-fluid mt-10">
                            <div class="head-title">
                                <div class="left">
                                    <!-- Titulo del modulo -->
                                    <h1>Parametros del Sistema</h1>
                                    <ul class="breadcrumb">
                                        <li>
                                            <a href="Dashboard.php">Inicio</a>
                                        </li>
                                        <li><i class='bx bx-chevron-right'></i></li>
                                        <li>
                                            <!-- Seccion del modulo -->
                                            <a href="#">Series</a>
                                        </li>
                                    </ul>
                                </div>
                                <button class="btn btn-success" id="btnAgregarSerie">
                                    <i class='bx bxs-briefcase'></i> Seleccionar Serie
                                </button>
                            </div>
                            <!-- Tabla de las series -->
                            <div class="table-data">
                                <div class="order">
                                    <input type="hidden" id="idDocumentoVend">
                                    <div class="head">
                                        <table id="tablaSeries">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Tipo de Documento</th>
                                                    <th scope="col">Serie</th>
                                                    <th scope="col">Tipo</th>
                                                </tr>
                                            </thead>
                                            <tbody id="series">
                                                <!-- Las series se agregarán aquí dinámicamente -->
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

        <!-- Modales -->
        <!-- Modal Campos Libres-->
        <div id="seleccionarCampoLibre" class="modal fade" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <!-- Encabezado -->
                    <div class="modal-header bg-primary text-white border-0">
                        <h5 class="modal-title w-100 text-center">Seleccionar Campo Libre</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <!-- Cuerpo -->
                    <div class="modal-body px-4">
                        <form id="formCamposLibres">
                            <div class="row g-3">
                                <!-- Seleccion de la tabla de SAE -->
                                <div class="col-md-6">
                                    <label for="selectTabla" class="form-label fw-semibold">Tabla</label>
                                    <select id="selectTabla" class="form-select form-select-sm">
                                        <option selected disabled>Seleccione una tabla</option>
                                    </select>
                                </div>
                                <!-- Seleccion del campo de la tabla seleccionada -->
                                <div class="col-md-6">
                                    <label for="selectCampo" class="form-label fw-semibold">Campo</label>
                                    <select id="selectCampo" class="form-select form-select-sm">
                                        <option selected disabled>Seleccione un campo</option>
                                    </select>
                                </div>
                                <!-- Campo de la descripcion del campo -->
                                <div class="col-12">
                                    <div class="form-floating">
                                        <input type="text" class="form-control form-control-sm" id="descripcion" placeholder="Descripción">
                                        <label for="descripcion">Descripción</label>
                                    </div>
                                </div>
                            </div>
                            <hr class="my-4">
                            <!-- Tabla con los campos usados de la tabla seleccionada -->
                            <div id="camposSeleccionados">
                                <h6 class="fw-semibold mb-3">Campos Seleccionados</h6>
                                <ul id="listaCamposSeleccionado" class="list-group list-group-flush">
                                    <!-- Los ítems seleccionados -->
                                </ul>
                            </div>
                        </form>
                    </div>
                    <!-- Pie de modal con los botones-->
                    <div class="modal-footer border-0">
                        <!-- Boton para cancelar la accion y cerra el modal-->
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <!-- Boton para guardar (revisar el archivo JS/parametros.js) para su funcionamiento -->
                        <button type="button" class="btn btn-primary" id="btnGuardarCamposLibres">Guardar Campo Libre</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal Administrador-->
        <div id="seleccionarClaveAdministrador" class="modal fade" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <!-- Encabezado -->
                    <div class="modal-header bg-primary text-white border-0">
                        <h5 class="modal-title w-100 text-center">Seleccionar Vendedor para Administrador</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <!-- Cuerpo -->
                    <div class="modal-body">
                        <form id="formCamposLibres" novalidate>
                            <div class="row gx-3 gy-4">
                                <!-- Nombre del administrador -->
                                <div class="col-md-6">
                                    <label for="nombreAdministrador" class="form-label fw-semibold">Administrador</label>
                                    <input
                                        type="text"
                                        class="form-control form-control-sm"
                                        id="nombreAdministrador"
                                        name="nombreAdministrador"
                                        readonly
                                        tabindex="-1" />
                                    <input type="hidden" id="idUsuario" name="idUsuario" />
                                </div>
                                <!-- Select de vendedores -->
                                <div class="col-md-6">
                                    <label for="vendedores" class="form-label fw-semibold">Vendedores</label>
                                    <select
                                        id="vendedores"
                                        name="vendedores"
                                        class="form-select form-select-sm"
                                        required>
                                        <option value="" disabled selected>Seleccione un Vendedor</option>
                                        <!-- Opciones se inyectan vía JS -->
                                    </select>
                                </div>
                            </div>
                        </form>
                    </div>
                    <!-- Pie de modal -->
                    <div class="modal-footer border-0">
                        <!-- Boton para cancelar y cerrar el modal -->
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Cancelar
                        </button>
                        <!-- Boton para guardar (revisar el archivo JS/parametros.js) para su funcionamiento -->
                        <button type="button" class="btn btn-primary" id="btnGuardarClave">
                            Guardar
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal Series-->
        <div id="seleccionarSerie" class="modal fade" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <!-- Encabezado -->
                    <div class="modal-header bg-primary text-white border-0">
                        <h5 class="modal-title w-100 text-center">Seleccionar Serie</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <!-- Cuerpo -->
                    <div class="modal-body px-4">
                        <form id="formCamposLibres">
                            <div class="row g-3">
                                <!-- Seleccion de folio -->
                                <div class="col-md-6">
                                    <label for="selectFolio" class="form-label fw-semibold">Documento</label>
                                    <select id="selectFolio" class="form-select form-select-sm">
                                        <option selected disabled>Seleccione un Tipo de Documento</option>
                                    </select>
                                </div>
                                <!-- Seleccion del la serie -->
                                <div class="col-md-6">
                                    <label for="selectSerie" class="form-label fw-semibold">Serie</label>
                                    <select id="selectSerie" class="form-select form-select-sm">
                                        <option selected disabled>Seleccione la Serie</option>
                                    </select>
                                </div>
                            </div>
                        </form>
                    </div>
                    <!-- Pie de modal con los botones-->
                    <div class="modal-footer border-0">
                        <!-- Boton para cancelar la accion y cerra el modal-->
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <!-- Boton para guardar la seleccion (revisar el archivo JS/parametros.js) para su funcionamiento -->
                        <button type="button" class="btn btn-primary" id="btnGuardarCamposLibres">Guardar Serie</button>
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