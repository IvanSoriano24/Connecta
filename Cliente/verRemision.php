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

    $mostrarModal = isset($_SESSION['empresa']) ? false : true;

    //$empresa = $_SESSION['empresa']['razonSocial'];
    if (isset($_SESSION['empresa'])) {
        $empresa = $_SESSION['empresa']['razonSocial'];
        $idEmpresa = $_SESSION['empresa']['id'];
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveUsuario = $_SESSION['empresa']['claveUsuario'] ?? null;
        $claveSae = $_SESSION['empresa']['claveSae'] ?? null;
        $contrasena = $_SESSION['empresa']['contrasena'] ?? null;
    }

    $csrf_token  = $_SESSION['csrf_token'];
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

    <title>MDConnecta</title>
    <link rel="icon" href="SRC/logoMDConecta.png" />
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

    .suggestions-list li {
        padding: 8px;
        cursor: pointer;
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
    }

    .clear-input:hover {
        color: #333;
    }

    input {
        padding: 5px;
        font-size: 12px;
        height: 30px;
    }

    .cantidad,
    .ieps,
    .subtotalPartida,
    .iva {
        width: 60px;
    }

    .unidad,
    .comision,
    .precioUnidad {
        width: 80px;
    }

    .descuento {
        width: 50px;
    }

    .subtotalPartida {
        width: 90px;
    }

    .producto {
        width: 150px;
    }

    .card-body {
        width: 100%;
        max-width: 980px;
        margin: 0 auto;
        padding: 20px;
        border: 1px solid #ccc;
        border-radius: 5px;
        background-color: #fff;
    }

    .form-container {
        position: sticky;
        /* Cambiado a sticky para mantener fijo */
        /*top: 0;*/
        /* Para que se fije en la parte superior */
        z-index: 10;
        background: white;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
    }

    .form-container .row {
        width: 100%;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 15px;
    }

    .form-element {
        flex-grow: 1;
        flex-basis: calc(20% - 10px);
        margin: 0;
        padding: 0;
    }

    label {
        display: block;
        font-size: 14px;
        margin-bottom: 5px;
    }

    input,
    select,
    textarea {
        padding: 5px;
        border: 1px solid #ccc;
        border-radius: 3px;
        font-size: 14px;
        box-sizing: border-box;
    }

    textarea {
        height: 6em;
        resize: none;
    }

    .button-container {
        text-align: center;
        margin-top: 20px;
    }

    button {
        background-color: #007bff;
        color: #fff;
        border: none;
        padding: 10px 20px;
        font-size: 14px;
        cursor: pointer;
        border-radius: 3px;
        margin: 0 10px;
    }




    @media (max-width: 671px) {
        #CodigoGuarnicion .form-element:nth-child(1) {
            flex-grow: 1;
        }

        #CodigoGuarnicion .form-element:nth-child(2) {
            flex-grow: 1;
        }

        #CodigoGuarnicion .form-element:nth-child(2) input {
            width: auto;
        }
    }


    /* Contenedor principal de la tabla */
    .table-container {
        flex: 1;
        /* Se expande para ocupar el espacio restante */
        overflow: hidden;
        /* Evita el scroll en este contenedor */
    }

    /* Wrapper de la tabla para activar el scroll interno */
    .table-wrapper {
        height: 100%;
        overflow-y: auto;
        /* Activa el scroll interno vertical */

    }

    /* Opcional: Mejora visual del scroll interno */
    .table-wrapper::-webkit-scrollbar {
        width: 8px;
    }

    .table-wrapper::-webkit-scrollbar-thumb {
        background-color: #ccc;
        border-radius: 4px;
    }

    .table-wrapper::-webkit-scrollbar-thumb:hover {
        background-color: #aaa;
    }

    .input-container div {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        /* Alinear elementos al inicio */
        gap: 5px;
        /* Espacio uniforme entre elementos */
    }

    .input-container input {
        flex-shrink: 0;
        /* Evitar que el input cambie de tamaño */
    }

    .input-container button {
        flex-shrink: 0;
        /* Evitar que los botones cambien de tamaño */
        padding: 5px 8px;
        /* Ajustar el tamaño del botón */
    }

    .input-container {
        width: auto;
        /* Asegurar que el contenedor no se expanda innecesariamente */
    }

    #clientesSugeridos li {
        padding: 5px;
        cursor: pointer;
    }

    #clientesSugeridos li.highlighted {
        background-color: #007bff;
        color: white;
    }

    /**********************************************************/
    /* Asegurar que la lista de sugerencias esté posicionada debajo del input */
    .suggestions-list-productos {
        position: absolute;
        top: 80%;
        /* La lista aparece justo debajo del input */
        left: 0;
        width: 180%;
        /* Se ajusta al ancho del input */
        background: white;
        border: 1px solid #ccc;
        max-height: 200px;
        /* Altura máxima para evitar que cubra todo */
        overflow-y: auto;
        /* Habilita el scroll si hay muchas sugerencias */
        z-index: 1000;
        /* Asegura que esté por encima de otros elementos */
        box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        /* Sombra para mejor visualización */
        border-radius: 5px;
    }

    /* Diseño para cada ítem en la lista */
    .suggestions-list-productos li {
        padding: 5px;
        cursor: pointer;
        list-style: none;
        /* Elimina los estilos de lista */
    }

    /* Resaltar la opción seleccionada */
    .suggestions-list-productos li.highlighted {
        background-color: #007bff;
        color: white;
    }

    /*-------------------------------------------------------*/
</style>
<style>
    .tabla-scroll {
        height: 250px;
        /* Altura fija para el área del scroll */
        overflow-y: auto;
        /* Activar scroll vertical */
    }

    .tabla-productos {
        /*border-collapse: collapse;*/
        width: 100%;
    }

    .tabla-productos thead {
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
<!-- <div class=""> -->
<!-- SIDEBAR -->
<?php include 'sidebar.php'; ?>
<div class="hero_area">
    <section id="content">
        <!-- NAVBAR -->
        <?php include 'navbar.php'; ?>
        <!-- MAIN -->
        <main class="">

            <div class="head-title">
                <!-- <div class="left">
                    <h4>Alta de Pedidos</h4>
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
                            <a class="" href="verPedido.php">Alta Pedidos</a>
                        </li>
                    </ul>
                     -->


            </div>

            <div class="card-body ">
                <form class="form-container" id="formularioPedido">
                    <!-- 1st row: 4 inputs (2 select, 2 text) -->
                    <div class="row">
                        <div class="form-element">
                            <label for="factura">Pedido: </label>
                            <select name="factura" id="factura" style="width: 170px;" readonly>
                                <option value="Directo">Directo</option>
                            </select>
                        </div>
                        <div class="form-element">
                            <label for="numero">Folio:</label>
                            <input type="text" name="numero" id="numero" readonly tabindex="-1">
                        </div>
                        <div class="form-element">
                            <label for="fecha">Fecha </label>
                            <input type="date" name="diaAlta" id="diaAlta" style="width:180px; align-items: center;"
                                   readonly1 tabindex="-1">
                        </div>

                        <div class="form-element">
                            <input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo $csrf_token; ?>">
                            <label for="cliente">Cliente</label>
                            <div class="input-container" style="position: relative;">
                                <div style="display: flex; align-items: center; gap: 5px;">
                                    <input name="cliente" id="cliente" autocomplete="off" readonly style="padding-right: 2rem; width: 170px;" />
                                </div>
                                <ul id="clientesSugeridos" class="suggestions-list"></ul>
                            </div>
                        </div>

                    </div>
                    <div class="row">

                        <div class="form-element" style="display: none;">
                            <label for="rfc">RFC </label>
                            <input type="text" name="rfc" id="rfc" tabindex="-1">
                        </div>

                        <div class="form-element">
                            <label for="nombre">Nombre </label>
                            <input type="text" name="nombre" id="nombre" style="width: 700px;" tabindex="-1" readonly />
                        </div>

                        <div class="form-element">
                            <label for="supedido">O.C</label>
                            <input type="text" name="supedido" id="supedido" style="width:170px;" readonly>
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-element" style="display: none;">
                            <label for="calle">Calle </label>
                            <input type="text" name="calle" id="calle" style="background-color: #e0e0e0; " value=""
                                   readonly>
                        </div>
                        <div class="form-element" style="display: none;">
                            <label for="numE">Num. ext. </label>
                            <input type="text" name="numE" id="numE" style="background-color: #e0e0e0; " value=""
                                   readonly>
                        </div>
                        <div class="form-element" style="display: none;">
                            <label for="numI">Num. Int.</label>
                            <input type="text" name="numI" id="numI" style="background-color: #e0e0e0; " value=""
                                   readonly>
                        </div>
                        <div class="form-element">
                            <label for="vendedor">Vendedor </label>
                            <input type="text" name="vendedor" id="vendedor" style="width: 180px;"
                                   value="<?php echo $claveUsuario ?>" tabindex="-1" readonly>
                        </div>
                        <div class="form-element">
                            <label for="almacen">Almacen </label>
                            <input type="text" name="almacen" id="almacen" style="background-color: #e0e0e0; width: 180px;"
                                   value="1" tabindex="-1" readonly>
                        </div>
                        <div class="form-element">
                            <label for="entrega">Entrega </label>
                            <input type="date" name="entrega" id="entrega"
                                   style="width:180px; align-items: center;" readonly1 tabindex="-1">

                        </div>
                        <!--<div class="form-element">
                            <label for="esquema">Esquema </label>
                            <input type="text" name="esquema" id="esquema" value="0" style="width: 170px;" readonly1>
                        </div>-->
                    </div>

                    <div class="row">
                        <div class="form-element" style="display: none;">
                            <label for="colonia">Colonia:</label>
                            <input type="text" name="colonia" id="colonia"
                                   style="background-color: #e0e0e0; width: 470px;" value="" readonly>
                        </div>
                        <div class="form-element">
                            <label for="condicion">Condicion </label>
                            <div style="display: flex; align-items: center;">
                                <input type="text" name="condicion" style="width: 410px;" id="condicion" maxlength="25" readonly>
                                <button type="button" class="btn ms-2" id="AyudaCondicion" tabindex="-1">
                                    <i class="bx bx-help-circle"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-element">

                        </div>
                        <div class="form-element">
                            <label for="descuentoCliente">Descuento </label>
                            <div style="display: flex; align-items: center;">
                                <input type="text" name="descuentoCliente" id="descuentoCliente" style="width: 110px;" tabindex="-1" readonly>
                                <button type="button" class="btn ms-2" id="AyudaDescuento" tabindex="-1">
                                    <i class="bx bx-help-circle"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-element">
                            <label for="enviar">Enviar a </label>
                            <div style="display: flex; align-items: center;">
                                <input type="text" name="enviar" style="width:410px;" id="enviar" readonly>
                                <button type="button" class="btn ms-2" id="AyudaEnviarA">
                                    <i class="bx bx-help-circle"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-element" style="display: none;">
                            <label for="codigoPostal">Código Postal:<a class='bx'>*</a></label>
                            <input type="text" name="codigoPostal" id="codigoPostal"
                                   style="background-color: #e0e0e0; " value="" readonly>
                        </div>
                        <div class="form-element" style="display: none;">
                            <label for="poblacion">Población:</label>
                            <input type="text" name="poblacion" id="poblacion" style="background-color: #e0e0e0; "
                                   value="" readonly>
                        </div>

                        <div class="form-element"></div>
                    </div>
                    <div class="row" style="display: none;">
                        <div class="form-element">
                            <label for="regimenFiscal">Régimen Fiscal: </label>
                            <input type="text" name="regimenFiscal" id="regimenFiscal"
                                   style="background-color: #e0e0e0; " value="" readonly>
                        </div>

                    </div>

                    <div class="row">
                        <div class="form-element" style="display: none;">
                            <div class="form-element">
                                <label for="comision">Comision </label>
                                <input type="text" name="comision" id="comision">
                            </div>
                            <div class="form-element">
                                <label for="pais">Pais: <a class='bx'>*</a></label>
                                <input type="text" name="pais" id="pais" style="background-color: #e0e0e0; "
                                       value="" readonly>
                            </div>
                        </div>

                    </div>
                    <div class="row">
                        <div class="form-element" style="display: none;">
                            <label for="destinatario">Destinatario </label>
                            <div style="display: flex; align-items: center;">
                                <input type="text" name="destinatario" id="destinatario"
                                       style="background-color: #e0e0e0; width: 470px; " value="" readonly>
                            </div>
                        </div>
                        <input class="input-mt" type="text" name="conCredito" id="conCredito" readonly hidden>
                        <div class="form-element"></div>
                    </div>
                    <div class="row">
                        <div class="form-element"></div>
                        <button type="button" class="btn-cancel" id="cancelarPedido" tabindex="-1"
                                style="width: 150px;">Regresar</button>
                    </div>
                </form>
                <!-- 5th row: 2 buttons -->
                <div class="table-data">
                    <div class="order">
                        <div class="table-container">
                            <div class="table-wrapper">
                                <div class="tabla-scroll">
                                    <table id="tablaProductos" name="tablaProductos" class="tabla-productos">
                                        <thead>
                                        <tr>
                                            <th>Producto</th>
                                            <th>Cant.</th>
                                            <th>Unidad</th>
                                            <th>Desc.</th>
                                            <th>I.V.A</th>
                                            <th>Prec.Unit</th>
                                            <th>Subtotal por Partida</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <!-- Filas dinámicas -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <input class="input-mt" type="text" name="listaPrecios" id="listaPrecios" readonly
                                   hidden>
                            <input class="input-mt" type="text" name="CVE_ESQIMPU" id="CVE_ESQIMPU" readonly hidden>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <!-- MAIN -->
        <!-- CONTENT -->
    </section>
</div>
<!-- CONTENT -->
<script src="JS/menu.js?n=1"></script>
<script src="JS/app.js?n=1"></script>
<script src="JS/script.js?n=1"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
<script src="JS/remisiones.js?n=1"></script>
<script src="JS/verRemision.js?n=1"></script>
<!--<script src="JS/clientes.js"></script>-->
</body>
</html>