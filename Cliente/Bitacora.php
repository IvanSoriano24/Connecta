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
    <!-- Bootstrap Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <script src="JS/sideBar.js?n=1"></script>
    <!-- My CSS -->
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/selec.css">
    <title>MDConnecta</title>
    <link rel="icon" href="SRC/logoMDConecta.png" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .filters-card {
            border-radius: 18px;
            padding: 1.5rem;
            background: #fff;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
            margin-bottom: 1.2rem;
        }

        .filters .form-label {
            font-weight: 600;
        }

        #estadoBitacora {
            min-height: 60px;
            background: #f8fafc;
            border: 1px dashed #cbd5f5;
            border-radius: 18px;
        }

        #content main .table-data.bitacora-wrapper>div {
            background: #ffffff;
            border-radius: 24px;
            border: 1px solid #eef2ff;
            box-shadow: 0 25px 60px rgba(15, 23, 42, 0.08);
            padding: 2rem;
        }

        .table-responsive {
            max-height: calc(100vh - 360px);
        }

        .badge-accion {
            font-size: 0.85rem;
            text-transform: capitalize;
        }

        .table-bitacora th {
            font-size: 0.9rem;
            font-weight: 600;
            color: #64748b;
            border-bottom: 2px solid #eef2ff;
        }

        .table-bitacora td {
            vertical-align: middle;
            font-size: 0.95rem;
            color: #0f172a;
        }

        .table-bitacora th:first-child,
        .table-bitacora td:first-child {
            width: 80px;
            min-width: 80px;
            max-width: 80px;
            font-weight: 600;
            text-align: center !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            vertical-align: middle !important;
        }

        .table-bitacora th:last-child,
        .table-bitacora td:last-child {
            text-align: center;
        }

        .table-bitacora tbody td:first-child {
            display: table-cell;
            text-align: center !important;
        }

        /* Modal detalle */
        .modal-detalle .modal-header {
            background: linear-gradient(135deg, #2563eb, #4f46e5);
            color: #fff;
            border-bottom: none;
        }

        .modal-detalle .modal-content {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 70px rgba(15, 23, 42, 0.2);
        }

        .modal-detalle .modal-body {
            background: #f8fafc;
        }

        .detalle-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .detalle-card {
            background: #fff;
            border-radius: 14px;
            padding: 1rem;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        }

        .detalle-card .detalle-label {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #94a3b8;
            margin-bottom: 0.2rem;
        }

        .detalle-card .detalle-value {
            font-weight: 600;
            color: #0f172a;
            font-size: 1.05rem;
        }

        .detalle-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.45rem 1.2rem;
            border-radius: 999px;
            font-weight: 600;
        }

        .detalle-section {
            background: #fff;
            border-radius: 16px;
            padding: 1.25rem;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.07);
            margin-bottom: 1.5rem;
        }

        .detalle-section h6 {
            font-size: 0.95rem;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            color: #475569;
        }

        .table-detalle {
            margin-bottom: 0;
        }

        .table-detalle thead th {
            background: #eef2ff;
            color: #475569;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            border-top: none;
        }

        .table-detalle tbody td {
            border-color: #f1f5f9;
        }

        .detalle-empty {
            padding: 2rem;
            text-align: center;
            color: #94a3b8;
        }

        .paginacion-bitacora {
            display: none;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 0 0;
        }

        .paginacion-bitacora.active {
            display: flex;
            flex-wrap: wrap;
        }

        .paginacion-bitacora .pagination {
            display: flex;
            gap: 0.4rem;
        }

        .paginacion-bitacora .pagination button {
            border: 1px solid #cbd5f5;
            background: #fff;
            color: #1e293b;
            padding: 0.35rem 0.75rem;
            border-radius: 10px;
            font-weight: 600;
            min-width: 34px;
            transition: all 0.2s ease;
        }

        .paginacion-bitacora .pagination button.active {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2);
        }

        .paginacion-bitacora .pagination button.disabled {
            opacity: 0.4;
            pointer-events: none;
        }

        .paginacion-bitacora .cantidad-label {
            font-weight: 600;
            color: #475569;
        }

        .paginacion-bitacora .cantidad-select {
            border-radius: 12px;
            border: 1px solid #cbd5f5;
            padding: 0.35rem 2rem 0.35rem 0.75rem;
        }
    </style>
</head>

<body>
    <div class="hero_area">
        <?php include 'sidebar.php'; ?>
        <section id="content">
            <?php include 'navbar.php'; ?>

            <main>
                <div class="head-title">
                    <div class="left">
                        <h1>Bitácora</h1>
                        <ul class="breadcrumb">
                            <li>
                                <a href="Dashboard.php">Inicio</a>
                            </li>
                            <li><i class='bx bx-chevron-right'></i></li>
                            <li>
                                <a href="#" class="active">Bitácora</a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="table-data bitacora-wrapper">
                    <div class="order">
                        <div class="filters-card filters">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="moduloFiltro" class="form-label">Módulo</label>
                                    <select id="moduloFiltro" class="form-select">
                                        <option value="">Selecciona una opción</option>
                                        <option value="PEDIDOS">Pedidos</option>
                                        <option value="CLIENTES">Clientes</option>
                                        <option value="FACTURAS">Facturas</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="accionFiltro" class="form-label">Acción</label>
                                    <select id="accionFiltro" class="form-select" disabled>
                                        <option value="">Selecciona un módulo primero</option>
                                    </select>
                                </div>
                                <div class="col-md-4" id="folioWrapper" style="display:none;">
                                    <label for="folioFiltro" class="form-label">Folio de pedido</label>
                                    <input type="text" id="folioFiltro" class="form-control" placeholder="Ej. 0000012345">
                                </div>
                                <div class="col-md-4">
                                    <label for="fechaInicio" class="form-label">Fecha inicio</label>
                                    <input type="date" id="fechaInicio" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label for="fechaFin" class="form-label">Fecha fin</label>
                                    <input type="date" id="fechaFin" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div id="estadoBitacora" class="estado-bitacora text-muted text-center py-4">
                            Completa los filtros para mostrar información.
                        </div>

                        <div class="table-responsive d-none" id="tablaBitacoraWrapper">
                            <table class="table table-striped align-middle table-bitacora">
                                <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Acción</th>
                                        <th scope="col">Usuario</th>
                                        <th scope="col">Fecha</th>
                                        <th scope="col">Pedido</th>
                                        <th scope="col">Cliente</th>
                                        <th scope="col">Detalles</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaBitacora"></tbody>
                            </table>
                        </div>

                        <div class="paginacion-bitacora d-none" id="paginacionControles">
                            <div id="paginacionBitacora" class="pagination"></div>
                            <div class="pagination-controls d-flex align-items-center gap-2">
                                <label for="selectCantidadBitacora" class="cantidad-label mb-0">Mostrar</label>
                                <select id="selectCantidadBitacora" class="cantidad-select">
                                    <option value="10">10</option>
                                    <option value="20">20</option>
                                    <option value="30">30</option>
                                </select>
                                <span class="cantidad-label mb-0">por página</span>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </section>
    </div>

    <div class="modal fade" id="modalDetalleBitacora" tabindex="-1" aria-labelledby="modalDetalleLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content modal-detalle">
                <div class="modal-header">
                    <div>
                        <p class="mb-1 text-uppercase small text-white-50">Detalle de la bitácora</p>
                        <h5 class="modal-title" id="modalDetalleLabel">Registro seleccionado</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="detalle-grid mb-2">
                        <div class="detalle-card text-center">
                            <p class="detalle-label">Acción</p>
                            <span class="detalle-chip bg-primary-subtle text-primary fw-semibold" id="detalleAccion"></span>
                        </div>
                        <div class="detalle-card">
                            <p class="detalle-label">Usuario</p>
                            <p class="detalle-value" id="detalleUsuario"></p>
                        </div>
                        <div class="detalle-card">
                            <p class="detalle-label">Fecha</p>
                            <p class="detalle-value" id="detalleFecha"></p>
                        </div>
                        <div class="detalle-card">
                            <p class="detalle-label">Pedido</p>
                            <p class="detalle-value" id="detallePedido"></p>
                        </div>
                        <div class="detalle-card">
                            <p class="detalle-label">Cliente</p>
                            <p class="detalle-value" id="detalleCliente"></p>
                        </div>
                    </div>

                    <div class="detalle-section">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Productos</h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-detalle">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Descripción</th>
                                        <th class="text-end">Cantidad anterior</th>
                                        <th class="text-end">Ajuste</th>
                                        <th class="text-end">Cantidad nueva</th>
                                        <th class="text-end">Precio</th>
                                    </tr>
                                </thead>
                                <tbody id="detalleProductos"></tbody>
                            </table>
                        </div>
                    </div>
                    <!--
                    <div class="detalle-section">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Cambios</h6>
                        </div>
                        <div id="detalleCambios" class="row g-3 d-none"></div>
                    </div>
                    -->
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
    <script src="JS/bitacora.js"></script>
</body>

</html>

