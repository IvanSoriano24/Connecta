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

    $mostrarModal = isset($_SESSION['empresa']) ? false : true;

    //$empresa = $_SESSION['empresa']['razonSocial'];
    if (isset($_SESSION['empresa'])) {
        $empresa = $_SESSION['empresa']['razonSocial'];
        $idEmpresa = $_SESSION['empresa']['id'];
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
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

    <title>Alta Pedidos</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


    <style>
        body.modal-open .hero_area {
            filter: blur(5px);
            /* Difumina el fondo mientras un modal está abierto */
        }
    </style>

</head>

<body>
    <!-- SIDEBAR -->
    <?php include 'sidebar.php'; ?>
    <div class="hero_area">
        <section id="content">
            <!-- MAIN -->
            <main class="text-center">

                <diV class="head-title">
                    <div class="left">
                        <h1>Alta de Pedidos</h1>
                        <ul class="breadcrumb">
                            <li>
                                <a href="#">Inicio</a>
                            </li>

                            <li><i class='bx bx-chevron-right'></i></li>
                            <li>
                                <a class="" href="Ventas.php">Ventas</a>
                            </li>
                            <li><i class='bx bx-chevron-right'></i></li>
                            <li>
                                <a class="" href="altaPedido.php">Alta Pedidos</a>
                            </li>
                        </ul>
                    </div>

                    <div class="table-data">
                        <div class="order">
                            <div class="head">
                                <h3>Documento</h3>
                                <a class=''>Campos Obligatorios *</a>
                            </div>

                            <form onsubmit="return validateForm()">
                                <!-- Primera fila -->
                                <div class="form-row">
                                    <label for="factura">Pedido: </label>
                                    <select class="input-mt" name="factura" id="factura">
                                        <option>Directo</option>
                                    </select>

                                    <label for="numero">Número</label>
                                    <input class="input-mt" type="text" name="numero" id="numero">

                                    <label for="fecha">Fecha </label>
                                    <input class="input-mt" type="date" name="diaAlta" id="diaAlta">

                                    <label for="cliente">Cliente </label>
                                    <input class="input-mt" name="cliente" id="cliente" autocomplete="">
                                </div>

                                <div class="form-row">
                                    <label for="rfc">RFC <a class='bx'> *</a></label>
                                    <input class="input-mt" type="text" name="rfc" id="rfc">

                                    <label for="nombre">Nombre <a class='bx'> *</a></label>
                                    <input class="input-mt input-largo" type="text" name="nombre" id="nombre">

                                    <label for="nombre">Su Pedido </label>
                                    <input class="input-mt" type="text" name="nombre" id="nombre">

                                </div>

                                <div class="form-row">
                                    <label for="calle">Calle </label>
                                    <input class="input-mt" type="text" name="calle" id="calle"
                                        style="background-color: #e0e0e0; margin-left: 10px;" value="" readonly>

                                    <label for="numE">Num. ext. </label>
                                    <input class="input-mt" type="text" name="numE" id="numE"
                                        style="background-color: #e0e0e0; margin-left: 10px;" value="" readonly>

                                    <label for="descuento">Esquema </label>
                                    <input class="input-mt" type="text" name="descuento" id="descuento">
                                </div>

                                <div class="form-row">
                                    <label for="colonia">Colonia:</label>
                                    <input class="input-mt" type="text" name="colonia" id="colonia"
                                        style="background-color: #e0e0e0; margin-left: 10px;" value="" readonly>

                                    <label for="numI">Num. Int.</label>
                                    <input class="input-mt" type="text" name="numI" id="numI"
                                        style="background-color: #e0e0e0; margin-left: 10px;" value="" readonly>

                                    <label for="descuento">Descuento </label>
                                    <input class="input-mt" type="text" name="descuento" id="descuento">

                                </div>

                                <div class="form-row">

                                    <label for="codigoPostal">Código Postal:<a class='bx'>*</a></label>
                                    <input class="input-mt" type="text" name="codigoPostal" id="codigoPostal"
                                        style="background-color: #e0e0e0; margin-left: 10px;" value="" readonly>

                                    <label for="poblacion">Población:</label>
                                    <input class="input-mt" type="text" name="poblacion" id="poblacion"
                                        style="background-color: #e0e0e0; margin-left: 10px;" value="" readonly>

                                    <label for="pais">Pais: <a class='bx'>*</a></label>
                                    <input class="input-mt" type="text" name="pais" id="pais"
                                        style="background-color: #e0e0e0; margin-left: 10px;" value="" readonly>

                                    <label for="descuentofin">Descuento Fin </label>
                                    <input class="input-mt" type="text" name="descuentofin" id="descuentofin">
                                </div>

                                <div class="form-row">
                                    <label for="regimenFiscal">Régimen Fiscal: <a class='bx'> *</a></label>
                                    <input class="input-m" type="text" name="regimenFiscal" id="regimenFiscal"
                                        style="background-color: #e0e0e0; margin-left: 10px;" value="" readonly>

                                    <label for="entrega">Entrega </label>
                                    <input class="input-mt" type="date" name="entrega" id="entrega">

                                    <label for="vendedor">Vendedor </label>
                                    <input class="input-mt" type="text" name="vendedor" id="vendedor">

                                </div>

                                <div class="form-row">
                                    <label for="condicion">Condicion </label>
                                    <input class="input-mt" type="text" name="condicion" id="condicion">

                                    <label for="comision">Comision </label>
                                    <input class="input-mt" type="text" name="comision" id="comision">
                                </div>

                                <div class="form-row">
                                    <label for="enviar">Enviar a </label>
                                    <input class="input-mt" type="text" name="enviar" id="enviar">

                                    <label for="almacen">Almacen </label>
                                    <input class="input-mt" type="text" name="almacen" id="almacen"
                                        style="background-color: #e0e0e0; margin-left: 10px;" value="1" readonly>

                                </div>

                                <div class="form-row">
                                    <label for="destinatario">Destinatario </label>
                                    <input class="input-mt" type="text" name="destinatario" id="destinatario"
                                        style="background-color: #e0e0e0; margin-left: 10px;" value="" readonly>
                                </div>


                                <!-- Sección de botones -->
                                <div class="form-buttons">
                                    <button type="submit" class="btn-save" id="guardarFactura">Guardar</button>
                                    <button type="button" class="btn-cancel">Cancelar</button>
                                </div>
                            </form>

                        </div>
                    </div>
            </main>
            <!-- MAIN -->
            <!-- CONTENT -->
        </section>
    </div>

    <!-- CONTENT -->
    </div>
    <div class="modal fade" id="empresaModal" tabindex="-1" aria-labelledby="empresaModalLabel" aria-hidden="true"
        data-bs-backdrop="static" data-bs-keyboard="false" class="modal <?php echo $mostrarModal ? '' : 'd-none'; ?>">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="text-center mb-4">
                    <h1 class="display-4 text-primary txth1">Bienvenido</h1>
                    <h2 class="card-title text-center txth2"> <?php echo $tipoUsuario; ?> </h2>
                </div>
                <div class="modal-body">
                    <select class="form-select" id="empresaSelect" name="empresaSelect">
                        <option value="" selected disabled class="txt">Selecciona una Empresa</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary txt" id="confirmarEmpresa"> Confirmar</button>
                </div>
            </div>
        </div>


        <!-- JS Para la confirmacion empresa -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            document.getElementById('empresaModal').addEventListener('shown.bs.modal', function () {
                var usuario = '<?php echo $nombreUsuario; ?>';
                cargarEmpresa(usuario);
            });
            document.addEventListener('DOMContentLoaded', function () {
                const empresaSeleccionada = <?php echo json_encode(isset($_SESSION['empresa']) ? $_SESSION['empresa'] : null); ?>;
                if (empresaSeleccionada === null) {
                    const empresaModal = new bootstrap.Modal(document.getElementById('empresaModal'));
                    empresaModal.show();
                }
            });

            document.getElementById('confirmarEmpresa').addEventListener('click', function () {
                const empresaSeleccionada = document.getElementById('empresaSelect').value;
                if (empresaSeleccionada != null) {

                    const empresaOption = document.querySelector(`#empresaSelect option[value="${empresaSeleccionada}"]`);

                    // Verificar que empresaOption no sea null
                    if (empresaOption) {
                        // Obtener los datos adicionales de la empresa utilizando los atributos data-*
                        const noEmpresa = empresaOption.getAttribute('data-no-empresa');
                        const razonSocial = empresaOption.getAttribute('data-razon-social');

                        // Usar SweetAlert en lugar de alert
                        Swal.fire({
                            title: 'Has seleccionado:',
                            text: `${noEmpresa} - ${razonSocial}`,
                            icon: 'success'
                        }).then(() => {
                            seleccionarEmpresa(noEmpresa);
                            const modal = bootstrap.Modal.getInstance(document.getElementById('empresaModal'));
                            modal.hide();

                            // Guardar los datos en la variable global
                            idEmpresarial = {
                                id: empresaSeleccionada,
                                noEmpresa: noEmpresa,
                                razonSocial: razonSocial
                            };
                            sesionEmpresa(idEmpresarial);
                        });
                    }
                } else {
                    // Usar SweetAlert en lugar de alert
                    Swal.fire({
                        title: 'Error',
                        text: 'Por favor, selecciona una empresa.',
                        icon: 'error'
                    });
                }
            });

        </script>
        <script src="JS/menu.js"></script>
        <script src="JS/app.js"></script>
        <script src="JS/script.js"></script>
</body>

</html>