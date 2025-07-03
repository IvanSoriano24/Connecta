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
                                <div class="order d-flex align-items-start gap-2 mb-3">
                                    <button class="btn btn-success" id="btnImportar">
                                        <i class='bx bxs-file-import'></i> Importar
                                    </button>
                                    <input
                                        type="file"
                                        id="inputExcel"
                                        accept=".xlsx,.xls"
                                        style="display:none" />

                                    <button class="btn btn-primary" id="btnAgregar">
                                        <i class='bx bxs-plus-circle'></i> Agregar Datos
                                    </button>
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
                    <div class="row mb-3">
                        <div class="col">
                            <label for="clienteId">Cliente</label>
                            <select
                                id="clienteId"
                                class="form-select"
                                required>
                                <option selected disabled>Selecciona un Cliente</option>
                            </select>
                        </div>
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
    <!-- Modal de visualizacion de correo -->
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
                                <select id="estadoContactoEditar" class="form-select" >
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
</body>

</html>