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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Titulo y Logo -->
    <title>MDConnecta</title>
    <link rel="icon" href="SRC/logoMDConecta.png" />

    <style>
        /* Asegúrate de incluirlo en tu CSS global */
        .suggestions-list {
            position: absolute;
            top: calc(100% + .25rem);
            left: 0;
            right: 0;
            z-index: 1050;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ced4da;
            border-radius: .25rem;
            background: #fff;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .suggestions-list .list-group-item {
            padding: .5rem .75rem;
            cursor: pointer;
        }

        .suggestions-list .list-group-item:hover {
            background-color: #f8f9fa;
        }

        #clientesSugeridos li {
            padding: 5px;
            cursor: pointer;
        }

        #clientesSugeridos li.highlighted {
            background-color: #007bff;
            color: white;
        }

        .search-group .search-input,
        .input-group .search-input {
            width: 100%;
            padding: 0.5rem 1rem;
            /* espacio a la izquierda para el icono */
            padding-left: 2.5rem;
            font-size: 1rem;
            border: 1px solid #ccc;
            border-radius: 0.25rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .input-group .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.2rem;
            color: #888;
            pointer-events: none;
        }

        .input-group .search-input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }
    </style>
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
                        <h2 class="text-center">Datos de Envio</h2>
                        <!-- Tabla de correos -->
                        <div class="table-data">
                            <div class="order">
                                <div class="order mb-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <!-- Botones a la izquierda -->
                                        <div class="btn-group">
                                            <button class="btn btn-success" id="btnImportar">
                                                <i class='bx bxs-file-import'></i> Importar
                                            </button>
                                            <button class="btn btn-primary" id="btnAgregar">
                                                <i class='bx bxs-plus-circle'></i> Agregar Datos
                                            </button>
                                        </div>

                                        <!-- Buscador a la derecha -->
                                        <div class="input-group" style="max-width: 300px;">
                                            <span class="input-group-text bg-white border-end-0">
                                                <i class='bx bx-search'></i>
                                            </span>
                                            <input
                                                id="searchTerm"
                                                class="form-control border-start-0"
                                                type="text"
                                                placeholder="Buscar Datos Envío..."
                                                onkeyup="debouncedSearch()" />
                                        </div>
                                    </div>
                                </div>
                                <div class="head">

                                    <table id="tablaDatos">
                                        <thead>
                                            <tr>
                                                <th>Cliente</th>
                                                <th>Titulo de Envio</th>
                                                <th>Visualizar</th>
                                                <th>Editar</th>
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
    <!-- Modal Crear Datos de Envio -->
    <div id="modalNuevoEnvio" class="modal fade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Crear Datos de Envío</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formularioNuevoEnvio" class="px-4 pb-4">
                    <!-- Título del envío -->
                    <!-- Dentro de tu formulario -->
                    <div class="row mb-3">
                        <!-- Autocomplete Cliente -->
                        <div class="col-md-6 position-relative">
                            <label for="cliente" class="form-label">Cliente</label>
                            <div class="input-group">
                                <input
                                    type="text"
                                    id="cliente"
                                    name="cliente"
                                    class="form-control"
                                    placeholder="Busca un cliente..."
                                    autocomplete="off"
                                    oninput="toggleClearButton()">
                                <button
                                    id="clearInput"
                                    type="button"
                                    class="btn btn-outline-secondary"
                                    onclick="clearAllFields()"
                                    tabindex="-1"
                                    style="display:none">
                                    <i class="bx bx-x"></i>
                                </button>
                            </div>
                            <!-- Lista de sugerencias -->
                            <ul id="clientesSugeridos" class="suggestions-list list-group"></ul>
                        </div>

                        <!-- Título de envío -->
                        <div class="col-md-6">
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
    <!-- Modal de visualizacion de Datos de Envio -->
    <div id="modalEnvio" class="modal fade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Datos de Envío</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formularioEnvio" class="px-4 pb-4">
                    <!-- selector y botón de nuevo dato -->
                    <!--<div class="row align-items-center mb-3">
                        <div class="col-md-8 d-flex align-items-center">
                            <label for="selectDatosEnvio" class="me-2 mb-0">Escoge tus datos:</label>
                            <select id="selectDatosEnvio" class="form-select w-auto">
                                <option selected disabled>Selecciona un Dato</option>
                            </select>
                        </div>
                    </div> -->

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
                                <input type="text" id="nombreContacto" class="form-control" disabled>
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
                        <!-- Sección: Datos de dirección -->
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
                    <!-- botones al pie -->
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="cerrarModalFooter">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Modal de edicion -->
    <div id="modalEnvioEditar" class="modal fade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Datos de Envío</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formularioEnvioEditar" class="px-4 pb-4">
                    <!-- selector y botón de nuevo dato -->
                    <div class="row align-items-center mb-3">
                        <div class="col-md-8 d-flex align-items-center">
                            <div class="mb-3">
                                <label for="titutoDatosEditar" class="form-label">Titulo de Envio <span class="text-danger">*</span></label>
                                <input type="text" id="titutoDatosEditar" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <!-- campos ocultos -->
                    <input type="hidden" id="csrf_tokenModal" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" id="folioDatosEditar" value="">
                    <input type="hidden" id="idDatosEditar" value="">
                    <input type="hidden" id="titutoDatosEditar" value="">

                    <!-- Sección: Datos de contacto -->
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold">Dirección</h6>
                            <div class="mb-3">
                                <label for="nombreContacto" class="form-label">Nombre del contacto <span class="text-danger">*</span></label>
                                <input type="text" id="nombreContactoEditar" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label for="compañiaContacto" class="form-label">Compañía <span class="text-danger">*</span></label>
                                <input type="text" id="compañiaContactoEditar" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label for="telefonoContacto" class="form-label">Teléfono <span class="text-danger">*</span></label>
                                <input type="tel" id="telefonoContactoEditar" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label for="correoContacto" class="form-label">Correo electrónico <span class="text-danger">*</span></label>
                                <input type="email" id="correoContactoEditar" class="form-control">
                            </div>
                        </div>
                        <!-- Sección: Datos de dirección -->
                        <div class="col-md-6">
                            <h6 class="fw-bold">Detalles de la dirección</h6>
                            <div class="mb-3">
                                <label for="direccion1Contacto" class="form-label">Línea 1 <span class="text-danger">*</span></label>
                                <input type="text" id="direccion1ContactoEditar" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label for="direccion2Contacto" class="form-label">Línea 2 <span class="text-danger">*</span></label>
                                <input type="text" id="direccion2ContactoEditar" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label for="codigoContacto" class="form-label">Código Postal <span class="text-danger">*</span></label>
                                <input type="text" id="codigoContactoEditar" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label for="estadoContacto" class="form-label">Estado <span class="text-danger">*</span></label>
                                <select id="estadoContactoEditar" class="form-select">
                                    <option selected disabled>Selecciona un estado</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="municipioContacto" class="form-label">Municipio <span class="text-danger">*</span></label>
                                <select id="municipioContactoEditar" class="form-select">
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
    <!-- JS Para el funcionamiento del sistema -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/menu.js"></script>
    <script src="JS/app.js"></script>
    <script src="JS/datosEnvio.js"></script>
    <script>
        const btn = document.getElementById('btnImportar');
        const input = document.getElementById('inputExcel');

        // Al hacer click en el botón, disparamos el file picker
        btn.addEventListener('click', () => {
            input.value = null; // Limpiar cualquier selección anterior
            input.click();
        });
        // Cuando el usuario selecciona archivo
        input.addEventListener('change', () => {
            Swal.fire({
                title: "Procesando datos...",
                text: "Por favor, espera mientras se guardan los datos.",
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                },
            });
            const file = input.files[0];
            if (!file) return;
            // Mostrar un spinner o desactivar botón si quieres
            const form = new FormData();
            form.append('numFuncion', '1'); // según tu PHP espera numFuncion = 1
            form.append('excel', file);
            fetch('../Servidor/PHP/datosEnvio.php', {
                    method: 'POST',
                    body: form
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        Swal.fire({
                            icon: "warning",
                            title: "Aviso",
                            text: data.message
                        });
                        return; //  ← aquí detenemos la ejecución en caso de error
                    }
                    const filas = data.rows ?? []; // si viene undefined, usamos array vacío
                    Swal.fire({
                        icon: "success",
                        text: `Se guardaron los datos correctamente.`,
                    }).then(() => {
                        obtenerDatosTabla();
                    });
                    console.log('Filas importadas:', filas);
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire({
                        icon: "warning",
                        title: "Aviso",
                        text: 'Error de red al importar el Excel.',
                    });
                });
        });
    </script>
    <script>
        $(document).ready(function() {
            // Referencias a los elementos UL donde se mostrarán las sugerencias
            const suggestionsList = $('#clientesSugeridos');
            // Evento que se dispara al escribir en el campo de cliente
            $('#cliente').on('input', function() {
                const clienteInput = $(this).val().trim(); // Valor ingresado
                const $clienteInput = $(this);

                if (clienteInput.length >= 1) {
                    // Si hay al menos un carácter, solicitamos sugerencias al servidor
                    $.ajax({
                        url: '../Servidor/PHP/clientes.php',
                        type: 'POST',
                        data: {
                            cliente: clienteInput, // Texto a buscar
                            numFuncion: '18', // Código de función para "buscar cliente"
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
                                suggestionsList.removeClass("d-none");
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
            // Si se hace clic fuera del campo #cliente, ocultamos la lista de sugerencias de clientes
            $(document).on('click', function(event) {
                if (!$(event.target).closest('#cliente').length) {
                    $('#clientesSugeridos').empty().hide();
                }
            });
            // Función para aplicar/remover la clase "highlighted" al <li> correcto
            function actualizarDestacado(items, index) {
                items.removeClass('highlighted');
                $(items[index]).addClass('highlighted');
            }
            $('#clearInput').on('click', function() {
                $('#cliente').val(''); // Limpia campo cliente
                $('#clientesSugeridos').empty().hide(); // Oculta sugerencias de clientes
                $(this).hide(); // Oculta el botón "X"
            });
        });
    </script>
    <script>
        // 1) Función que recorre las filas y esconde las que no coincidan
        function filterTable() {
            const term = document
                .getElementById('searchTerm')
                .value
                .trim()
                .toLowerCase();

            document
                .querySelectorAll('#tablaDatos tbody tr')
                .forEach(row => {
                    // tomamos todo el texto de la fila
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(term) ? '' : 'none';
                });
        }

        // 2) Un pequeño "debounce" para no disparar filterTable en cada pulsación
        function debounce(fn, delay = 200) {
            let timeoutId;
            return function(...args) {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => fn.apply(this, args), delay);
            };
        }
        // 3) Creamos la versión "debounced" de filterTable
        const debouncedSearch = debounce(filterTable, 250);

        // (Opcional) Si prefieres bindear con addEventListener en vez de onkeyup:
        // document.getElementById('searchTerm')
        //   .addEventListener('input', debouncedSearch);
    </script>

</body>

</html>