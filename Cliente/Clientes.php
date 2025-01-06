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
		<section id="sidebar">
			<a href="#" class="brand">
				<i class='bx bxs-cloud'></i>
				<span class="text">MDCloud</span>
			</a>
			<ul class="side-menu top">
				<li class="active">
					<a href="Dashboard.php">
						<i class='bx bxs-dashboard'></i>
						<span class="text">Inicio</span>
					</a>
				</li>
				<li>
					<a href="#">
						<i class='bx bxs-shopping-bag-alt'></i>
						<span class="text">Ventas</span>
					</a>
				</li>
				<li>
					<a href="Productos.php">
						<i class='bx bxs-package'></i>
						<span class="text">Productos</span>
					</a>
				</li>
				<li>
					<a href="Usuarios.php">
						<i class='bx bxs-user'></i>
						<span class="text">Mis Clientes</span>
					</a>
				</li>
				<li>
					<a href="#">
						<i class='bx bxs-message-dots'></i>
						<span class="text">Mensajes</span>
					</a>
				</li>
				<li>
					<a href="#">
						<i class='bx bxs-file'></i>
						<span class="text">Reportes</span>
					</a>
				</li>

			</ul>
			<ul class="side-menu">
				<?php
				if ($tipoUsuario == "ADMINISTRADOR") { ?>
					<li>
						<a href="#" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
							<i class='bx bxs-cog'></i>
							<span class="text">Configuración</span>
						</a>
						<ul class="dropdown-menu">
							<li><a class="dropdown-item" href="infoEmpresa.php" id="informaEmpresa">Información Empresa</a>
							</li>

							<li><a class="dropdown-item" href="ConexioSAE.php" id="infoSae">Conexión SAE</a></li>
						</ul>
					</li>
				<?php }
				?>
				<li>
					<a href="" class="logout" id="cerrarSesion">
						<i class='bx bxs-log-out-circle'></i>
						<span class="text">Cerrar Sesion</span>
					</a>
				</li>
			</ul>
		</section>
		<!-- SIDEBAR -->

		<!-- CONTENT -->
		<section id="content">
			<!-- NAVBAR -->
			<nav>
				<!--
			<i class='bx bx-menu' ></i>
			<a href="#" class="nav-link"></a>
			
			<form action="#">
				<div class="form-input">
					<input type="search" placeholder="Search...">
					<button type="submit" class="search-btn"><i class='bx bx-search' ></i></button>
				</div>
			</form>
							   
			<input type="checkbox" id="switch-mode" hidden>
			<label for="switch-mode" class="switch-mode"></label>
			
			<a href="#" class="notification">
				<i class='bx bxs-bell' ></i>
				<span class="num">8</span>
			</a>

			<a href="#" class="profile">
				<img src="img/people.png">
			</a>
			-->
			</nav>
			<!-- fin NAVBAR -->

			<!-- MAIN -->

			<main class="text-center my-5 hero_area">

				<div class="head-title">
					<div class="left">
						<h1>Usuarios</h1>
						<ul class="breadcrumb">
							<li>
								<a href="#">Inicio</a>
							</li>
							<li><i class='bx bx-chevron-right'></i></li>
							<li>
								<a class="" href="Usuarios.php">Clientes</a>
							</li>
						</ul>
					</div>

					<i href="#" class="btn-download">
						<i class='bx bxs-file-plus'></i>
						<span class="text">Agregar Cliente</span>
					</i>



					<div class="table-data">
						<div class="order">
							<div class="head">
								<h3>Usuarios</h3>
								<i class='bx bx-search'></i>
								<i class='bx bx-filter'></i>
							</div>
							<table>
								<thead>
									<tr>
										<th>Clave</th>
										<th>Estatus</th>
										<th>Nombre</th>
										<th>Calle</th>
										<th>Telefono</th>
										<th>Saldo</th>
										<th>Estado Datos Timbrado</th>
										<th>Nombre Comercial</th>
									</tr>
								</thead>
								<tbody id="datosClientes">

								</tbody>
							</table>
						</div>
			</main>
			<!-- MAIN -->
		</section>
		<!-- CONTENT -->
	</div>
	</section>
	<!-- CONTENT -->
	<!-- JS Para la confirmacion empresa -->
	<script src="JS/menu.js"></script>
	<script src="JS/app.js"></script>
	<script src="JS/script.js"></script>
	<script src="JS/clientes.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
	<script>
		$(document).ready(function() {
			informaEmpresa(); // Llamada a la función cuando la página de la empresa se ha cargado.
		});
	</script>
	<script>
		document.getElementById('empresaModal').addEventListener('shown.bs.modal', function() {
			//var usuario = '<?php echo $nombreUsuario; ?>';
			cargarEmpresa(usuario);
		});
		document.addEventListener('DOMContentLoaded', function() {
			const empresaSeleccionada = <?php echo json_encode(isset($_SESSION['empresa']) ? $_SESSION['empresa'] : null); ?>;
			if (empresaSeleccionada === null) {
				const empresaModal = new bootstrap.Modal(document.getElementById('empresaModal'));
				empresaModal.show();
			}
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