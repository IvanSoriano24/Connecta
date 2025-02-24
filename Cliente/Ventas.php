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
		$claveVendedor = $_SESSION['empresa']['claveVendedor'] ?? null;
		$claveSae = $_SESSION['empresa']['claveSae'] ?? null;
	}
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
    <title>AdminHub</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
    body.modal-open .hero_area {
        filter: blur(5px);
        /* Difumina el fondo mientras un modal está abierto */
    }
    </style>


    <style>
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
                        <h1>Pedidos</h1>
                        <ul class="breadcrumb">
                            <li>
                                <a href="#">Inicio</a>
                            </li>
                            <li><i class='bx bx-chevron-right'></i></li>
                            <li>
                                <a class="" href="Ventas.php">Ventas</a>
                            </li>
                        </ul>
                    </div>
                    <div class="button-container">
                        <a href="#" class="btn-crear" id="altaPedido">
                            <i class='bx bxs-file-plus'></i>
                            <span href="#" class="text">Crear Pedido</span>
                        </a>
                    </div>

                    <!-- TABLA PEDIDOS  -->
                    <div class="table-data">
                        <div class="order">
                            <div class="head">
                                <h3></h3>
                                <i class='bx bx-search'></i>
                                <!-- <i class='bx bx-filter'></i> -->
                            </div>
                            <tr>
                                <td>
                                    <select id="filtroFecha">
                                        <option value="Hoy">Hoy</option>
                                        <option value="Mes">Mes</option>
                                        <option value="Mes Anterior">Mes Anterior</option>
                                        <option value="Todos">Todos</option>
                                    </select>
                                </td>
                            </tr>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Clave</th>
                                        <th>Cliente</th>
                                        <th>Nombre</th>
                                        <th>Estatus</th>
                                        <th>Fecha Elaboracion</th>
                                        <th>Subtotal</th>
                                        <th>Total de Comisiones</th>
                                        <th>Importe total</th>
                                        <th>Nombre del vendedor</th>
                                        <th>Editar</th>
                                        <th>Cancelar</th>
                                    </tr>
                                </thead>
                                <tbody id="datosPedidos">
                                </tbody>
                            </table>
                            <!-- Botón Mostrar Más -->
                            <div style="text-align: center; margin-top: 1rem;">
                                <button id="btnMostrarMas">
                                    <span>
                                        <svg height="24" width="24" viewBox="0 0 24 24"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M11 11V5h2v6h6v2h-6v6h-2v-6H5v-2z" fill="currentColor"></path>
                                        </svg>
                                        Mostrar 
                                    </span>
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
    <!-- CONTENT -->
    <!-- JS Para la confirmacion empresa -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/menu.js"></script>
    <script src="JS/app.js"></script>
    <script src="JS/script.js"></script>
    <script src="JS/ventas.js"></script>
    <script>
    $(document).ready(function() {
        datosPedidos();
    });
    </script>

</body>

</html>
<!-- 
				<script>
			var empresa = '<?php // echo $nombreEmpresa 
							?>'
			console.log(empresa);
		</script>
		-->