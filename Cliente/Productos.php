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

    <script src="JS/sideBar.js?n=1"></script>

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
	</style>
	<style>
		/* CSS */
		.search-head {
			display: flex;
			align-items: center;
			justify-content: space-between;
			margin-bottom: 1rem;
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
	<style>
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
			<main class="text-center my-5 hero_area">
				<div class="head-title">
					<div class="left">
						<h1>Inventario</h1>
						<ul class="breadcrumb">
							<li>
								<a href="#">Inicio</a>
							</li>
							<li><i class='bx bx-chevron-right'></i></li>
							<li>
								<a class="" href="Usuarios.php">Productos</a>
							</li>
						</ul>
						<div class="input-group">
							<i class='bx bx-search search-icon'></i>
							<input
								id="searchTerm"
								class="search-input"
								type="text"
								placeholder="Buscar producto..."
								onkeyup="debouncedSearch()"/>
						</div>
					</div>
				</div>
				<section id="features" class="features section">
					<!-- Section Title -->
					<div class="table-data">
						<input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo $csrf_token; ?>">
						<div class="order">
							<div class="head">
							</div>
							<table class="table" id="producto">
								<thead>
									<tr>
										<th>Clave</th>
										<th>Descripcion</th>
										<th>Existencias</th>
										<!--<th>Precio Vendedor</th>-->
									</tr>
								</thead>
								<tbody id="datosProductos">
								</tbody>
							</table>
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
				</section><!-- /Features Section -->
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
	<script src="JS/menu.js?n=1"></script>
	<script src="JS/app.js?n=1"></script>
	<script src="JS/script.js?n=1"></script>
	<script src="JS/productos.js?n=1"></script>
</body>

</html>
<!-- 
				<script>
			var empresa = '<?php // echo $nombreEmpresa 
							?>'
			console.log(empresa);
		</script>
		-->