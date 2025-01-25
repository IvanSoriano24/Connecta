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
						<a class="btn-crear" id="altaPedido">
							<i class='bx bxs-file-plus'></i>
							<span class="text">Crear Pedido</span>
						</a>
						<a href="modificarPedido.php" class="btn-modificar">
							<i class='bx bxs-edit'></i>
							<span class="text">Modificar Pedido</span>
						</a>
						<a href="eliminarPedido.php" class="btn-eliminar">
							<i class='bx bxs-file-plus'></i>
							<span class="text">Cancelar Pedido</span>
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
										<th>Eliminar</th>
									</tr>
								</thead>
								<tbody id="datosPedidos">
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