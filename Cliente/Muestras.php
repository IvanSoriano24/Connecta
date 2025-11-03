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

    $mostrarModal = isset($_SESSION['empresa']) ? false : true;

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
    <!-- Bootstrap Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <script src="JS/sideBar.js?n=1"></script>
    <!-- My CSS -->
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/selec.css">
    <title>MDConnecta - Muestras</title>
    <link rel="icon" href="SRC/logoMDConecta.png" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body.modal-open .hero_area {
            filter: blur(5px);
        }

        .pagination {
            text-align: center;
            margin-top: 1rem;
        }

        .pagination button {
            margin: 0 .25rem;
            padding: 0.4rem .8rem;
            border: 1px solid #007bff;
            background: none;
            cursor: pointer;
        }

        .pagination button.active {
            background: #007bff;
            color: #fff;
        }

        .pagination-controls {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.5rem;
            margin-top: 1rem;
            font-family: Lato, sans-serif;
            font-size: 0.9rem;
            color: #333;
        }

        .cantidad-label {
            margin: 0;
        }

        .cantidad-select {
            padding: 0.4rem 0.6rem;
            font-size: 0.9rem;
            border: 1px solid #ccc;
            border-radius: 0.25rem;
            background-color: #fff;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .cantidad-select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }

        #filtroFecha,
        #filtroVendedor {
            padding-left: 2.5rem;
            border: 1px solid #ccc;
            border-radius: 0.25rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            color: #888;
        }

        .filtros-container {
            margin-bottom: 1rem;
            text-align: center;
        }

        .filtros-titulo {
            margin-bottom: .5rem;
            font-weight: 600;
        }

        .filtros-row {
            display: flex;
            justify-content: center;
            align-items: flex-end;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .filtro-item {
            display: flex;
            flex-direction: column;
            min-width: 200px;
            text-align: left;
        }

        .filtro-item label {
            font-size: 0.9rem;
            margin-bottom: 4px;
            font-weight: 500;
        }

        .form-control {
            padding: 6px 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 0.9rem;
            height: 38px;
        }

        .search-wrapper {
            position: relative;
        }

        .search-wrapper .search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 1rem;
        }

        .search-wrapper input {
            padding-left: 32px;
        }

        .modal-backdrop {
            display: none !important;
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
            <main class="text-center ">
                <div class="head-title">
                    <div class="left">
                        <h1>Muestras</h1>
                        <ul class="breadcrumb">
                            <li>
                                <a href="#">Inicio</a>
                            </li>
                            <li><i class='bx bx-chevron-right'></i></li>
                            <li>
                                <a class="" href="Muestras.php">Muestras</a>
                            </li>
                        </ul>
                    </div>
                    <!-- Secciones de Muestra-->
                    <div class="order">
                        <div style="align-items: center; display: flex; justify-content: center;" class="btn-group" role="group" aria-label="Filtros de Muestras">
                            <button type="button" class="btn btn-primary filtro-rol" data-rol="Activas">Activas</button>
                            <button type="button" class="btn btn-secondary filtro-rol" data-rol="Entregadas">Entregadas</button>
                            <button type="button" class="btn btn-secondary filtro-rol" data-rol="Canceladas">Canceladas</button>
                        </div>
                    </div>
                    <!-- Boton de Muestra-->
                    <div class="button-container">
                        <?php if ($tipoUsuario != "FACTURISTA"  && $tipoUsuario != "SUPER-ALMACENISTA") { ?>
                            <a href="gestionMuestra.php?accion=crear" class="btn-crear">
                                <i class='bx bxs-file-plus'></i>
                                <span href="#" class="text">Crear Muestra</span>
                            </a>
                        <?php } ?>
                    </div>

                    <!-- TABLA MUESTRAS  -->
                    <div class="table-data" id="muestrasActivas">
                        <div class="order">
                            <div class="filtros-container">
                                <h6 class="filtros-titulo">Filtrar por:</h6>

                                <div class="filtros-row">
                                    <div class="filtro-item">
                                        <label for="filtroFecha">Periodo</label>
                                        <select id="filtroFecha" class="form-control">
                                            <option value="Hoy">Hoy</option>
                                            <option value="Mes">Este Mes</option>
                                            <option value="Mes Anterior">Mes Anterior</option>
                                            <option value="Todos">Todos</option>
                                        </select>
                                    </div>

                                    <?php if ($tipoUsuario === "ADMINISTRADOR") { ?>
                                        <div class="filtro-item">
                                            <label for="filtroVendedor">Vendedor</label>
                                            <select id="filtroVendedor" class="form-control">
                                                <!-- opciones dinámicas -->
                                            </select>
                                        </div>
                                    <?php } ?>

                                    <div class="filtro-item search-box">
                                        <label for="searchTerm">Buscar</label>
                                        <div class="search-wrapper">
                                            <i class='bx bx-search search-icon'></i>
                                            <input
                                                id="searchTerm"
                                                class="form-control"
                                                type="text"
                                                placeholder="Buscar muestra..."
                                                onkeyup="debouncedSearch()" />
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <table id="muestras">
                                <thead>
                                    <tr>
                                        <th>Folio</th>
                                        <th>Cliente</th>
                                        <th>Nombre</th>
                                        <th>Fecha Elaboracion</th>
                                        <th>Nombre del vendedor</th>
                                        <th colspan="3" style="text-align:center;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="datosMuestras">
                                </tbody>
                            </table>
                            <!-- Paginación -->
                            <div id="pagination" class="pagination">
                            </div>
                            <div class="pagination-controls">
                                <label for="selectCantidad" class="cantidad-label">Mostrar</label>
                                <select id="selectCantidad" class="cantidad-select">
                                    <option value="10">10</option>
                                    <option value="20">20</option>
                                    <option value="30">30</option>
                                </select>
                                <span class="cantidad-label">por página</span>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <!-- MAIN -->
        </section>
        <!-- CONTENT -->
    </div>
    <!-- CONTENT -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/menu.js?n=1"></script>
    <script src="JS/app.js?n=1"></script>
    <script src="JS/script.js?n=1"></script>
    <script src="JS/muestras.js?n=1"></script>
    <script>
        const tipoUsuario = "<?php echo $tipoUsuario; ?>";
        if (tipoUsuario === "ADMINISTRADOR") {
            llenarFiltroVendedor();
        }

        // Evento para el botón "Mostrar más"
        $("#selectCantidad").on("change", function() {
            const seleccion = parseInt($(this).val(), 10);
            registrosPorPagina = isNaN(seleccion) ? registrosPorPagina : seleccion;
            paginaActual = 1;
            estadoMuestra = localStorage.getItem("estadoMuestra") || "Activas";
            datosMuestras(true);
        });

        // Evento para el cambio del filtro
        document.getElementById("filtroFecha").addEventListener("change", function() {
            localStorage.setItem("filtroSeleccionado", this.value);
            paginaActual = 1;
            datosMuestras(true);
        });

        // Carga inicial cuando el DOM esté listo
        document.addEventListener("DOMContentLoaded", function() {
            paginaActual = 1;
            registrosPorPagina = 10;
            datosMuestras(true);
        });
    </script>
    <div id="tipoUsuario" data-tipo="<?php echo htmlspecialchars($tipoUsuario); ?>" style="display:none;"></div>
</body>

</html>
