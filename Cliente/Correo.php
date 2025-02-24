<?php
session_start();
if (isset($_SESSION['usuario'])) {
    $nombreUsuario = $_SESSION['usuario']["nombre"];
    $tipoUsuario = $_SESSION['usuario']["tipoUsuario"];
    $correo = $_SESSION['usuario']["correo"];

    //$empresa = $_SESSION['empresa']['razonSocial'];
    if (isset($_SESSION['empresa'])) {
        $empresa = $_SESSION['empresa']['razonSocial'];
        $idEmpresa = $_SESSION['empresa']['id'];
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveUsuario = $_SESSION['empresa']['claveUsuario'] ?? null;
        $claveSae = $_SESSION['empresa']['claveSae'] ?? null;
    }
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

    <!-- My CSS -->
    <link rel="stylesheet" href="CSS/style.css">

    <link rel="stylesheet" href="CSS/selec.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>AdminHub</title>
</head>

<body>
    <div class="hero_area">
        <?php include 'sidebar.php'; ?>

        <!-- CONTENT -->
        <section id="content">
            <!-- NAVBAR -->
            <?php include 'navbar.php'; ?>
            <!-- MAIN -->
            <main class="text-center ">
                <div class="card-body">
                    <div class="container-fluid mt-10">
                        <h2 class="text-center">Correos</h2>
                        <!-- Tabla de comandas -->
                        <div class="table-data">
                            <div class="order">
                                <button class="btn btn-success" id="btnAgregar">
                                    <i class='bx bxs-user-plus'></i> Agregar Correo
                                </button>
                                <div class="head">
                                    <table id="tablaCorreos">
                                        <thead>
                                            <tr>
                                                <th>Correos</th>
                                                <th>Visualizar</th>
                                                <th>Editar</th>
                                                <th>Eliminar</th>
                                            </tr>
                                        </thead>
                                        <tbody>
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
        <!-- CONTENT -->
    </div>
    </section>
    <!-- Modal Correo -->
    <div id="modalCorreo" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Agregar Correo</h5>
                    <button type="button" class="btn-close custom-close" data-dismiss="modal"
                        id="cerrarModalCorreoHeader" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="container">
                        <div class="row">
                            <label for="tipoUsuario">Tipo de Usuario</label>
                            <select id="selectUsuario" class="form-select">
                                <option selected disabled>Selecciona un Usuario</option>
                                <option value="ALMACENISTA">ALMACENISTA</option>
                                <option value="VENDEDOR">VENDEDOR</option>
                                <option value="FACTURISTA">FACTURISTA</option>
                            </select>
                            <label for="usuarioVendedor">Usuario</label>
                            <select id="selectVendedor" class="form-select" disabled>
                                <option selected disabled>Selecciona un Usuario</option>
                            </select>
                            <div class="mb-2">
                                <label for="correo" class="form-label">Correo</label>
                                <input type="text" id="correo" class="form-control" readonly1>
                            </div>
                            <div class="mb-2">
                                <label for="contraseña" class="form-label">Contraseña</label>
                                <input type="text" id="contraseña" class="form-control">
                            </div>
                            <input type="text" id="claveUsuario" name="claveUsuario" hidden readonly>
                            <input type="text" id="usuario" name="usuario" hidden readonly>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cerrarModalCorreoFooter"
                        data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary custom-blue" id="btnGuardarCorreo">Guardar
                        Correo</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal de edición de correo -->
    <div id="editarModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Correo</h5>
                    <button type="button" class="btn-close custom-close" data-dismiss="modal"
                    id="cerrarModalCorreoEditarHeader" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="container">
                        <div class="row">
                            <div class="mb-2">
                                <label for="correoEditar" class="form-label">Correo</label>
                                <input type="text" id="correoEditar" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <label for="contraseñaEditar" class="form-label">Contraseña</label>
                                <input type="text" id="contraseñaEditar" class="form-control" required>
                            </div>
                            <input type="hidden" id="documentId">
                            <input type="hidden" id="claveUsuarioEditar">
                            <input type="hidden" id="usuarioEditar">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="cerrarModalCorreoEditarFooter">Cancelar</button>
                    <button type="button" class="btn btn-primary custom-blue" id="btnGuardarEdicion">Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>

    <!-- CONTENT -->

    <!-- JS Para la confirmacion empresa -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/menu.js"></script>
    <script src="JS/app.js"></script>
    <script src="JS/correo.js"></script>
</body>

</html>