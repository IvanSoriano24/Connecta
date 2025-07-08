<?php
//Iniciar sesion
session_start();
//Validar si hay una sesion
if (isset($_SESSION['usuario'])) {
    //Si la sesion iniciada es de un Cliente, redirigir al E-Commers
    if ($_SESSION['usuario']['tipoUsuario'] == 'CLIENTE') {
        header('Location:Menu.php');
        exit();
    }
    //Obtener valores del Usuario
    $nombreUsuario = $_SESSION['usuario']["nombre"];
    $tipoUsuario = $_SESSION['usuario']["tipoUsuario"];
    $correo = $_SESSION['usuario']["correo"];

    //$mostrarModal = isset($_SESSION['empresa']) ? false : true;

    //$empresa = $_SESSION['empresa']['razonSocial'];

    //Obtener valores de la empresa
    if (isset($_SESSION['empresa'])) {
        $empresa = $_SESSION['empresa']['razonSocial'];
        $idEmpresa = $_SESSION['empresa']['id'];
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveUsuario = $_SESSION['empresa']['claveUsuario'] ?? null;
        $claveSae = $_SESSION['empresa']['claveSae'] ?? null;
        $contrasena = $_SESSION['empresa']['contrasena'] ?? null;
    }
    //Obtener token de seguridad
    $csrf_token  = $_SESSION['csrf_token'];
} else {
    //Si no hay una sesion iniciada, redirigir al index
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

    <!-- Titulo y Logo -->
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
    /*.suggestions-list-productos {
        position: absolute;
        top: 80%;
        // La lista aparece justo debajo del input 
        left: 0;
        width: 180%;
        // Se ajusta al ancho del input 
        background: white;
        border: 1px solid #ccc;
        max-height: 200px;
        // Altura máxima para evitar que cubra todo 
        overflow-y: auto;
        // Habilita el scroll si hay muchas sugerencias 
        z-index: 2000;
        // Asegura que esté por encima de otros elementos 
        box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        // Sombra para mejor visualización 
        border-radius: 5px;
    }*/
    .suggestions-list-productos {
        position: absolute;
        top: 100%;
        /* justo debajo del input */
        left: 0;
        width: 100%;
        background: #fff;
        border: 1px solid #ccc;
        max-height: 200px;
        overflow-y: auto;
        z-index: 2000;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        border-radius: 4px;
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
<!-- Estilos para la tabla de partidas -->
<style>
    /*.tabla-scroll {
        height: 300px;
        //Altura fija para el área del scroll 
        overflow-y: auto;
        //Activar scroll vertical 
    }*/
    .tabla-scroll {
        position: relative;
        /* para que los absolutos se anclen bien */
        overflow: auto;
        /* mantienes el scroll aquí */
        max-height: 600px;
        /* o lo que necesites */
        height: 350px;
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

    /********************************/
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
                <div class="card-body ">
                    <!-- Formulario -->
                    <form class="form-container" id="formularioPedido">
                        <!-- 1st row: 4 inputs (2 select, 2 text) -->
                        <div class="row">
                            <div class="form-element">
                                <label for="factura">Pedido: </label>
                                <select name="factura" id="factura" style="width: 170px;">
                                    <option value="Directo">Directo</option>
                                </select>
                            </div>
                            <div class="form-element">
                                <label for="numero">Folio:</label>
                                <input type="text" name="numero" id="numero" readonly tabindex="-1">
                            </div>

                            <input type="text" name="tipoOperacion" id="tipoOperacion" hidden readonly value="alta" tabindex="-1">

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
                                        <input name="cliente" id="cliente" autocomplete="off"
                                            oninput="toggleClearButton()" style="padding-right: 2rem; width: 170px;" />
                                        <button id="clearInput" type="button" class="btn" onclick="clearAllFields()" tabindex="-1"
                                            style="display: none; padding: 5px 10px;">
                                            <i class="bx bx-x"></i>
                                        </button>
                                        <button type="button" class="btn" onclick="abrirModalClientes()" tabindex="-1"
                                            style="padding: 5px 5px;">
                                            <i class="bx bx-search"></i>
                                        </button>
                                    </div>
                                    <ul id="clientesSugeridos" class="suggestions-list"></ul>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-element" style="display: none;">
                                <label for="rfc">RFC <a class='bx'> *</a></label>
                                <input type="text" name="rfc" id="rfc" tabindex="-1">
                            </div>
                            <div class="form-element">
                                <label for="nombre">Nombre <a class='bx'> *</a></label>
                                <input type="text" name="nombre" id="nombre" style="width: 700px;" tabindex="-1" readonly />
                            </div>
                            <div class="form-element">
                                <label for="supedido">O.C</label>
                                <input type="text" name="supedido" id="supedido" style="width:170px;" disabled>
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
                                    style="width:180px; align-items: center;" disabled tabindex="-1">
                            </div>
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
                                    <input type="text" name="condicion" style="width: 410px;" id="condicion" maxlength="25" disabled>
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
                                    <input type="text" name="descuentoCliente" id="descuentoCliente" style="width: 110px;" tabindex="-1" disabled>
                                    <button type="button" class="btn ms-2" id="AyudaDescuento" tabindex="-1">
                                        <i class="bx bx-help-circle"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-element">
                                <label for="enviar">Enviar a <input type="button" id="datosEnvio" value="Datos de Envio" disabled> <a class='bx'> *</a> </label>
                                <div style="display: flex; align-items: center;">
                                    <input type="text" name="enviar" style="width:410px;" id="enviar" disabled>
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
                                <label for="regimenFiscal">Régimen Fiscal: <a class='bx'> *</a></label>
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
                            <button type="button" class="btn-primary" id="verTotales" tabindex="-1"
                                style="width: 150px;">Ver Totales</button>
                        </div>
                    </form>
                    <!-- 5th row: 2 buttons -->
                    <!-- Seccion de partidas  -->
                    <div class="table-data">
                        <div class="order">
                            <div class="table-container">
                                <div class="table-wrapper">
                                    <button type="button" class="btn-secondary" id="añadirPartida" tabindex="-1">Añadir
                                        Partida</button>
                                    <br>
                                    <div class="tabla-scroll">
                                        <!-- Tabla de partidas -->
                                        <table id="tablaProductos" name="tablaProductos" class="tabla-productos">
                                            <thead>
                                                <tr>
                                                    <th>Eliminar</th>
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
                                <!-- Campos ocultos para la lista de precios y la lista de impuestos -->
                                <input class="input-mt" type="text" name="listaPrecios" id="listaPrecios" readonly
                                    hidden>
                                <input class="input-mt" type="text" name="CVE_ESQIMPU" id="CVE_ESQIMPU" readonly hidden>
                            </div>
                        </div>
                    </div>
                    <br>
                    <!-- Botones para el Guardado y la cancelacion del pedio (revisar archivo JS/altaPedido.js) -->
                    <div class="row">
                        <div class="form-element"></div>
                        <button type="button" class="btn-save" id="guardarPedido" tabindex="-1"
                            style="width: 150px;">Guardar</button>
                        <button type="button" class="btn-cancel" id="cancelarPedido" tabindex="-1"
                            style="width: 150px;">Cancelar</button>
                    </div>
                    <!-- Modal clientes -->
                    <div id="modalClientes" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                            <div class="modal-content" style="max-height: 90vh; overflow-y: auto;">
                                <!-- Modal Header -->
                                <div class="modal-header">
                                    <h5 class="modal-title">Ayuda Clientes</h5>
                                    <button type="button" class="btn-close" onclick="cerrarModalClientes()"></button>
                                </div>
                                <!-- Filtro Estático -->
                                <div class="modal-static">
                                    <div class="form-group row">
                                        <div class="col-4">
                                            <label for="filtroCriterioClientes" class="form-label">Filtrar por:</label>
                                            <select id="filtroCriterioClientes" class="form-control">
                                                <option value="CLAVE">Clave</option>
                                                <option value="NOMBRE">Nombre</option>
                                            </select>
                                        </div>
                                        <div class="col-8">
                                            <label for="campoBusquedaClientes" class="form-label">Buscar:</label>
                                            <input type="search" id="campoBusquedaClientes" class="form-control"
                                                placeholder="Escribe aquí...">
                                        </div>
                                    </div>
                                </div>
                                <!-- Modal Body -->
                                <div class="modal-body">
                                    <!-- Lista de Productos -->
                                    <div id="">
                                        <table id="" name="tablalistaProductos" class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Clave</th>
                                                    <th>Nombre</th>
                                                    <th>Telefono</th>
                                                    <th>Saldo</th>
                                                </tr>
                                            </thead>
                                            <tbody id="datosClientes">
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
                </div>
                <!-- Modal Datos de Envio -->
                <div id="modalEnvio" class="modal fade" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h5 class="modal-title">Datos de Envío</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form id="formularioEnvio" class="px-4 pb-4">
                                <!-- selector y botón de nuevo dato -->
                                <div class="row align-items-center mb-3">
                                    <div class="col-md-8 d-flex align-items-center">
                                        <label for="selectDatosEnvio" class="me-2 mb-0">Escoge tus datos:</label>
                                        <select id="selectDatosEnvio" class="form-select w-auto">
                                            <option selected disabled>Selecciona un Dato</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <button type="button" id="nuevosDatosEnvio" class="btn btn-outline-primary">
                                            <i class="bx bxs-add-to-queue me-1"></i>Nuevo Dato
                                        </button>
                                    </div>
                                </div>

                                <!-- campos ocultos -->
                                <input type="hidden" id="csrf_tokenModal" value="<?php echo $csrf_token; ?>">
                                <input type="hidden" id="idDatos" value="">
                                <input type="hidden" id="folioDatos" value="">
                                <input type="hidden" id="titutoDatos" value="">

                                <!-- Sección: Datos de contacto -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="fw-bold">Dirección</h6>
                                        <div class="mb-3">
                                            <label for="nombreContacto" class="form-label">Nombre del contacto <span class="text-danger">*</span></label>
                                            <input type="text" id="nombreContacto" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="compañiaContacto" class="form-label">Compañía <span class="text-danger">*</span></label>
                                            <input type="text" id="compañiaContacto" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="telefonoContacto" class="form-label">Teléfono <span class="text-danger">*</span></label>
                                            <input type="tel" id="telefonoContacto" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="correoContacto" class="form-label">Correo electrónico <span class="text-danger">*</span></label>
                                            <input type="email" id="correoContacto" class="form-control" required>
                                        </div>
                                    </div>
                                    <!-- Sección: Datos de dirección -->
                                    <div class="col-md-6">
                                        <h6 class="fw-bold">Detalles de la dirección</h6>
                                        <div class="mb-3">
                                            <label for="direccion1Contacto" class="form-label">Línea 1 <span class="text-danger">*</span></label>
                                            <input type="text" id="direccion1Contacto" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="direccion2Contacto" class="form-label">Línea 2 <span class="text-danger">*</span></label>
                                            <input type="text" id="direccion2Contacto" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="codigoContacto" class="form-label">Código Postal <span class="text-danger">*</span></label>
                                            <input type="text" id="codigoContacto" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="estadoContacto" class="form-label">Estado <span class="text-danger">*</span></label>
                                            <select id="estadoContacto" class="form-select" required>
                                                <option selected disabled>Selecciona un estado</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="municipioContacto" class="form-label">Municipio <span class="text-danger">*</span></label>
                                            <select id="municipioContacto" class="form-select" required>
                                                <option selected disabled>Selecciona un municipio</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <!-- botones al pie -->
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-primary" id="actualizarDatos">Guardar</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="cerrarModalFooter">Cancelar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- Modal Nuevos Datos de Envio -->
                <div id="modalNuevoEnvio" class="modal fade" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h5 class="modal-title">Crear Datos de Envío</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form id="formularioNuevoEnvio" class="px-4 pb-4">
                                <!-- Título del envío -->
                                <div class="row mb-3">
                                    <div class="col">
                                        <label for="titutoContacto" class="form-label">
                                            Título de envío <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            id="titutoContacto"
                                            class="form-control"
                                            required>
                                    </div>
                                </div>

                                <!-- Datos ocultos -->
                                <input type="hidden" id="csrf_tokenModal" value="<?php echo $csrf_token; ?>">

                                <!-- Datos del contacto -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="fw-bold">Dirección</h6>

                                        <div class="mb-3">
                                            <label for="nombreNuevoContacto" class="form-label">
                                                Nombre del contacto <span class="text-danger">*</span>
                                            </label>
                                            <input
                                                type="text"
                                                id="nombreNuevoContacto"
                                                class="form-control"
                                                required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="compañiaNuevoContacto" class="form-label">
                                                Compañía <span class="text-danger">*</span>
                                            </label>
                                            <input
                                                type="text"
                                                id="compañiaNuevoContacto"
                                                class="form-control"
                                                required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="telefonoNuevoContacto" class="form-label">
                                                Teléfono <span class="text-danger">*</span>
                                            </label>
                                            <input
                                                type="tel"
                                                id="telefonoNuevoContacto"
                                                class="form-control"
                                                required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="correoNuevoContacto" class="form-label">
                                                Correo electrónico <span class="text-danger">*</span>
                                            </label>
                                            <input
                                                type="email"
                                                id="correoNuevoContacto"
                                                class="form-control"
                                                required>
                                        </div>
                                    </div>
                                    <!-- Direccion del contacto -->
                                    <div class="col-md-6">
                                        <h6 class="fw-bold">Detalles de la dirección</h6>

                                        <div class="mb-3">
                                            <label for="direccion1NuevoContacto" class="form-label">
                                                Línea 1 <span class="text-danger">*</span>
                                            </label>
                                            <input
                                                type="text"
                                                id="direccion1NuevoContacto"
                                                class="form-control"
                                                required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="direccion2NuevoContacto" class="form-label">
                                                Línea 2 <span class="text-danger">*</span>
                                            </label>
                                            <input
                                                type="text"
                                                id="direccion2NuevoContacto"
                                                class="form-control">
                                        </div>

                                        <div class="mb-3">
                                            <label for="codigoNuevoContacto" class="form-label">
                                                Código Postal <span class="text-danger">*</span>
                                            </label>
                                            <input
                                                type="text"
                                                id="codigoNuevoContacto"
                                                class="form-control"
                                                required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="estadoNuevoContacto" class="form-label">
                                                Estado <span class="text-danger">*</span>
                                            </label>
                                            <select
                                                id="estadoNuevoContacto"
                                                class="form-select"
                                                required>
                                                <option selected disabled>Selecciona un estado</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="municipioNuevoContacto" class="form-label">
                                                Municipio <span class="text-danger">*</span>
                                            </label>
                                            <select
                                                id="municipioNuevoContacto"
                                                class="form-select"
                                                required>
                                                <option selected disabled>Selecciona un municipio</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <!-- Botones -->
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" id="guardarDatosEnvio" class="btn btn-primary">
                                        Guardar
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-secondary"
                                        data-bs-dismiss="modal"
                                        id="cerrarModalFooterNuevo">
                                        Cancelar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- Modal Totales Pedido -->
                <div id="modalTotales" class="modal fade" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h5 class="modal-title">Totales del Pedido</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form id="formularioTotales" class="px-4 pb-4">
                                <!-- Sección: Datos de contacto -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="subtotal" class="form-label">Importe: <input type="text" id="subtotal"
                                                    class="form-control border-0 bg-transparent text-end" readonly>
                                            </label> <!-- class="form-control" -->
                                        </div>
                                        <div class="mb-3">
                                            <label for="descuento" class="form-label">Descuento: <input type="text" id="descuento"
                                                    class="form-control border-0 bg-transparent text-end" readonly>
                                            </label>
                                        </div>
                                        <div class="mb-3">
                                            <label for="subtotalPedido" class="form-label">SubTotal: <input type="text" id="subtotalPedido"
                                                    class="form-control border-0 bg-transparent text-end" readonly>
                                            </label>
                                        </div>
                                        <div class="mb-3">
                                            <label for="iva" class="form-label">I.V.A: <input type="text" id="iva"
                                                    class="form-control border-0 bg-transparent text-end" readonly>
                                            </label>
                                        </div>
                                        <div class="mb-3">
                                            <label for="importe" class="form-label">Importe: <input type="tel" id="importe"
                                                    class="form-control border-0 bg-transparent text-end" readonly>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <!-- botones al pie -->
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- </div> -->
            </main>
            <!-- MAIN -->
            <!-- CONTENT -->
        </section>
    </div>
    <!-- CONTENT -->
    <!-- Modal Productos -->
    <div id="modalProductos" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
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
                        <div class="col-6">
                            <label for="campoBusqueda" class="form-label">Buscar:</label>
                            <input type="search" id="campoBusqueda" class="form-control" placeholder="Escribe aquí...">
                        </div>
                        <div class="col-2">
                            <div class="form-check">
                                <!-- onchange en el input y paso de this -->
                                <input
                                    type="checkbox"
                                    name="todosProductos"
                                    id="todosProductos"
                                    class="form-check-input"
                                    onchange="mostrarTodosProductos(this)">
                                <label class="form-check-label" for="todosProductos">
                                    Mostrar Todos los Productos
                                </label>
                            </div>
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

    <!-- Scripts de JS para el funcionamiento del sistema -->
    <script src="JS/menu.js"></script>
    <script src="JS/app.js"></script>
    <script src="JS/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/ventas.js"></script>
    <script src="JS/altaPedido.js"></script>
    <!--<script src="JS/clientes.js"></script>-->

    <!-- Funcion JS para obtener la fecha actual-->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const now = new Date();
            const fechaActual = now.toISOString().slice(0, 10); // Formato YYYY-MM-DDTHH:MM
            document.getElementById("entrega").value = fechaActual;
        });
    </script>
    <!-- Funcion JS la sugerencia de Clientes y Productos-->
    <script>
        $(document).ready(function() {
            // Referencias a los elementos UL donde se mostrarán las sugerencias
            const suggestionsList = $('#clientesSugeridos');
            const suggestionsListProductos = $('#productosSugeridos');
            let highlightedIndex = -1; // Índice del elemento actualmente resaltado

            // Evento que se dispara al escribir en el campo de cliente
            $('#cliente').on('input', function() {
                const clienteInput = $(this).val().trim(); // Valor ingresado
                const claveUsuario = '<?php echo $claveUsuario ?>'; // Clave de usuario PHP inyectada
                const $clienteInput = $(this);

                if (clienteInput.length >= 1) {
                    // Si hay al menos un carácter, solicitamos sugerencias al servidor
                    $.ajax({
                        url: '../Servidor/PHP/ventas.php',
                        type: 'POST',
                        data: {
                            cliente: clienteInput, // Texto a buscar
                            numFuncion: '4', // Código de función para "buscar cliente"
                            clave: claveUsuario // Clave de usuario para filtrar resultados
                        },
                        success: function(response) {
                            try {
                                // Si la respuesta viene como string, intentamos parsear a JSON
                                if (typeof response === 'string') {
                                    response = JSON.parse(response);
                                }
                            } catch (e) {
                                console.error("Error al parsear la respuesta JSON", e);
                                return;
                            }

                            // Si la búsqueda tuvo éxito y devolvió un arreglo con al menos un cliente...
                            if (response.success && Array.isArray(response.cliente) && response.cliente.length > 0) {
                                suggestionsList.empty().show(); // Limpiamos y mostramos el listado
                                highlightedIndex = -1; // Reiniciamos índice resaltado

                                // Iteramos sobre cada cliente encontrado y creamos un <li> para cada uno
                                response.cliente.forEach((cliente, index) => {
                                    const listItem = $('<li></li>')
                                        .text(`${cliente.CLAVE.trim()} - ${cliente.NOMBRE}`) // Texto visible
                                        .attr('data-index', index) // Índice en el arreglo
                                        .attr('data-cliente', JSON.stringify(cliente)) // Datos completos JSON en atributo
                                        .on('click', function() {
                                            // Al hacer clic, seleccionamos ese cliente
                                            seleccionarClienteDesdeSugerencia(cliente);
                                        });

                                    suggestionsList.append(listItem);
                                });
                            } else {
                                // Si no hay coincidencias, ocultamos el listado
                                suggestionsList.empty().hide();
                            }
                        },
                        error: function() {
                            console.error("Error en la solicitud AJAX para sugerencias");
                            suggestionsList.empty().hide(); // Ocultamos ante fallo
                        }
                    });
                } else {
                    // Si el input queda vacío, limpamos y ocultamos las sugerencias
                    suggestionsList.empty().hide();
                }
            });

            // Manejo de navegación con teclado en el campo de cliente
            $('#cliente').on('keydown', function(e) {
                const items = suggestionsList.find('li');
                if (!items.length) return; // Si no hay sugerencias, nada que hacer

                if (e.key === 'ArrowDown') {
                    // Flecha abajo: avanzamos índice (circular) y resaltamos
                    highlightedIndex = (highlightedIndex + 1) % items.length;
                    actualizarDestacado(items, highlightedIndex);
                    e.preventDefault();
                } else if (e.key === 'ArrowUp') {
                    // Flecha arriba: retrocedemos índice (circular) y resaltamos
                    highlightedIndex = (highlightedIndex - 1 + items.length) % items.length;
                    actualizarDestacado(items, highlightedIndex);
                    e.preventDefault();
                } else if (e.key === 'Tab' || e.key === 'Enter') {
                    // Tab o Enter: si hay elemento resaltado, lo seleccionamos
                    if (highlightedIndex >= 0) {
                        const clienteSeleccionado = JSON.parse(
                            $(items[highlightedIndex]).attr('data-cliente')
                        );
                        seleccionarClienteDesdeSugerencia(clienteSeleccionado);
                        suggestionsList.empty().hide();
                        e.preventDefault(); // Prevenir tabulación normal
                    }
                }
            });

            // Función para aplicar/remover la clase "highlighted" al <li> correcto
            function actualizarDestacado(items, index) {
                items.removeClass('highlighted');
                $(items[index]).addClass('highlighted');
            }

            // Evento de entrada en campos con clase .producto (para sugerir productos)
            $(document).on("input", ".producto", function() {
                const productoInput = $(this).val().trim(); // Valor ingresado
                const claveUsuario = '<?php echo $claveUsuario ?>'; // Clave de usuario
                const $productoInput = $(this);

                // Obtenemos el <ul> de sugerencias correspondiente a esa fila
                const suggestionsListProductos = $productoInput.closest("td")
                    .find(".suggestions-list-productos");

                if (productoInput.length >= 2) {
                    // Si hay al menos 2 caracteres, solicitamos sugerencias de producto
                    $.ajax({
                        url: "../Servidor/PHP/ventas.php",
                        type: "POST",
                        data: {
                            producto: productoInput, // Texto a buscar
                            numFuncion: "16", // Código de función para "buscar producto"
                            clave: claveUsuario,
                        },
                        success: function(response) {
                            try {
                                if (typeof response === "string") {
                                    response = JSON.parse(response);
                                }
                            } catch (e) {
                                console.error("Error al parsear la respuesta JSON", e);
                                return;
                            }

                            // Limpiamos el <ul> y lo mostramos
                            suggestionsListProductos.empty().show();

                            /***********************************************************************/
                            const $scrollContainer = $productoInput.closest('.tabla-scroll');
                            if ($scrollContainer.length) {
                                const containerEl = $scrollContainer.get(0);
                                const dropdownEl = suggestionsListProductos.get(0);
                                const contRect = containerEl.getBoundingClientRect();
                                const dropRect = dropdownEl.getBoundingClientRect();

                                // si la parte inferior de la lista SALE del contenedor, la subimos
                                if (dropRect.bottom > contRect.bottom) {
                                    const delta = (dropRect.bottom - contRect.bottom) + 5;
                                    //alert(1);
                                    containerEl.scrollTop += delta;
                                    window.scrollBy(0,5);
                                }
                                // si la parte superior de la lista QUEDA por encima, la bajamos
                                if (dropRect.top < contRect.top) {
                                    //alert(2);
                                    const delta = (contRect.top - dropRect.top) + 5;
                                    containerEl.scrollTop -= delta;
                                    window.scrollBy(0,5);
                                }
                            }
                            /***********************************************************************/

                            if (response.success && Array.isArray(response.productos) && response.productos.length > 0) {
                                suggestionsListProductos.removeClass("d-none");

                                // a) Poblar lista con cada producto sugerido
                                response.productos.forEach((producto, index) => {
                                    const listItem = $("<li></li>")
                                        .text(`${producto.CVE_ART.trim()} - ${producto.DESCR}`) // Texto a mostrar
                                        .attr("data-index", index)
                                        .attr("data-producto", JSON.stringify(producto)) // Datos JSON
                                        .addClass("suggestion-item")
                                        .on("click", function() {
                                            seleccionarProductoDesdeSugerencia($productoInput, producto);
                                        });
                                    suggestionsListProductos.append(listItem);
                                });

                                // b) Resaltamos la primera opción por defecto
                                highlightedIndex = 0;
                                const allItems = suggestionsListProductos.find("li");
                                actualizarDestacadoProducto(allItems, highlightedIndex);

                            } else {
                                // Si no hubo coincidencias, mostramos mensaje "no match"
                                suggestionsListProductos.empty()
                                    .append("<li class='no-match'>No se encontraron coincidencias</li>")
                                    .show();
                            }
                        },
                        error: function() {
                            console.error("Error en la solicitud AJAX para sugerencias");
                            suggestionsListProductos.empty().hide();
                        },
                    });
                } else {
                    // Si el input tiene menos de 2 caracteres, ocultamos sugerencias
                    suggestionsListProductos.empty().hide();
                }
            });

            // Manejo de navegación con teclado en campo .producto
            $(document).on("keydown", ".producto", function(e) {
                const $input = $(this);
                const $row = $input.closest("tr");
                const $suggestions = $row.find(".suggestions-list-productos");
                const items = $suggestions.find("li.suggestion-item");
                const qty = $row.find(".unidad").val();
                //console.log("unidad: ", qty);

                // 1) Si se presiona Tab/Enter pero no hay sugerencias => mostrar aviso y bloquear
                if ((e.key === "Tab" || e.key === "Enter") && items.length === 0 && (!qty || qty.trim() === "")) {
                    //Validacion si ya hay un producto (.unidad)
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Aviso',
                        text: 'No se encontraron coincidencias para ese producto.',
                    });
                    return;
                }

                // 2) Navegar con flechas solo si hay sugerencias
                if (e.key === "ArrowDown" && items.length > 0) {
                    highlightedIndex = (highlightedIndex + 1) % items.length;
                    actualizarDestacadoProducto(items, highlightedIndex);
                    e.preventDefault();
                } else if (e.key === "ArrowUp" && items.length > 0) {
                    highlightedIndex = (highlightedIndex - 1 + items.length) % items.length;
                    actualizarDestacadoProducto(items, highlightedIndex);
                    e.preventDefault();
                }

                // 3) Si Tab/Enter y hay sugerencias => seleccionamos la resaltada
                if ((e.key === "Tab" || e.key === "Enter") && items.length > 0) {
                    e.preventDefault();
                    const productoSeleccionado = JSON.parse(
                        $(items[highlightedIndex]).attr("data-producto")
                    );
                    seleccionarProductoDesdeSugerencia($input, productoSeleccionado);
                    $suggestions.empty().hide();

                    // Luego de seleccionar, enfocamos el input de cantidad en la misma fila
                    const $cantidadInput = $input.closest("tr").find(".cantidad");
                    if ($cantidadInput.length) {
                        $cantidadInput.focus();
                        $cantidadInput.select();
                    }
                }
            });

            // Helper: resalta el <li> en índice dado para productos
            function actualizarDestacadoProducto(items, index) {
                items.removeClass("highlighted");
                $(items[index]).addClass("highlighted");
            }

            // Si se hace clic en cualquier parte fuera de .producto o .suggestions-list-productos, ocultamos las sugerencias
            $(document).on("click", function(event) {
                if (!$(event.target).closest(".producto, .suggestions-list-productos").length) {
                    $(".suggestions-list-productos").empty().hide();
                }
            });

            // Si se hace clic fuera del campo #cliente, ocultamos la lista de sugerencias de clientes
            $(document).on('click', function(event) {
                if (!$(event.target).closest('#cliente').length) {
                    $('#clientesSugeridos').empty().hide();
                }
            });

            // Evento para el botón "X" que limpia el input de cliente y sus campos relacionados
            $('#clearInput').on('click', function() {
                $('#cliente').val(''); // Limpia campo cliente
                $('#rfc').val(''); // Limpia RFC
                $('#nombre').val(''); // Limpia nombre
                $('#calle').val(''); // Limpia calle
                $('#numE').val(''); // Limpia número exterior
                $('#colonia').val(''); // Limpia colonia
                $('#codigoPostal').val(''); // Limpia código postal
                $('#poblacion').val(''); // Limpia población
                $('#pais').val(''); // Limpia país
                $('#regimenFiscal').val(''); // Limpia régimen fiscal
                $('#destinatario').val(''); // Limpia destinatario
                $('#clientesSugeridos').empty().hide(); // Oculta sugerencias de clientes
                $(this).hide(); // Oculta el botón "X"
            });
        });
    </script>
</body>

</html>