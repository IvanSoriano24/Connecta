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
    if ($_SESSION['usuario']['tipoUsuario'] == 'ADMIISTRADOR') {
        header('Location:Dashboard.php');
        exit();
    }

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
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootsstrap -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <!-- My CSS -->
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/selec.css">
    <link rel="stylesheet" href="CSS/carrito.css">

    <title>MDConnecta</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<style>
    #tablaComandas {
        width: 100%;
        table-layout: auto;
        /* Permite que las columnas se ajusten al contenido */
    }

    #tablaComandas th,
    #tablaComandas td {
        white-space: nowrap;
        /* Evita que el texto se corte o salte de línea */
    }



    .card-body {

        width: 1000px;
        max-width: 980px;
        margin: 0 auto;
        padding: 20px;
        border: 1px solid #ccc;
        border-radius: 5px;
        background-color: #fff;
    }
</style>
<style>
    .tabla-scroll {
        height: 350px;
        /* Altura fija para el área del scroll */
        overflow-y: auto;
        /* Activar scroll vertical */
    }

    .tabla-comandas {
        /*border-collapse: collapse;*/
        width: 100%;
    }

    .tabla-comandas thead {
        position: sticky;
        /* Encabezado fijo */
        /*top: 0;*/
        /* background-color: #f4f4f4;*/
        /* Fondo para destacar */
        z-index: 1;
        /* Mantener el encabezado sobre las filas */
    }
</style>
<style>
    .tabla-scroll-pedidos {
        height: 350px;
        /* Altura fija para el área del scroll */
        overflow-y: auto;
        /* Activar scroll vertical */
    }

    .tabla-pedidos {
        /*border-collapse: collapse;*/
        width: 100%;
    }

    .tabla-pedidos thead {
        position: sticky;
        /* Encabezado fijo */
        /*top: 0;*/
        /* background-color: #f4f4f4;*/
        /* Fondo para destacar */
        z-index: 1;
        /* Mantener el encabezado sobre las filas */
    }
</style>

<body>
    <div class="hero_area">
        <!-- SIDEBAR -->
        <?php include 'sidebar.php'; ?>
        <!-- CONTENT -->
        <section id="content">
            <?php include 'navbar.php'; ?>
            <!-- MAIN -->
            <main class="text-center">
                <h1 class="text-center">Mensajes</h1>
                <!-- Mostrar mensajes genéricos -->
                <p class="text-center">Aquí puedes ver tus notificaciones generales.</p>
                <div class="container mt-10">
                    <hr>
                </div>
                <?php if ($tipoUsuario === 'ADMINISTRADOR'): ?>
                    <div class="card-body">
                        <h2 class="text-center">Pedidos</h2>
                        <div class="mb-3">
                            <label for="filtroPedido" class="form-label">Filtrar por Status:</label>
                            <select id="filtroPedido" class="form-select form-select-sm" style="width: 150px;">
                                <option value="Sin Autorizar">Sin Autorizar</option>
                                <option value="Autorizado">Autorizadas</option>
                                <option value="Rechazado">No Autorizadas</option>
                                <option value="">Todos</option>
                            </select>
                        </div>
                        <!-- Tabla de pedidos -->
                        <div class="table-data">
                            <div class="order">
                                <div class="head">
                                    <div class="tabla-scroll-pedidos">
                                        <table id="tablaPedidos" class="tabla-pedidos">
                                            <thead>
                                                <tr>
                                                    <th>No. Pedido</th>
                                                    <th>Cliente</th>
                                                    <th class="col-fecha">Fecha</th>
                                                    <th>Vendedor</th>
                                                    <th>Status</th>
                                                    <th>Total</th>
                                                    <th>Detalles</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Los productos se generarán aquí dinámicamente -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <br>
                <?php if ($tipoUsuario === 'ALMACENISTA' || $tipoUsuario === 'ADMINISTRADOR'): ?>
                    <div class="card-body">
                        <h2 class="text-center">Comandas</h2>
                        <div class="mb-3">
                            <label for="filtroStatus" class="form-label">Filtrar por Status:</label>
                            <select id="filtroStatus" class="form-select form-select-sm" style="width: 150px;">
                                <option value="Abierta">Abiertas</option>
                                <option value="Pendiente">Pendientes</option>
                                <option value="TERMINADA">Terminadas</option>
                                <option value="">Todos</option>
                            </select>
                        </div>
                        <!-- Tabla de comandas -->
                        <div class="table-data">
                            <div class="order">
                                <div class="head">
                                    <div class="tabla-scroll">
                                        <table id="tablaComandas" class="tabla-comandas">
                                            <thead>
                                                <tr>
                                                    <th>No. Pedido</th>
                                                    <th>Nombre Cliente</th>
                                                    <th>Status</th>
                                                    <th class="col-fecha">Fecha</th>
                                                    <th>Hora</th>
                                                    <th>Detalles</th>
                                                    <?php if ($tipoUsuario === 'ADMINISTRADOR'): ?>
                                                        <th>Aurotizar Comanda</th>
                                                    <?php endif; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Los productos se generarán aquí dinámicamente -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <!-- MODAL -->
                <!-- Modal para Ver Detalles -->
                <div class="modal fade" id="modalDetalles" tabindex="-1" aria-labelledby="modalDetallesLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title" id="modalDetallesLabel">Detalles del Pedido</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Cerrar"></button>
                            </div>
                            <div class="modal-body">
                                <form id="formDetalles">
                                    <!-- Campo oculto para el ID -->
                                    <input type="hidden" id="detalleIdComanda">
                                    <input type="hidden" name="csrf_token_C" id="csrf_token_C" value="<?php echo $csrf_token; ?>">

                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <label for="detalleNoPedido" class="form-label">No. Pedido:</label>
                                            <input type="text" id="detalleNoPedido" class="form-control form-control-sm"
                                                readonly>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label for="detalleNombreCliente" class="form-label">Nombre
                                                Cliente:</label>
                                            <input type="text" id="detalleNombreCliente"
                                                class="form-control form-control-sm" readonly>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <label for="detalleStatus" class="form-label">Status:</label>
                                            <input type="text" id="detalleStatus" class="form-control form-control-sm"
                                                readonly>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label for="detalleFecha" class="form-label">Fecha:</label>
                                            <input type="text" id="detalleFecha" class="form-control form-control-sm"
                                                readonly>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label for="detalleHora" class="form-label">Hora:</label>
                                            <input type="text" id="detalleHora" class="form-control form-control-sm"
                                                readonly>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <label for="numGuia" class="form-label">Numero Guia</label>
                                            <input type="text" class="form-control form-control-sm" id="numGuia">
                                        </div>
                                        <div class="col-md-6 mb-2" id="divFechaEnvio">
                                            <label for="fechaEnvio" class="form-label">Fecha de Envio</label>
                                            <input type="text" class="form-control form-control-sm" id="fechaEnvio" readonly1>
                                        </div>
                                    </div>
                                    <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Datos de Envío</label>
                                        <button 
                                        type="button"
                                        id="datEnvio"
                                        class="btn btn-outline-primary btn-sm w-100"
                                        >Mostrar datos de envío</button>
                                    </div>
                                    </div>
                                    <div class="row d-none" id="datosEnvio"> <!-- style="display: none;" -->
                                        <div class="col-md-6">
                                            <h6 class="fw-bold">Dirección</h6>
                                            <div class="mb-3">
                                                <label for="nombreContacto" class="form-label">Nombre del contacto <span class="text-danger">*</span></label>
                                                <input type="text" id="nombreContacto" class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="compañiaContacto" class="form-label">Compañía <span class="text-danger">*</span></label>
                                                <input type="text" id="compañiaContacto" class="form-control" disabled>
                                            </div>
                                            <div class="mb-3">
                                                <label for="telefonoContacto" class="form-label">Teléfono <span class="text-danger">*</span></label>
                                                <input type="tel" id="telefonoContacto" class="form-control" disabled>
                                            </div>
                                            <div class="mb-3">
                                                <label for="correoContacto" class="form-label">Correo electrónico <span class="text-danger">*</span></label>
                                                <input type="email" id="correoContacto" class="form-control" disabled>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <h6 class="fw-bold">Detalles de la dirección</h6>
                                            <div class="mb-3">
                                                <label for="direccion1Contacto" class="form-label">Línea 1 <span class="text-danger">*</span></label>
                                                <input type="text" id="direccion1Contacto" class="form-control" disabled>
                                            </div>
                                            <div class="mb-3">
                                                <label for="direccion2Contacto" class="form-label">Línea 2 <span class="text-danger">*</span></label>
                                                <input type="text" id="direccion2Contacto" class="form-control" disabled>
                                            </div>
                                            <div class="mb-3">
                                                <label for="codigoContacto" class="form-label">Código Postal <span class="text-danger">*</span></label>
                                                <input type="text" id="codigoContacto" class="form-control" disabled>
                                            </div>
                                            <div class="mb-3">
                                                <label for="estadoContacto" class="form-label">Estado <span class="text-danger">*</span></label>
                                                <select id="estadoContacto" class="form-select" disabled>
                                                    <option selected disabled>Selecciona un estado</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="municipioContacto" class="form-label">Municipio <span class="text-danger">*</span></label>
                                                <select id="municipioContacto" class="form-select" disabled>
                                                    <option selected disabled>Selecciona un municipio</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <br>
                                    <div class="mb-4">
                                        <label class="form-label">Productos:</label>
                                        <div class="table-data">
                                            <div class="order">
                                                <div class="head">
                                                    <table
                                                        class="table table-hover table-striped text-center align-middle">
                                                        <thead class="table-dark">
                                                            <tr>
                                                                <th scope="col">Clave</th>
                                                                <th scope="col">Descripción</th>
                                                                <th scope="col">Cantidad</th>
                                                                <th scope="col">Lote</th>
                                                                <th scope="col">Acciones</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="detalleProductos">
                                                            <!-- Los productos se generarán aquí dinámicamente -->
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

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
                <!-- Modal Pedido -->
                <div class="modal fade" id="modalPedido" tabindex="-1" aria-labelledby="modalDetallesLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title" id="modalDetallesLabel">Detalles del Pedido</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Cerrar"></button>
                            </div>
                            <div class="modal-body">
                                <form id="formPedido">
                                    <!-- Campo oculto para el ID -->
                                    <input type="hidden" id="detalleIdPedido">
                                    <input type="hidden" id="noEmpresa">
                                    <input type="hidden" id="claveSae">
                                    <input type="hidden" id="vendedor">
                                    <input type="hidden" name="csrf_tokenP" id="csrf_tokenP" value="<?php echo $csrf_token; ?>">

                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <label for="folio" class="form-label">No. Pedido:</label>
                                            <input type="text" id="folio" class="form-control form-control-sm"
                                                style="text-align: center; vertical-align: middle;" readonly>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label for="nombreCliente" class="form-label">Nombre
                                                Cliente:</label>
                                            <input type="text" id="nombreCliente"
                                                style="text-align: center; vertical-align: middle;" class="form-control form-control-sm" readonly>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <label for="status" class="form-label">Status:</label>
                                            <input type="text" id="status" class="form-control form-control-sm"
                                                style="text-align: center; vertical-align: middle;" readonly>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label for="diaAlta" class="form-label">Dia Alta:</label>
                                            <input type="text" id="diaAlta" class="form-control form-control-sm"
                                                style="text-align: center; vertical-align: middle;" readonly>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="mb-4">
                                        <label class="form-label">
                                            <h5>Partidas:</h5>
                                        </label>
                                        <div class="table-data">
                                            <div class="order">
                                                <div class="head">
                                                    <table
                                                        class="table table-hover table-striped text-center align-middle">
                                                        <thead class="table-dark">
                                                            <tr>
                                                                <th scope="col">Clave</th>
                                                                <th scope="col">Descripción</th>
                                                                <th scope="col">Cantidad</th>
                                                                <th scope="col">subtotal</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="detallePartidas">
                                                            <!-- Los productos se generarán aquí dinámicamente -->
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Salir</button>
                                <button type="button" class="btn btn-success" id="btnAutorizar">Autorizar</button>
                                <button type="button" class="btn btn-danger" id="btnRechazar">Rechazar</button>
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
    <script>
        $(document).ready(function() {
            const tipoUsuario = '<?php echo $tipoUsuario; ?>';
            cargarComandas(tipoUsuario);
            cargarPedidos();
            // Escuchar el cambio en el filtro
            $("#filtroStatus").change(function() {
                cargarComandas(tipoUsuario); // Recargar las comandas con el filtro aplicado
            });
            /****/
            $("#btnTerminar").click(function() {
                const comandaId = $("#detalleIdComanda").val();
                const numGuia = $("#numGuia").val().trim(); // Obtener y limpiar espacios en la guía
                const token = $("#csrf_token_C").val().trim();
                // Validar que el Número de Guía no esté vacío y tenga exactamente 9 dígitos
                if (numGuia === "" || !/^\d{9}$/.test(numGuia)) {
                    Swal.fire({
                        text: "El Número de Guía debe contener exactamente 9 dígitos.",
                        icon: "warning",
                    });
                    return; // Detener el proceso si la validación falla
                }
                const horaActual = new Date().getHours(); // Obtener la hora actual en formato 24h
                const enviarHoy = horaActual < 15; // Antes de las 3 PM
                $.post(
                    "../Servidor/PHP/mensajes.php", {
                        numFuncion: "3",
                        comandaId: comandaId,
                        numGuia: numGuia,
                        enviarHoy: enviarHoy,
                        token: token,
                    },
                    function(response) {
                        if (response.success) {
                            Swal.fire({
                                text: enviarHoy ?
                                    "La comanda se ha marcado como TERMINADA y se enviará hoy." : "La comanda se ha marcado como TERMINADA y se enviará mañana.",
                                icon: "success",
                            });
                            $("#modalDetalles").modal("hide");
                            cargarComandas(tipoUsuario); // Recargar la tabla
                        } else {
                            Swal.fire({
                                text: "Error al marcar la comanda como TERMINADA.",
                                icon: "error",
                            });
                        }
                    },
                    "json"
                );
            });
        });

        function activarComanda(id) {
            $.get(
                "../Servidor/PHP/mensajes.php", {
                    numFuncion: "9",
                    comandaId: id
                },
                function(response) {
                    if (!response.success) {
                        console.error("Error en la solicitud:", response.message);
                        return;
                    } else {
                        Swal.fire({
                            text: "El pedido fue autorizado",
                            icon: "success",
                        }).then(() => {
                            $("#modalPedido").modal("hide");
                            //cargarComandas(tipoUsuario); // Recargar la tabla
                            //window.location.reload();
                        });
                    }
                },
                "json"
            ).fail((jqXHR, textStatus, errorThrown) => {
                console.error("Error en la solicitud:", textStatus, errorThrown);
                console.log("Detalles:", jqXHR.responseText);
            });
        }
    </script>
</body>

</html>