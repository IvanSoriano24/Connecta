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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

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
    </style>
    <style>
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

        /*
        /* BOTON MOSTRAR MAS ¿Ocupas esto Jose?
        Este mismo codigo estaba en: Remisiones, estadosCuentaGeneralR,
        Reportes, cobranzaGeneralR, ventasCantidadesR, Facturas, ventasCantidadesR
        y estadosCuentasDetalladoR

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
        } */
    </style>
    <style>
        /* CSS */
        .search-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
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
            padding: 0.5rem 1rem;
            /* espacio a la izquierda para el icono */
            padding-left: 2.5rem;
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
    </style>
    <style>
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
    </style>
    <style>
        #filtroFecha,
        #filtroVendedor {
            /* espacio a la izquierda para el icono */
            padding-left: 2.5rem;
            /*font-size: 1rem;*/
            border: 1px solid #ccc;
            border-radius: 0.25rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            color: #888;
        }




        .filtros-container {
            margin-bottom: 1rem;
            text-align: center;
        }

        .filtros-titulo {
            margin-bottom: .5rem;
            font-weight: 600;
        }

        .filtros-row {
            display: flex;
            justify-content: center;
            /* centra horizontal */
            align-items: flex-end;
            gap: 2rem;
            /* espacio entre Periodo, Vendedor y Buscar */
            flex-wrap: wrap;
        }

        .filtro-item {
            display: flex;
            flex-direction: column;
            min-width: 200px;
            /* ancho fijo similar para todos */
            text-align: left;
        }

        .filtro-item label {
            font-size: 0.9rem;
            margin-bottom: 4px;
            font-weight: 500;
        }

        .form-control {
            padding: 6px 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 0.9rem;
            height: 38px;
            /* mismo alto */
        }

        /* buscador con ícono dentro */
        .search-wrapper {
            position: relative;
        }

        .search-wrapper .search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 1rem;
        }

        .search-wrapper input {
            padding-left: 32px;
            /* espacio para el ícono */
        }

        .modal-backdrop {
    display: none !important;
}

/*centrar modal*/
        .input-group-centered {
            width: 80%;
            margin: 0 auto;
        }
        .input-group-prepend .input-group-text {
            background-color: #f8f9fa;
            border-right: none;
        }
        #whatsappNumber {
            border-left: none;
            padding-left: 0;
        }
        #whatsappNumber:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .input-group:focus-within .input-group-prepend .input-group-text {
            border-color: #86b7fe;
        }
        .modal-body p {
            text-align: center;
        }
        .text-muted {
            text-align: center;
            display: block;
            margin-top: 8px;
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
            <main class="text-center ">
                <div class="head-title">
                    <div class="left">
                        <h1>Pedidos</h1>
                        <ul class="breadcrumb">
                            <li>
                                <a href="#">Inicio</a>
                            </li>
                            <li><i class='bx bx-chevron-right'></i></li>
                            <li>
                                <a class="" href="Ventas.php">Ventas</a>
                            </li>
                        </ul>
                    </div>
                    <!-- Secciones de Pedido-->
                    <div class="order">
                        <div style="align-items: center; display: flex; justify-content: center;" class="btn-group" role="group" aria-label="Filtros de Usuarios">
                            <button type="button" class="btn btn-primary filtro-rol" data-rol="Activos">Activos</button>
                            <button type="button" class="btn btn-secondary filtro-rol" data-rol="Vendidos">Vendidos</button>
                            <button type="button" class="btn btn-secondary filtro-rol" data-rol="Cancelados">Cancelados</button>
                        </div>
                    </div>
                    <!-- Boton de Pedido-->
                    <div class="button-container">
                        <?php if ($tipoUsuario != "FACTURISTA"  && $tipoUsuario != "SUPER-ALMACENISTA") { ?>
                            <a href="#" class="btn-crear" id="altaPedido">
                                <i class='bx bxs-file-plus'></i>
                                <span href="#" class="text">Crear Pedido</span>
                            </a>
                        <?php } ?>
                    </div>

                    <!-- TABLA PEDIDOS  -->
                    <div class="table-data" id="pedidosActivos">
                        <div class="order">
                            <div class="filtros-container">
                                <h6 class="filtros-titulo">Filtrar por:</h6>

                                <div class="filtros-row">
                                    <div class="filtro-item">
                                        <label for="filtroFecha">Periodo</label>
                                        <select id="filtroFecha" class="form-control">
                                            <option value="Hoy">Hoy</option>
                                            <option value="Mes">Este Mes</option>
                                            <option value="Mes Anterior">Mes Anterior</option>
                                            <option value="Todos">Todos</option>
                                        </select>
                                    </div>

                                    <?php if ($tipoUsuario === "ADMINISTRADOR") { ?>
                                        <div class="filtro-item">
                                            <label for="filtroVendedor">Vendedor</label>
                                            <select id="filtroVendedor" class="form-control">
                                                <!-- opciones dinámicas -->
                                            </select>
                                        </div>
                                    <?php } ?>

                                    <div class="filtro-item search-box">
                                        <label for="searchTerm">Buscar</label>
                                        <div class="search-wrapper">
                                            <i class='bx bx-search search-icon'></i>
                                            <input
                                                id="searchTerm"
                                                class="form-control"
                                                type="text"
                                                placeholder="Buscar pedido..."
                                                onkeyup="debouncedSearch()" />
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <table id="pedidos">
                                <thead>
                                    <tr>
                                        <th>Clave</th>
                                        <th>Cliente</th>
                                        <th>Nombre</th>
                                        <!--<th>Estatus</th>-->
                                        <th>Fecha Elaboracion</th>
                                        <th>Subtotal</th>
                                        <!--<th>Total de Comisiones</th>-->
                                        <th>Importe total</th>
                                        <?php //if ($tipoUsuario == "ADMINISTRADOR") { 
                                        ?>
                                        <th>Nombre del vendedor</th>
                                        <?php //} 
                                        ?>
                                        <th colspan="4" style="text-align:center;">Acciones</th>
                                        <th id="confirmacion">Confirmación</th>
                                    </tr>
                                </thead>
                                <tbody id="datosPedidos">
                                </tbody>
                            </table>
                            <!-- Botón Mostrar Más -->
                            <div id="pagination" class="pagination">
                            </div>
                            <div class="pagination-controls">
                                <label for="selectCantidad" class="cantidad-label">Mostrar</label>
                                <select id="selectCantidad" class="cantidad-select">
                                    <option value="10">10</option>
                                    <option value="20">20</option>
                                    <option value="30">30</option>
                                </select>
                                <span class="cantidad-label">por página</span>
                            </div>
                            <!--<button id="btnMostrarMas">
                                    <span>
                                        <svg height="24" width="24" viewBox="0 0 24 24"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M11 11V5h2v6h6v2h-6v6h-2v-6H5v-2z" fill="currentColor"></path>
                                        </svg>
                                        Mostrar
                                    </span>
                                </button>-->
                        </div>
                    </div>
                </div>

 <!-- Modal Whats -->
<div class="modal fade" id="whatsappModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Enviar confirmación por WhatsApp</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Ingrese el número de WhatsApp para enviar la confirmación:</p>
                
                <!-- Grupo de entrada centrado -->
                <div class="input-group mb-3 input-group-centered">
                    <div class="input-group-prepend">
                        <span class="input-group-text d-flex align-items-center">
                            <span class="me-2">🇲🇽</span>
                            <span>+52</span>
                        </span>
                    </div>
                    <input type="tel" 
                           id="whatsappNumber" 
                           class="form-control" 
                           placeholder="1234567890"
                           maxlength="10"
                           pattern="[0-9]{10}"
                           oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                </div>
                <small class="text-muted">Solo ingrese los 10 dígitos del número sin el prefijo.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="enviarWhatsAppBtn">Enviar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Correos Electrónicos -->
<div class="modal fade" id="modalEnvioCorreos" tabindex="-1" aria-labelledby="modalEnvioCorreosLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEnvioCorreosLabel">Enviar confirmación por correo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Ingrese las direcciones de correo electrónico (máximo 4)</p>
                
                <div id="emailContainer">
                    <!-- Los campos de correo se agregarán aquí -->
                </div>
                
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div id="contadorCorreos" class="text-muted">0/4 correos ingresados</div>
                    <button type="button" class="btn btn-sm btn-outline-primary add-email-btn" id="btnAddEmail">
                        <i class="bi bi-plus-circle"></i> Agregar otro correo
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnEnviarCorreos" disabled>Enviar</button>
            </div>
        </div>
    </div>
</div>
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
    <script src="JS/ventas.js"></script>
    <script>
        const tipoUsuario = "<?php echo $tipoUsuario; ?>";
        if (tipoUsuario === "ADMINISTRADOR") {
            llenarFiltroVendedor();
        }

        // Evento para el botón "Mostrar más"
        $("#selectCantidad").on("change", function() {
            const seleccion = parseInt($(this).val(), 10);
            registrosPorPagina = isNaN(seleccion) ? registrosPorPagina : seleccion;
            paginaActual = 1; // volvemos a la primera página
            estadoPedido = localStorage.getItem("estadoPedido") || "Activos";
            datosPedidos(true); // limpia la tabla y carga sólo registrosPorPagina filas
        });
        // Evento para el cambio del filtro
        document.getElementById("filtroFecha").addEventListener("change", function() {
            localStorage.setItem("filtroSeleccionado", this.value);
            paginaActual = 1; // Reinicia la paginación

            //document.getElementById("btnMostrarMas").style.display = "block"; // Asegura que el botón se muestre
            datosPedidos(true); // Carga inicial con nuevo filtro (limpia la tabla)
        });
        // Carga inicial cuando el DOM esté listo
        document.addEventListener("DOMContentLoaded", function() {
            paginaActual = 1;
            registrosPorPagina = 10;

            //document.getElementById("btnMostrarMas").style.display = "block";
            datosPedidos(true);
        });
    </script>
    <div id="tipoUsuario" data-tipo="<?php echo htmlspecialchars($tipoUsuario); ?>" style="display:none;"></div>
</body>

</html>