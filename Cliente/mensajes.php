<?php
session_start();

if (isset($_SESSION['usuario'])) {
    if ($_SESSION['usuario']['tipoUsuario'] == 'CLIENTE') {
        header('Location:Menu.php');
        exit();
    }
    $nombreUsuario = $_SESSION['usuario']["nombre"];
    $tipoUsuario = $_SESSION['usuario']["tipoUsuario"];
    if ($_SESSION['usuario']['tipoUsuario'] == 'ADMIISTRADOR') {
        header('Location:Dashboard.php');
        exit();
    }

    if (isset($_SESSION['empresa'])) {
        $empresa = $_SESSION['empresa']['razonSocial'];
        $idEmpresa = $_SESSION['empresa']['id'];
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveVendedor = $_SESSION['empresa']['claveVendedor'];
    }
} else {
    header('Location:../index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootsstrap -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
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
</head>

<body>
    <div class="hero_area">
        <!-- SIDEBAR -->
        <?php include 'sidebar.php'; ?>
        <!-- CONTENT -->
        <section id="content">
            <?php include 'navbar.php'; ?>
            <!-- MAIN -->
            <main class="text-center">
                <div class="container mt-5">
                    <h1 class="text-center">Mensajes</h1>
                    <!-- Mostrar mensajes genéricos -->
                    <p class="text-center">Aquí puedes ver tus notificaciones generales.</p>
                    <hr>
                    <?php if ($tipoUsuario === 'ALMACENISTA' || $tipoUsuario === 'ADMINISTRADOR'): ?>
                        <h2 class="text-center">Comandas</h2>
                        <table class="table table-bordered table-striped mt-3" id="tablaComandas">
                            <thead>
                                <tr>
                                    <th>No. Pedido</th>
                                    <th>Nombre Cliente</th>
                                    <th>Status</th>
                                    <th>Fecha</th>
                                    <th>Hora</th>
                                    <th>Detalles</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- MODAL -->
                <!-- Modal para Ver Detalles -->
                <!-- Modal para Ver Detalles -->
                <div class="modal fade" id="modalDetalles" tabindex="-1" aria-labelledby="modalDetallesLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title" id="modalDetallesLabel">Detalles del Pedido</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                            </div>
                            <div class="modal-body">
                                <form id="formDetalles">
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <label for="detalleNoPedido" class="form-label">No. Pedido:</label>
                                            <input type="text" id="detalleNoPedido" class="form-control form-control-sm" readonly>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label for="detalleNombreCliente" class="form-label">Nombre Cliente:</label>
                                            <input type="text" id="detalleNombreCliente" class="form-control form-control-sm" readonly>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <label for="detalleStatus" class="form-label">Status:</label>
                                            <input type="text" id="detalleStatus" class="form-control form-control-sm" readonly>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label for="detalleFecha" class="form-label">Fecha:</label>
                                            <input type="text" id="detalleFecha" class="form-control form-control-sm" readonly>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label for="detalleHora" class="form-label">Hora:</label>
                                            <input type="text" id="detalleHora" class="form-control form-control-sm" readonly>
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Productos:</label>
                                        <ul id="detalleProductos" class="list-group" style="font-size: 1.2rem;">
                                            <!-- Los productos se cargarán aquí dinámicamente -->
                                        </ul>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Salir</button>
                                <button type="button" class="btn btn-success" id="btnTerminar">Terminar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </section>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/menu.js"></script>
    <script src="JS/app.js"></script>
    <script src="JS/script.js"></script>
    <script src="JS/mensajes.js"></script>
</body>

</html>