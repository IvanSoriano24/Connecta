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
            text-align: center;
            font-weight: 600;
        }

        .table-bitacora th:last-child,
        .table-bitacora td:last-child {
            text-align: center;
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
                    </div>
                </div>
            </main>
        </section>
    </div>

    <div class="modal fade" id="modalDetalleBitacora" tabindex="-1" aria-labelledby="modalDetalleLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalDetalleLabel">Detalle del registro</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <p class="mb-1 text-muted">Acción</p>
                            <p class="fw-semibold" id="detalleAccion"></p>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-1 text-muted">Usuario</p>
                            <p class="fw-semibold" id="detalleUsuario"></p>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-1 text-muted">Fecha</p>
                            <p class="fw-semibold" id="detalleFecha"></p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-1 text-muted">Pedido</p>
                            <p class="fw-semibold" id="detallePedido"></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 text-muted">Cliente</p>
                            <p class="fw-semibold" id="detalleCliente"></p>
                        </div>
                    </div>

                    <h6 class="mt-4">Productos</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Descripción</th>
                                    <th class="text-end">Cantidad</th>
                                    <th class="text-end">Precio</th>
                                </tr>
                            </thead>
                            <tbody id="detalleProductos"></tbody>
                        </table>
                    </div>

                    <h6 class="mt-4">Cambios</h6>
                    <div id="detalleCambios" class="row g-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
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

