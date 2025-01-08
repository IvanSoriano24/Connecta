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
        $claveVendedor = $_SESSION['empresa']['claveVendedor'];
    }
} else {
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

    <title>Alta Pedidos</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>


</head>
<style>
    .suggestions-list {
        list-style: none;
        margin: 0;
        padding: 0;
        border: 1px solid #ccc;
        border-top: none;
        background-color: white;
        position: absolute;
        width: 100%;
        /* Asegura que ocupe el mismo ancho que el input */
        max-height: 200px;
        /* Para limitar la altura */
        overflow-y: auto;
        /* Añade scroll si hay muchas sugerencias */
        z-index: 1000;
        display: none;
        /* Se oculta inicialmente */
        box-sizing: border-box;
        /* Asegura que el padding no afecte el ancho */
    }

    .suggestions-list li {
        padding: 8px;
        cursor: pointer;
    }

    .suggestions-list li:hover {
        background-color: #f0f0f0;
    }
</style>

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
                                        <option value="Directo">Directo</option>
                                    </select>
                                    <label for="numero">Número</label>
                                    <input class="input-mt" type="text" name="numero" id="numero" readonly> <!--Folio-->
                                    <label for="fecha">Fecha </label>
                                    <input class="input-mt" type="date" name="diaAlta" id="diaAlta">
                                    <label for="cliente">Cliente </label>
                                    <input class="input-mt" name="cliente" id="cliente" autocomplete="" />
                                    <ul id="clientesSugeridos" class="suggestions-list"></ul>
                                </div>
                                <div class="form-row">
                                    <label for="rfc">RFC <a class='bx'> *</a></label>
                                    <input class="input-mt" type="text" name="rfc" id="rfc">
                                    <label for="nombre">Nombre <a class='bx'> *</a></label>
                                    <input class="input-larg" type="text" name="nombre" id="nombre">
                                    <label for="nombre">Su Pedido </label>
                                    <input class="input-mt" type="text" name="nombre" id="nombre">
                                </div>
                                <div class="form-row">
                                    <label for="calle">Calle </label>
                                    <input class="input-larg" type="text" name="calle" id="calle"
                                        style="background-color: #e0e0e0; margin-left: 10px;" value="" readonly>
                                    <label for="numE">Num. ext. </label>
                                    <input class="input-small" type="text" name="numE" id="numE"
                                        style="background-color: #e0e0e0; margin-left: 10px;" value="" readonly>
                                    <label for="descuento">Esquema </label>
                                    <input class="input-mt" type="text" name="descuento" id="descuento">
                                </div>
                                <div class="form-row">
                                    <label for="colonia">Colonia:</label>
                                    <input class="input-larg" type="text" name="colonia" id="colonia"
                                        style="background-color: #e0e0e0; margin-left: 10px;" value="" readonly>
                                    <label for="numI">Num. Int.</label>
                                    <input class="input-small" type="text" name="numI" id="numI"
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
                                    <input class="input-mt" type="text" name="regimenFiscal" id="regimenFiscal"
                                        style="background-color: #e0e0e0; margin-left: 10px;" value="" readonly>
                                    <label for="entrega">Entrega </label>
                                    <input class="input-mt" type="date" name="entrega" id="entrega">
                                    <label for="vendedor">Vendedor </label>
                                    <input class="input-mt" type="text" name="vendedor" id="vendedor">
                                </div>
                                <div class="form-row">
                                    <label for="condicion">Condicion </label>
                                    <input class="input-larg" type="text" name="condicion" id="condicion">
                                    <label for="comision">Comision </label>
                                    <input class="input-mt" type="text" name="comision" id="comision">
                                </div>
                                <div class="form-row">
                                    <label for="enviar">Enviar a </label>
                                    <input class="input-larg" type="text" name="enviar" id="enviar">
                                    <label for="almacen">Almacen </label>
                                    <input class="input-mt" type="text" name="almacen" id="almacen"
                                        style="background-color: #e0e0e0; margin-left: 10px;" value="1" readonly>
                                </div>
                                <div class="form-row">
                                    <label for="destinatario">Destinatario </label>
                                    <input class="input-larg" type="text" name="destinatario" id="destinatario"
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
    <script src="JS/menu.js"></script>
    <script src="JS/app.js"></script>
    <script src="JS/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/ventas.js"></script>
    <script>
        $(document).ready(function() {
            $('#cliente').on('input', function() {
                var cliente = $(this).val();
                var clave = '<?php echo $claveVendedor ?>';
                var $clienteInput = $(this);

                // Si el texto ingresado tiene más de 2 caracteres
                if (cliente.length > 2) {
                    $.ajax({
                        url: '../Servidor/PHP/ventas.php',
                        type: 'POST',
                        data: {
                            cliente: cliente,
                            numFuncion: '4',
                            clave: clave
                        },
                        success: function(response) {
                            console.log(response);
                            try {
                                if (typeof response === 'string') {
                                    response = JSON.parse(response);
                                }
                            } catch (e) {
                                console.error("Error al parsear la respuesta JSON", e);
                            }

                            if (response.success && Array.isArray(response.cliente) && response.cliente.length > 0) {
                                var suggestions = response.cliente.map(function(cliente) {
                                    return cliente.NOMBRE;
                                });

                                // Mostrar las sugerencias debajo del input
                                var suggestionsList = $('#clientesSugeridos');
                                suggestionsList.empty().show();

                                suggestions.forEach(function(suggestion) {
                                    var listItem = $('<li></li>')
                                        .text(suggestion)
                                        .on('click', function() {
                                            $clienteInput.val(suggestion);
                                            suggestionsList.empty().hide();
                                        });

                                    suggestionsList.append(listItem);
                                });
                            } else {
                                $('#clientesSugeridos').empty().hide();
                            }
                        }
                    });
                } else {
                    $('#clientesSugeridos').empty().hide();
                }
            });

            // Cerrar la lista de sugerencias si se hace clic fuera del input
            $(document).on('click', function(event) {
                if (!$(event.target).closest('#cliente').length) {
                    $('#clientesSugeridos').empty().hide();
                }
            });
        });
    </script>
</body>

</html>