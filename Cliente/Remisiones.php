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
    <!-- Ionicons v5 -->
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
    <!-- IconPark Core -->
    <link rel="stylesheet" href="https://unpkg.com/@icon-park/web/lib/index.css" />

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
                        <h1>Remisiones</h1>
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
                            <button type="button" class="btn btn-primary filtro-rol" data-rol="Vendidos">Vendidos</button>
                            <button type="button" class="btn btn-secondary filtro-rol" data-rol="Cancelados">Cancelados</button>
                        </div>
                    </div>

                    <!-- TABLA PEDIDOS  -->
                    <div class="table-data" id="pedidosActivos">
                        <div class="order">
                            <label for="filtros" style="margin-right: 94%;">Filtrar Por:</label>
                            <div class="head">
                                <div class="input-group">
                                    <tr>
                                        <td>
                                            <label for="Periodo">Periodo: </label>
                                            &nbsp; &nbsp;
                                            <select id="filtroFecha">
                                                <option value="Hoy">Hoy</option>
                                                <option value="Mes">Este Mes</option>
                                                <option value="Mes Anterior">Mes Anterior</option>
                                                <option value="Todos">Todos</option>
                                            </select>
                                        </td>
                                    </tr>
                                </div>

                                <div class="input-group">

                                    <?php if ($tipoUsuario === "ADMINISTRADOR") { ?>
                                        <tr>
                                            <td>
                                                <label for="Vendedor">Vendedor: </label>
                                                &nbsp; &nbsp;
                                                <select id="filtroVendedor">
                                                </select>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </div>
                                <h3></h3>
                                <div class="input-group">
                                    <i class='bx bx-search search-icon'></i>
                                    <input
                                        id="searchTerm"
                                        class="search-input"
                                        type="text"
                                        placeholder="Buscar remisión..."
                                        onkeyup="debouncedSearch()" />
                                </div>
                                <!-- <i class='bx bx-filter'></i> -->
                            </div>
                            <table id="pedidos">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Clave</th>
                                        <th>Cliente</th>
                                        <th>Nombre</th>
                                        <th>Estatus</th>
                                        <th>Fecha Elaboracion</th>
                                        <th>Subtotal</th>
                                        <!--<th>Total de Comisiones</th>-->
                                        <th>Importe total</th>
                                        <?php //if ($tipoUsuario == "ADMINISTRADOR") {
                                        ?>
                                        <th>Nombre del vendedor</th>
                                        <?php //}
                                        ?>
                                        <th>Visualizar</th>
                                        <th>Facturar</th>
                                        <th>Estado Factura</th>
                                        <th>Mostrar Errores</th>
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
            </main>
            <!-- MAIN -->
        </section>
        <!-- CONTENT -->
    </div>
    <!-- Modal de Errores de Facturacion -->
    <div id="modalErrores" class="modal fade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Errores de Facturacion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formularioErrores" class="px-4 pb-4">
                    <div class="row">
                        
                            <div class="table-data">
                                <div class="order">
                                    <div class="head">
                                        <table
                                            class="table table-hover table-striped text-center align-middle">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th scope="col">Origen del Error</th>
                                                    <th scope="col">Problema</th>
                                                </tr>
                                            </thead>
                                            <tbody id="detallesErrores">
                                                <!-- Los productos se generarán aquí dinámicamente -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        
                    </div>
                    <!-- botones al pie -->
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="cerrarModalFooter">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- CONTENT -->
    <!-- JS Para la confirmacion empresa -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/menu.js"></script>
    <script src="JS/app.js"></script>
    <script src="JS/script.js"></script>
    <script src="JS/remisiones.js"></script>
    <script>
        var tipoUsuario = "<?php echo $tipoUsuario; ?>";
        if (tipoUsuario === "ADMINISTRADOR") {
            llenarFiltroVendedor();
        }
    </script>
    <script>
        // Asignar el evento "change" al select del filtro (asegúrate que el id sea correcto)
        /*document.getElementById("filtroFecha").addEventListener("change", function() {
            const filtroSeleccionado = this.value;
            cargarPedidos(filtroSeleccionado);
        });*/
        // Evento para el botón "Mostrar más"
        $("#selectCantidad").on("change", function() {
            const seleccion = parseInt($(this).val(), 10);
            registrosPorPagina = isNaN(seleccion) ? registrosPorPagina : seleccion;
            paginaActual = 1; // volvemos a la primera página
            estadoRemision = localStorage.getItem("estadoRemision") || "Vendidos";
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
</body>

</html>