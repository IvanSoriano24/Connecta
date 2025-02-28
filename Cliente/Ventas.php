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
        $contrasena = $_SESSION['empresa']['contrasena'] ?? null;
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
    <!--<script>
        $(document).ready(function() {
            datosPedidos();
        });
    </script>-->
    <script>
        // Asignar el evento "change" al select del filtro (asegúrate que el id sea correcto)
        document.getElementById("filtroFecha").addEventListener("change", function() {
            const filtroSeleccionado = this.value;
            cargarPedidos(filtroSeleccionado);
        });
        // Evento para el botón "Mostrar más"
        document.getElementById("btnMostrarMas").addEventListener("click", function() {
            paginaActual++; // Incrementa la página para cargar más registros
            datosPedidos(false); // Carga sin limpiar la tabla (se agregan nuevos registros)
        });


        // Evento para el cambio del filtro
        document.getElementById("filtroFecha").addEventListener("change", function() {
            localStorage.setItem("filtroSeleccionado", this.value);
            paginaActual = 1; // Reinicia la paginación
            document.getElementById("btnMostrarMas").style.display = "block"; // Asegura que el botón se muestre
            datosPedidos(true); // Carga inicial con nuevo filtro (limpia la tabla)
        });

        // Carga inicial cuando el DOM esté listo
        document.addEventListener("DOMContentLoaded", function() {
            paginaActual = 1;
            document.getElementById("btnMostrarMas").style.display = "block";
            datosPedidos(true);
        });
        // Al cargar la página, se lee el filtro guardado y se carga la información
        document.addEventListener("DOMContentLoaded", function() {
            let filtroGuardado = localStorage.getItem("filtroSeleccionado") || "Todos";
            // Actualiza el select con el filtro guardado (asegúrate que el elemento exista)
            const filtroSelect = document.getElementById("filtroFecha");
            if (filtroSelect) {
                filtroSelect.value = filtroGuardado;
            } else {
                console.error("No se encontró el elemento select con id 'filtroFecha'");
            }
            cargarPedidos(filtroGuardado);
        });
        // Función para cargar los pedidos (la tienes definida) y que recibe el filtro seleccionado
        // Función para renderizar los pedidos en la tabla
        function mostrarPedidosEnTabla(pedidos) {
            const pedidosTable = document.getElementById("datosPedidos");
            if (!pedidosTable) {
                console.error("No se encontró el elemento con id 'datosPedidos'");
                return;
            }
            pedidosTable.innerHTML = ""; // Limpiar la tabla
            pedidos.forEach((pedido) => {
                const row = document.createElement("tr");
                row.innerHTML = `
            <td>${pedido.Tipo || "Sin tipo"}</td>
            <td>${pedido.Clave || "Sin clave"}</td>
            <td>${pedido.Cliente || "Sin cliente"}</td>
            <td>${pedido.Nombre || "Sin nombre"}</td>
            <td>${pedido.Estatus || "0"}</td>
            <td>${pedido.FechaElaboracion?.date || "Sin fecha"}</td>
            <td style="text-align: right;">${pedido.Subtotal ? Math.floor(pedido.Subtotal) : "Sin subtotal"
            }</td>
            <td style="text-align: right;">${pedido.TotalComisiones
                ? `$${parseFloat(pedido.TotalComisiones).toFixed(2)}`
                : "Sin comisiones"
            }</td>
            <td style="text-align: right;">${pedido.ImporteTotal
                ? `$${parseFloat(pedido.ImporteTotal).toFixed(2)}`
                : "Sin importe"
            }</td>
            <td>${pedido.NombreVendedor || "Sin vendedor"}</td>
            <td>
                <button class="btnEditarPedido" name="btnEditarPedido" data-id="${pedido.Clave
            }" style="
                    display: inline-flex;
                    align-items: center;
                    padding: 0.5rem 1rem;
                    font-size: 1rem;
                    font-family: Lato;
                    color: #fff;
                    background-color: #007bff;
                    border: none;
                    border-radius: 0.25rem;
                    cursor: pointer;
                    transition: background-color 0.3s ease;">
                    <i class="fas fa-eye" style="margin-right: 0.5rem;"></i> Editar
                </button>
            </td>
            <td>
                <button class="btnCancelarPedido" name="btnCancelarPedido" data-id="${pedido.Clave
            }" style="
                    display: inline-flex;
                    align-items: center;
                    padding: 0.5rem 1rem;
                    font-size: 1rem;
                    font-family: Lato;
                    color: #fff;
                    background-color: #dc3545;
                    border: none;
                    border-radius: 0.25rem;
                    cursor: pointer;
                    transition: background-color 0.3s ease;">
                    <i class="fas fa-trash" style="margin-right: 0.5rem;"></i> Cancelar
                </button>
            </td>
        `;
                pedidosTable.appendChild(row);
            });
            // Si tienes función para asignar eventos a los botones, llámala aquí:
            agregarEventosBotones && agregarEventosBotones();
        }
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