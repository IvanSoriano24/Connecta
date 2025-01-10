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
    .input-container {
        position: relative;
        width: 100%;
    }

    .lista-sugerencias {
        max-height: 150px;
        overflow-y: auto;
        z-index: 1000;
    }

    .suggestion-item:hover {
        background-color: #f0f0f0;
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

    .suggestions-list li {
        padding: 8px;
        cursor: pointer;
    }

    .suggestions-list li:hover {
        background-color: #f0f0f0;
    }

    .suggestions-list li:hover {
        background-color: #f0f0f0;
    }

    .clear-input {
        position: absolute;
        top: 50%;
        right: 10px;
        transform: translateY(-50%);
        font-size: 16px;
        color: #888;
        pointer-events: all;
        display: none;
        /* Escondemos la X por defecto */
    }

    .clear-input {
        position: absolute;
        top: 50%;
        right: 10px;
        transform: translateY(-50%);
        font-size: 16px;
        color: #888;
        pointer-events: all;
        display: none;
        /* Escondemos la X por defecto */
    }

    .clear-input:hover {
        color: #333;
    }

    .clear-input:hover {
        color: #333;
    }

    /* */
    /* General styling for input fields */
    input {
        padding: 5px;
        font-size: 12px;
        /* Reduce font size */
        height: 30px;
        /* Make inputs smaller */
    }

    /* Specific input field adjustments for smaller size */
    .cantidad,
    .ieps,
    .subtotalPartida,
    .iva {
        width: 60px;
        /* Narrow width */
    }

    .unidad,
    .subtotalPartida,
    .comision,
    .precioUnidad {
        width: 80px;
        /* Slightly wider for these fields */
    }

    /* Styling for the "producto" field to keep it normal size */
    .producto {
        width: 150px;
        /* Default size for the product field */
    }

    /* Styling for the suggestion list to ensure it looks correct */
    .lista-sugerencias {
        max-height: 150px;
        overflow-y: auto;
        z-index: 1000;
    }

    .suggestion-item:hover {
        background-color: #f0f0f0;
    }

    .suggestions-list li {
        padding: 8px;
        cursor: pointer;
    }
</style>

<body>
    <!-- <div class=""> -->
    <!-- SIDEBAR -->
    <?php include 'sidebar.php'; ?>
    <div class="hero_area">
        <section id="content">
            <!-- NAVBAR -->
            <?php include 'navbar.php'; ?>
            <!-- MAIN -->
            <main class="text-center">

                <div class="head-title">
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

                            <form>
                                <!-- Primera fila -->
                                <div class="form-row">
                                    <label for="factura">Pedido: </label>
                                    <select class="input-mt" name="factura" id="factura">
                                        <option value="Directo">Directo</option>
                                    </select>
                                    <label for="numero">Número</label>
                                    <input class="input-mt" type="text" name="numero" id="numero" readonly>
                                    <!--Folio-->
                                    <label for="fecha">Fecha </label>
                                    <input class="input-mt" type="date" name="diaAlta" id="diaAlta">
                                    <label for="cliente">Cliente </label>
                                    <div class="input-container" style="position: relative;">
                                        <input class="input-mt" name="cliente" id="cliente" autocomplete="" />
                                        <span id="clearInput" class="clear-input"
                                            style="cursor: pointer; display: none;">&#10005;</span>
                                        <ul id="clientesSugeridos" class="suggestions-list"></ul>
                                    </div>

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
                                    <button type="submit" class="btn-save" id="guardarPedido">Guardar</button>
                                    <button type="button" class="btn-cancel" id="cancelarPedido">Cancelar</button>
                                </div>
                                <input class="input-mt" type="text" name="listaPrecios" id="listaPrecios" readonly hidden>
                                <input class="input-mt" type="text" name="CVE_ESQIMPU" id="CVE_ESQIMPU" readonly hidden>
                                <div id="divProductos">
                                    <table id="tablaProductos" name="tablaProductos" class="tabla-productos">
                                        <thead>
                                            <tr>
                                                <th>Cant.</th>
                                                <th>Producto</th>
                                                <th>Unidad</th>
                                                <th>Desc.1</th>
                                                <th>Desc.2</th>
                                                <th>I.E.P.S</th>
                                                <th>I.V.A</th>
                                                <th>Comision</th>
                                                <th>Prec.Unit</th>
                                                <th>Subtotal por Partida</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Aquí se agregarán dinámicamente las filas de las partidas -->
                                        </tbody>
                                    </table>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
            <!-- MAIN -->
            <!-- CONTENT -->
        </section>
    </div>
    <!-- CONTENT -->
    <!-- Modal Productos -->
    <div id="modalProductos" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content" style="max-height: 90vh; overflow-y: auto;">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h5 class="modal-title">Buscar Producto</h5>
                    <button type="button" class="btn-close" onclick="cerrarModal()"></button>
                </div>
                <!-- Filtro Estático -->
                <div class="modal-static">
                    <div class="form-group row">
                        <div class="col-4">
                            <label for="filtroCriterio" class="form-label">Filtrar por:</label>
                            <select id="filtroCriterio" class="form-control">
                                <option value="CVE_ART">Clave</option>
                                <option value="DESCR">Descripción</option>
                            </select>
                        </div>
                        <div class="col-8">
                            <label for="campoBusqueda" class="form-label">Buscar:</label>
                            <input type="search" id="campoBusqueda" class="form-control" placeholder="Escribe aquí...">
                        </div>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="modal-body">
                    <!-- Lista de Productos -->
                    <div id="listaProductos">
                        <table id="tablalistaProductos" name="tablalistaProductos" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Clave</th>
                                    <th>Descripción</th>
                                    <th>Existencias</th>
                                </tr>
                            </thead>
                            <tbody>
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



    <script src="JS/menu.js"></script>
    <script src="JS/app.js"></script>
    <script src="JS/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/ventas.js"></script>
    <script src="JS/altaPedido.js"></script>
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

                                suggestions.forEach(function(suggestion, index) {
                                    var listItem = $('<li></li>')
                                        .text(suggestion)
                                        .on('click', function() {
                                            // Al seleccionar un cliente, llenar los campos del formulario
                                            $clienteInput.val(suggestion);
                                            suggestionsList.empty().hide();

                                            // Llenar otros campos con la información del cliente seleccionado
                                            var selectedClient = response.cliente[index];
                                            $('#rfc').val(selectedClient.RFC);
                                            $('#nombre').val(selectedClient.NOMBRE);
                                            $('#calle').val(selectedClient.CALLE);
                                            $('#numE').val(selectedClient.NUMEXT);
                                            $('#numI').val(selectedClient.NUMINT);
                                            $('#colonia').val(selectedClient.COLONIA);
                                            $('#codigoPostal').val(selectedClient.CODIGO);
                                            $('#poblacion').val(selectedClient.LOCALIDAD);
                                            $('#pais').val(selectedClient.PAIS);
                                            $('#regimenFiscal').val(selectedClient.REGIMEN_FISCAL);
                                            $('#cliente').val(selectedClient.CLAVE);
                                            $('#listaPrecios').val(selectedClient.LISTA_PREC);
                                            //$('#destinatario').val(selectedClient.DESTINATARIO);
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

            // Al hacer clic en la X, borrar el valor del input y los demás campos
            $('#clearInput').on('click', function() {
                $('#cliente').val(''); // Borra el valor del input
                $('#rfc').val(''); // Borra el valor de RFC
                $('#nombre').val(''); // Borra el valor de nombre
                $('#calle').val(''); // Borra el valor de calle
                $('#numE').val(''); // Borra el valor de número externo
                $('#colonia').val(''); // Borra el valor de colonia
                $('#codigoPostal').val(''); // Borra el valor de código postal
                $('#poblacion').val(''); // Borra el valor de población
                $('#pais').val(''); // Borra el valor de país
                $('#regimenFiscal').val(''); // Borra el valor de régimen fiscal
                $('#destinatario').val(''); // Borra el valor de destinatario
                $('#clientesSugeridos').empty().hide(); // Oculta las sugerencias
                $(this).hide(); // Oculta la X
            });
        });
    </script>


</body>

</html>