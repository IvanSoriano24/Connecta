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
    /*if ($_SESSION['usuario']['tipoUsuario'] == 'ADMINISTRADOR') {
        header('Location:Dashboard.php');
        exit();
    }*/

    $mostrarModal = isset($_SESSION['empresa']) ? false : true;

    //$empresa = $_SESSION['empresa']['razonSocial'];
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
    <title>MDConnecta</title>
    <link rel="icon" href="SRC/logoMDConecta.png" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body.modal-open .hero_area {
            filter: blur(5px);
            /* Difumina el fondo mientras un modal está abierto */
        }
        /* From Uiverse.io by barisdogansutcu */
        svg {
            width: 3.25em;
            transform-origin: center;
            animation: rotate4 2s linear infinite;
        }

        circle {
            fill: none;
            stroke: hsl(214, 97%, 59%);
            stroke-width: 2;
            stroke-dasharray: 1, 200;
            stroke-dashoffset: 0;
            stroke-linecap: round;
            animation: dash4 1.5s ease-in-out infinite;
        }

        @keyframes rotate4 {
            100% {
                transform: rotate(360deg);
            }
        }

        @keyframes dash4 {
            0% {
                stroke-dasharray: 1, 200;
                stroke-dashoffset: 0;
            }

            50% {
                stroke-dasharray: 90, 200;
                stroke-dashoffset: -35px;
            }

            100% {
                stroke-dashoffset: -125px;
            }
        }

        /* BOTON MOSTRAR MAS */
        /* From Uiverse.io by felipesntr */
        button {
            border: 2px solid #24b4fb;
            background-color: #24b4fb;
            border-radius: 0.9em;
            cursor: pointer;
            padding: 0.8em 1.2em 0.8em 1em;
            transition: all ease-in-out 0.2s;
            font-size: 16px;
        }

        button span {
            display: flex;
            justify-content: center;
            align-items: center;
            color: #fff;
            font-weight: 600;
        }

        button:hover {
            background-color: #0071e2;
        }
        .search-head h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
        }

        .input-group {
            position: relative;
            width: 300px;
            /* ajusta al ancho deseado */
        }

        .search-group .search-input,
        .input-group .search-input {
            width: 100%;
            padding: 0.5rem 1rem 0.5rem 2.5rem;
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

        /*******************/
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
        #filtroVendedor,
        #filtroClientes {
            border: 1px solid #ccc;
            border-radius: 0.25rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            color: #888;
            min-width: 80%;
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
                    <h1>Reportes</h1>
                    <ul class="breadcrumb">
                        <li>
                            <a href="#">Inicio</a>
                        </li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li>
                            <a class="" href="Reportes.php">Reportes</a>
                        </li>
                    </ul>
                </div>

                <!-- TABLA PEDIDOS  -->
                <div class="table-data" id="reportesActivos">
                    <input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="order">
                        <label for="filtros" style="margin-right: 94%;">Filtrar Por:</label>
                        <div class="head">
                            <div class="input-group">
                                <tr>
                                    <td>
                                        <label for="Periodo">Periodo: </label>
                                        <select id="filtroFecha">

                                        </select>
                                    </td>
                                </tr>
                            </div>

                            <div class="input-group">
                                <?php if ($tipoUsuario === "ADMINISTRADOR") { ?>
                                    <tr>
                                        <td>
                                            <label for="Vendedor">Vendedor: </label>
                                            <select id="filtroVendedor">
                                            </select>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </div>
                            <div class="input-group">
                                <tr>
                                    <td>
                                        <label for="Clientes">Clientes: </label>
                                        <select id="filtroClientes">
                                        </select>
                                    </td>
                                </tr>
                            </div>
                            <h3></h3>
                            <div class="input-group">
                                <i class='bx bx-search search-icon'></i>
                                <input
                                    id="searchTerm"
                                    class="search-input"
                                    type="text"
                                    placeholder="Buscar..."
                                    onkeyup="debouncedSearchReportes()" />
                            </div>
                            <!-- <i class='bx bx-filter'></i> -->
                        </div>
                        <table id="reportes">
                            <thead>
                            <tr>
                                <th>Líneas y Productos</th>
                                <th>ENERO</th>
                                <th>FEBRERO</th>
                                <th>MARZO</th>
                                <th>ABRIL</th>
                                <th>MAYO</th>
                                <th>JUNIO</th>
                                <th>JULIO</th>
                                <th>AGOSTO</th>
                                <th>SEPTIEMBRE</th>
                                <th>OCTUBRE</th>
                                <th>NOVIEMBRE</th>
                                <th>DICIEMBRE</th>
                            </tr>
                            </thead>
                            <tbody id="datosReportes">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->
</div>
<!-- CONTENT -->
<!-- JS Para la confirmacion empresa -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
<script src="JS/menu.js"></script>
<script src="JS/app.js"></script>
<script src="JS/script.js"></script>
<script src="JS/reportes.js"></script>
<script>
    var tipoUsuario = "<?php echo $tipoUsuario; ?>";
    if (tipoUsuario === "ADMINISTRADOR") {
        llenarFiltroVendedor();
    } else {
        llenarFiltroCliente();
    }
</script>
</body>
</html>