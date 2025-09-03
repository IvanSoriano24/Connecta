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
    $csrf_token  = $_SESSION['csrf_token'];
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

    <script src="JS/sideBar.js"></script>
    <!-- My CSS -->
    <link rel="stylesheet" href="CSS/style.css">

    <link rel="stylesheet" href="CSS/selec.css">

    <title>MDConnecta</title>
    <link rel="icon" href="SRC/logoMDConecta.png" />
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
            <main class="my-5 hero_area">
                <input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo $csrf_token; ?>">

                <div class="inventory-wrapper container py-4">
                    <h1 class="page-title text-center">Inventario Físico</h1>
                    <p class="page-subtitle text-center">
                        Selecciona la manera en la que deseas realizar el inventario
                    </p>

                    <div class="text-center mt-4">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalInventarios" id="btnModalInventarios">
                            <i class='bx bx-clipboard'></i> Ver Inventarios
                        </button>
                    </div>

                    <div class="row justify-content-center g-4 mt-3">
                        <!-- Opción: Almacén -->
                        <div class="col-12 col-md-6 col-xl-4 d-flex justify-content-center">
                            <!--<a href="inventarioAlmacen.php" class="inventory-option shadow-sm">-->
                            <a href="#" class="inventory-option shadow-sm">
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
                <div class="modal fade" id="modalInventarios" tabindex="-1" aria-labelledby="modalInventariosLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content">

                            <!-- Encabezado -->
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title" id="modalInventariosLabel">
                                    <i class='bx bx-clipboard'></i> Inventarios Realizados
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                            </div>

                            <!-- Cuerpo con la tabla -->
                            <div class="modal-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover align-middle">
                                        <thead class="table-primary">
                                            <tr>
                                                <th>#</th>
                                                <th>Fecha</th>
                                                <th>Estado</th>
                                                <!--         <th>Acciones</th> -->
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

                                <div class="container">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Seleccionar Usuario</h6>
                                            <select id="selectUsuario" class="form-select">
                                                <option selected disabled>Seleccione un usuario</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Seleccionar Linea</h6>
                                            <select id="lineaSelect" class="form-select">
                                                <option selected disabled>Seleccione una linea</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <input type="button" value="Asignar" class="form-button" id="btnAsignar">
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
                                    data-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-primary custom-blue" id="btnGuardarAsignacion">Guardar
                                    Asignacion</button>
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
    <!-- CONTENT -->
    </div>
    <!-- JS Para la confirmacion empresa -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/menu.js"></script>
    <script src="JS/app.js"></script>
    <script src="JS/script.js"></script>
    <script src="JS/productos.js"></script>
    <script src="JS/inventarioMenu.js"></script>
</body>

</html>
<!-- 
				<script>
			var empresa = '<?php // echo $nombreEmpresa 
                            ?>'
			console.log(empresa);
		</script>
		-->