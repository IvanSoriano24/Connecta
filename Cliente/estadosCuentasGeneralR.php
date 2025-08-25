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
    <!-- Bootsstrap  -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <script src="JS/sideBar.js"></script>
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


        .search-head h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
        }

        .input-group {
            position: relative;
            width: 300px;
            /* ajusta al ancho deseado */
        }

        .search-group .search-input,
        .input-group .search-input {
            width: 100%;
            padding: 0.5rem 1rem 0.5rem 2.5rem;
            font-size: 1rem;
            border: 1px solid #ccc;
            border-radius: 0.25rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .input-group .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.2rem;
            color: #888;
            pointer-events: none;
        }

        .input-group .search-input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }
        .pagination {
            text-align: center;
            margin-top: 1rem;
        }

        .pagination button {
            margin: 0 .25rem;
            padding: 0.4rem .8rem;
            border: 1px solid #007bff;
            background: none;
            cursor: pointer;
        }

        .pagination button.active {
            background: #007bff;
            color: #fff;
        }

        /*******************/
        .pagination-controls {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.5rem;
            margin-top: 1rem;
            font-family: Lato, sans-serif;
            font-size: 0.9rem;
            color: #333;
        }

        .cantidad-label {
            margin: 0;
        }

        .cantidad-select {
            padding: 0.4rem 0.6rem;
            font-size: 0.9rem;
            border: 1px solid #ccc;
            border-radius: 0.25rem;
            background-color: #fff;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .cantidad-select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }
        #filtroFecha,
        #filtroVendedor,
        #filtroClientes {
            border: 1px solid #ccc;
            border-radius: 0.25rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            color: #888;
            min-width: 80%;
        }
        #reportes {
            border-collapse: separate !important;
            border-spacing: 0;
            width: 100%;
            min-width: 1000px;
        }

        #reportes thead th {
            position: sticky;
            background-color: #f9f9f9;
            border-bottom: 2px solid gray;
            z-index: 2;
            white-space: nowrap; /* evita que se rompa el texto */
            top: -36px; /* sube 2px el sticky */
        }
        #clientesSugeridos li {
            padding: 5px;
            cursor: pointer;
        }

        #clientesSugeridos li.highlighted {
            background-color: #007bff;
            color: white;
        }

        .suggestions-list {
            list-style: none;
            margin: 0;
            padding: 0;
            border: 1px solid #ccc;
            border-top: none;
            background-color: white;
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-sizing: border-box;
        }

        .suggestions-list li {
            padding: 8px;
            cursor: pointer;
        }

        .suggestions-list li:hover {
            background-color: #f0f0f0;
        }
        .input-group label {
            width: 100%;
            text-align: left;
        }
        .input-group select {
            width: 180px;
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
                    <h1>Estados de Cuenta General</h1>
                    <ul class="breadcrumb">
                        <li>
                            <a href="#">Inicio</a>
                        </li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li>
                            <a class="" href="Reportes.php">Estados de Cuenta General</a>
                        </li>
                    </ul>
                </div>

                <!-- INFO CLIENTE -->
                <div class="info-cliente-card mb-4" style="margin:0 !important;">
                    <div class="info-cliente-header">
                        <h5 class="mb-0 fw-bold">
                            <i class="fa-solid fa-user"></i>
                            <span id="clienteNombre">Nombre Cliente</span>
                        </h5>
                    </div>
                    <div class="info-cliente-body row gx-5 gy-2 mt-2">
                        <!-- Columna izquierda -->
                        <div class="col-12 col-md-6" style="width: 49%">
                            <div class="mb-1"><b>Dirección:</b> <span id="clienteDireccion"></span></div>
                            <div class="mb-1"><b>RFC:</b> <span id="clienteRFC"></span></div>
                            <div class="mb-1"><b>Teléfono:</b> <span id="clienteTelefono"></span></div>
                            <div class="mb-1"><b>No. Ext.:</b> <span id="clienteNumExterior"></span></div>
                        </div>
                        <!-- Columna derecha -->
                        <div class="col-12 col-md-6" style="width: 49%">
                            <div class="mb-1"><b>Clasificación:</b> <span id="clienteClasificacion"></span></div>
                            <div class="mb-1 d-flex align-items-center flex-wrap" style="justify-content: space-between">
                                <div><b>Días Crédito:</b> <span id="clienteDiasCredito"></span></div>
                                <span class="mx-2"></span>
                                <div><b>Límite Crédito:</b> <span id="clienteLimiteCredito"></span></div>
                            </div>
                            <div class="mb-1 d-flex align-items-center flex-wrap" style="justify-content: space-between">
                                <div><b>Moneda:</b> <span id="clienteMoneda"></span></div>
                                <span class="mx-2"></span>
                                <div><b>Tipo de Cambio:</b> <span id="clienteTipoCambio"></span></div>
                            </div>
                            <div class="mb-1"><b>Saldo Disp.:</b> <span id="clienteSaldoDisp"></span></div>
                        </div>
                    </div>
                </div>

                <!-- TABLA PEDIDOS  -->
                <div class="table-data" id="reportesActivos" style="display: block !important; margin: 0 !important;">
                    <input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="order" style="overflow: visible">
                        <label for="filtros" style="margin-right: 94%;">Filtrar Por:</label>
                        <div class="head">
                            <div class="input-group">
                                <label for="filtroFechaInicio">Fecha Inicio: </label>
                                <input type="date" id="filtroFechaInicio" style="width:180px; align-items: center; padding: 5px; border: 1px solid #ccc; border-radius: 3px; font-size: 14px; box-sizing: border-box;" tabindex="-1" name="filtroFechaInicio">
                            </div>
                            <div class="input-group">
                                <label for="filtroFechaFin" style="margin-right: 20px">Fecha Fin: </label>
                                <input type="date" id="filtroFechaFin" style="width:180px; align-items: center; padding: 5px; border: 1px solid #ccc; border-radius: 3px; font-size: 14px; box-sizing: border-box;" tabindex="-1" name="filtroFechaFin">
                            </div>
                            <div class="input-group">
                                <?php if ($tipoUsuario === "ADMINISTRADOR") { ?>
                                    <label for="Vendedor">Vendedor: </label>
                                    <select id="filtroVendedor"></select>
                                <?php } ?>
                            </div>
                            <div class="input-group">
                                <input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo $csrf_token; ?>">
                                <label for="cliente">Cliente:</label>
                                <div class="input-container" style="position: relative;">
                                    <div style="display: flex; align-items: center; gap: 5px;">
                                        <input name="cliente" id="cliente" hidden/>
                                        <input name="filtroClientes" id="filtroClientes" autocomplete="off"
                                               oninput="toggleClearButton()" style="padding-right: 1rem; width: 170px;" />
                                        <button id="clearInput" type="button" class="btn" onclick="clearAllFields()" tabindex="-1"
                                                style="display: none; padding: 5px 10px;">
                                            <i class="bx bx-x"></i>
                                        </button>
                                        <button type="button" class="btn" onclick="abrirModalClientes()" tabindex="-1"
                                                style="padding: 5px 5px;">
                                            <i class="bx bx-search"></i>
                                        </button>
                                    </div>
                                    <ul id="clientesSugeridos" class="suggestions-list"></ul>
                                </div>
                            </div>
                            <div class="input-group">
                                <i class='bx bx-search search-icon'></i>
                                <input
                                    id="searchTerm"
                                    class="search-input"
                                    type="text"
                                    placeholder="Buscar..."
                                    onkeyup="debouncedSearchReportes()" />
                            </div>
                            <!-- <i class='bx bx-filter'></i> -->
                        </div>
                        <table id="reportes" class="table align-middle">
                            <thead>
                            <tr>
                                <th>Clave</th>
                                <th>Tipo</th>
                                <th>Concepto</th>
                                <th>Documento</th>
                                <th>Núm.</th>
                                <th>F. de aplicación</th>
                                <th>F. de venc. referencia</th>
                                <th style="text-align:right;">Cargos</th>
                                <th style="text-align:right;">Abonos</th>
                                <th style="text-align:right;">Saldo</th>
                            </tr>
                            </thead>
                            <tbody id="datosReportes">
                            <!-- Tus filas van aquí -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->
</div>
<!-- Modal clientes -->
<div id="modalClientes" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content" style="max-height: 90vh; overflow-y: auto;">
            <!-- Modal Header -->
            <div class="modal-header">
                <h5 class="modal-title">Ayuda Clientes</h5>
                <button type="button" class="btn-close" onclick="cerrarModalClientes()"></button>
            </div>
            <!-- Filtro Estático -->
            <div class="modal-static">
                <div class="form-group row">
                    <div class="col-4">
                        <label for="filtroCriterioClientes" class="form-label">Filtrar por:</label>
                        <select id="filtroCriterioClientes" class="form-control">
                            <option value="CLAVE">Clave</option>
                            <option value="NOMBRE">Nombre</option>
                        </select>
                    </div>
                    <div class="col-8">
                        <label for="campoBusquedaClientes" class="form-label">Buscar:</label>
                        <input type="search" id="campoBusquedaClientes" class="form-control"
                               placeholder="Escribe aquí...">
                    </div>
                </div>
            </div>
            <!-- Modal Body -->
            <div class="modal-body">
                <!-- Lista de Productos -->
                <div id="">
                    <table id="" name="tablalistaProductos" class="table table-striped">
                        <thead>
                        <tr>
                            <th>Clave</th>
                            <th>Nombre</th>
                            <th>Telefono</th>
                            <th>Saldo</th>
                        </tr>
                        </thead>
                        <tbody id="datosClientes">
                        <!-- Contenido dinámico -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer">
                <!-- <button type="button" class=" btn-cancel" onclick="cerrarModal()">C</button> -->
            </div>
        </div>
    </div>
</div>
<!-- CONTENT -->
<!-- JS Para la confirmacion empresa -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
<script src="JS/menu.js"></script>
<script src="JS/app.js"></script>
<script src="JS/script.js"></script>
<script src="JS/estadosCuentasGeneralR.js"></script>
<script>
    var tipoUsuario = "<?php echo $tipoUsuario; ?>";
    if (tipoUsuario === "ADMINISTRADOR") {
        llenarFiltroVendedor();
    } else {
        llenarFiltroCliente();
    }
</script>
</body>
</html>