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
    }
    $csrf_token = $_SESSION['csrf_token'];
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

    <script src="JS/sideBar.js?n=1"></script>
    <!-- My CSS -->
    <link rel="stylesheet" href="CSS/style.css">

    <link rel="stylesheet" href="CSS/selec.css">

    <title>MDConnecta</title>
    <link rel="icon" href="SRC/logoMDConecta.png"/>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* Encabezado */
        .inventory-wrapper .page-title {
            font-weight: 700;
            margin-bottom: .25rem;
        }

        .inventory-wrapper .page-subtitle {
            color: #6b7280;
            /* gris suave */
            margin: 0;
        }

        /* Tarjetas grandes tipo botón */
        .inventory-option {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: .75rem;
            width: 100%;
            max-width: 360px;
            height: 220px;
            border-radius: 16px;
            text-decoration: none;
            background: #2f66d0;
            /* azul principal */
            color: #fff;
            transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
        }

        .inventory-option i {
            font-size: 92px;
            line-height: 1;
        }

        .inventory-option .title {
            font-size: 18px;
            font-weight: 600;
            letter-spacing: .2px;
        }

        /* Hover/focus accesible */
        .inventory-option:hover,
        .inventory-option:focus-visible {
            transform: translateY(-3px);
            box-shadow: 0 10px 22px rgba(47, 102, 208, .28);
            background: #2a5bc0;
            outline: none;
        }

        /* Responsive fino */
        @media (max-width: 576px) {
            .inventory-option {
                height: 180px;
                max-width: 100%;
            }

            .inventory-option i {
                font-size: 72px;
            }
        }

        .badge-estado {
            display: inline-block;
            padding: 5px 16px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .badge-activo {
            background-color: rgba(40, 167, 69, 0.15); /* Verde muy suave */
            color: #28a745; /* Texto verde */
        }

        .badge-finalizado {
            background-color: rgba(108, 117, 125, 0.15); /* Gris muy suave */
            color: #6c757d; /* Texto gris */
        }

        /* Forzar que los menús desplegables dentro del modal se muestren encima */
        .modal .dropdown-menu {
            position: fixed !important;  /* saca el menú del flujo del modal */
            top: auto !important;
            left: auto !important;
            transform: none !important;
            z-index: 3000 !important;    /* más alto que el modal */
        }

        /* Opcional: limitar altura si hay muchas opciones */
        .modal .dropdown-menu.show {
            max-height: 300px;
            overflow-y: auto;
            border-radius: 10px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }

        #modalInventarios .modal-content {
            padding: 0 !important;
        }


    </style>
</head>

<body>
<div class="hero_area">
    <!-- SIDEBAR -->
    <?php include 'sidebar.php'; ?>
    <!-- CONTENT -->
    <section id="content">
        <!-- NAVBAR -->
        <?php include 'navbar.php'; ?>
        <!-- MAIN -->
        <main class=" hero_area">
            <input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo $csrf_token; ?>">

            <div class="inventory-wrapper container py-4">
                <h1 class="page-title text-center">Inventario Físico</h1>
                <p class="page-subtitle text-center">
                    Selecciona la manera en la que deseas realizar el inventario
                </p>
                <?php
                if ($tipoUsuario === "SUPER-ALMACENISTA" || $tipoUsuario === "ADMINISTRADOR") { ?>
                    <div class="text-center mt-4">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#modalInventarios" id="btnModalInventarios">
                            <i class='bx bx-clipboard'></i> Ver Inventarios
                        </button>

                        <div class="mt-3">
                            <div class="form-check form-switch d-inline-block me-4">
                                <input class="form-check-input" type="checkbox" id="switchGeneracionConteos">
                                <label class="form-check-label" for="switchGeneracionConteos">
                                    Generación conteos automático
                                </label>
                            </div>

                            <div class="form-check form-switch d-inline-block">
                                <input class="form-check-input" type="checkbox" id="switchGuardadoAutomatico">
                                <label class="form-check-label" for="switchGuardadoAutomatico">
                                    Guardado automático
                                </label>
                            </div>
                        </div>
                    </div>

                    <?php
                }
                ?>

                <!-- Botones ALAMCEN y LINEAS -->
                <div class="row justify-content-center g-4 mt-3">
                    <!-- Opción: Almacén -->
                    <div class="col-12 col-md-6 col-xl-4 d-flex justify-content-center">
                        <!--<a href="inventarioAlmacen.php" class="inventory-option shadow-sm">-->
                        <a href="#" class="inventory-option shadow-sm" style="background: #8f8f8f !important;">
                            <i class='bx bx-package'></i>
                            <span class="title">Almacén</span>
                        </a>
                    </div>

                    <!-- Opción: Líneas -->
                    <div class="col-12 col-md-6 col-xl-4 d-flex justify-content-center">
                        <a href="inventarioLineas.php" class="inventory-option shadow-sm">
                            <i class='bx bx-barcode'></i>
                            <span class="title">Líneas</span>
                        </a>
                    </div>
                </div>
            </div>


            <!-- Modal de Inventarios -->
            <div class="modal fade" id="modalInventarios" tabindex="-1" aria-labelledby="modalInventariosLabel"
                 aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content">

                        <!-- Encabezado -->
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="modalInventariosLabel">
                                <i class='bx bx-clipboard'></i> Inventarios
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                    aria-label="Cerrar"></button>
                        </div>

                        <!-- Cuerpo con la tabla -->
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle text-center">
                                    <thead class="table-primary">
                                    <tr>
                                        <th>No. Inventario</th>
                                        <th>Fecha</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                    </thead>
                                    <tbody id="tablaInventarios">
                                    <!-- Aquí se insertan dinámicamente los inventarios -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Footer con botón de crear nuevo -->
                        <div class="modal-footer">
                            <button type="button" class="btn btn-success" id="btnNuevoInventario">
                                <i class='bx bx-plus-circle'></i> Crear Nuevo Inventario
                            </button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>

                    </div>
                </div>
            </div>


            <!-- Modal de Asignacion -->
            <div id="asociarLineas" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Asignar Lineas</h5>
                            <button type="button" class="btn-close custom-close" data-dismiss="modal"
                                    id="cerrarModalAsociasionHeader" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">

                            <div>
                                <div class="row align-items-end">
                                    <div class="col-md-5">
                                        <h6>Seleccionar Usuario</h6>
                                        <select id="selectUsuario" class="form-select">
                                            <option selected disabled>Seleccione un usuario</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <h6>Seleccionar Linea</h6>
                                        <select id="lineaSelect" class="form-select">
                                            <option selected disabled>Seleccione una línea</option>
                                        </select>
                                    </div>
                                    <div class="col d-flex">
                                        <button type="button" class="btn btn-success ms-auto" id="btnAsignar">Asignar</button>
                                    </div>
                                    <div id="empresasAsociadas" class="mt-4">
                                        <h6>Lineas Asignadas</h6>
                                        <ul id="listaEmpresasAsociadas" class="list-group">
                                            <!-- Las empresas asociadas se cargarán dinámicamente aquí -->
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" id="cerrarModalAsociasionFooter"
                                    data-dismiss="modal">Cancelar
                            </button>
                            <button type="button" class="btn btn-primary custom-blue" id="btnGuardarAsignacion">Guardar
                                Asignacion
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </main>
        <!-- MAIN -->

    </section>
    <!-- CONTENT -->
</div>


<!-- jsPDF core -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<!-- jsPDF AutoTable plugin -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>

<!-- ExcelJS + FileSaver -->
<script src="https://cdn.jsdelivr.net/npm/exceljs@4.3.0/dist/exceljs.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/file-saver@2.0.5/dist/FileSaver.min.js"></script>


<!-- JS Para la confirmación empresa -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
<script src="JS/menu.js?n=1"></script>
<script src="JS/app.js?n=1"></script>
<script src="JS/script.js?n=1"></script>
<!--<script src="JS/productos.js"></script>-->
<script src="JS/inventarioMenu.js?n=1"></script>
</body>
</html>