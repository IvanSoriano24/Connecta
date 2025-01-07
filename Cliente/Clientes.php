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
<html lang="es">
<style>
	/* Asegura que el modal sea más grande y ocupe un 90% del ancho de la pantalla */
	.modal-dialog {
		max-width: 90%;
		margin: 30px auto;
	}

	/* Establece una altura mínima en el formulario y permite desplazamiento */
	.form-container {
		max-height: none; /* Elimina la limitación de altura para que no corte el formulario */
		overflow-y: auto; /* Permite el desplazamiento vertical si es necesario */
	}

	/* Asegura que el contenido del modal ocupe el 90% de la altura de la ventana */
	.modal-content {
		max-height: 90vh; /* 90% de la altura de la ventana */
		overflow-y: auto; /* Si el contenido es grande, se agregará un scrollbar */
	}

	/* Espaciado entre las filas */
	.row {
		margin-bottom: 1rem;
	}

	/* Asegura que los inputs ocupen todo el ancho disponible */
	.form-control {
		width: 100%;
	}

	/* Ajustes de margen para los elementos */
	.input-mt {
		margin-top: 10px;
	}
</style>

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
			<!-- MAIN -->
			<main class="text-center">
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
										<th>Nombre</th>
										<th>Calle</th>
										<th>Telefono</th>
										<th>Saldo</th>
										<th>Estado Datos Timbrado</th>
										<th>Nombre Comercial</th>
										<th>Visualizar</th>
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
	<div id="usuarioModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog modal-xl" role="document"> <!-- Cambié modal-lg por modal-xl -->
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Agregar Usuario</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close" id="cerrarModal">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<form class="form-container">
					<!-- Primera fila -->
					<div class="row">
						<div class="col-6 mb-3">
							<label for="clave">Clave: </label>
							<input type="text" id="clave" class="form-control">
						</div>
						<div class="col-6 mb-3">
							<label for="nombre">Nombre</label>
							<input class="form-control" type="text" name="nombre" id="nombre">
						</div>
					</div>
					<!-- Segunda fila -->
					<div class="row">
						<div class="col-6 mb-3">
							<label for="estatus">Estatus</label>
							<input type="text" name="estatus" id="estatus" class="form-control">
						</div>
						<div class="col-6 mb-3">
							<label for="saldo">Saldo</label>
							<input type="text" id="saldo" class="form-control">
						</div>
					</div>
					<!-- Tercera fila -->
					<div class="row">
						<div class="col-6 mb-3">
							<label for="rfc">RFC</label>
							<input class="form-control" type="text" name="rfc" id="rfc">
						</div>
						<div class="col-6 mb-3">
							<label for="regimenFiscal">Regimen fiscal</label>
							<input class="form-control" type="text" name="regimenFiscal" id="regimenFiscal">
						</div>
						<div class="col-6 mb-3">
							<label for="curp">C.U.R.P.:</label>
							<input class="form-control" type="text" name="curp" id="curp">
						</div>
					</div>
					<!-- Cuarta fila -->
					<div class="row">
						<div class="col-6 mb-3">
							<label for="calle">Calle</label>
							<input class="form-control" type="text" name="calle" id="calle">
						</div>
					</div>
					<!-- Quinta fila -->
					<div class="row">
						<div class="col-6 mb-3">
							<label for="numE">Num. ext</label>
							<input class="form-control" type="text" name="numE" id="numE">
						</div>
						<div class="col-6 mb-3">
							<label for="numI">Num. int.</label>
							<input class="form-control" type="text" name="numI" id="numI">
						</div>
						<div class="col-6 mb-3">
							<label for="entreCalle">Entre Calle</label>
							<input class="form-control" type="text" name="entreCalle" id="entreCalle">
						</div>
					</div>
					<!-- Sexta fila -->
					<div class="row">
						<div class="col-6 mb-3">
							<label for="yCalle">Y calle:</label>
							<input class="form-control" type="text" name="yCalle" id="yCalle">
						</div>
						<div class="col-6 mb-3">
							<label for="pais">Pais:</label>
							<input class="form-control" type="text" name="pais" id="pais">
						</div>
					</div>
					<!-- Septima fila -->
					<div class="row">
						<div class="col-6 mb-3">
							<label for="nacionalidad">Nacionalidad:</label>
							<input class="form-control" type="text" name="nacionalidad" id="nacionalidad">
						</div>
						<div class="col-6 mb-3">
							<label for="codigoPostal">Codigo Postal:</label>
							<input class="form-control" type="date" name="codigoPostal" id="codigoPostal">
						</div>
					</div>
					<!-- Octava fila -->
					<div class="row">
						<div class="col-6 mb-3">
							<label for="estado">Estado:</label>
							<input class="form-control" type="text" name="estado" id="estado">
						</div>
						<div class="col-6 mb-3">
							<label for="municipio">Municipio:</label>
							<input class="form-control" type="text" name="municipio" id="municipio">
						</div>
					</div>
					<!-- Novena fila -->
					<div class="row">
						<div class="col-6 mb-3">
							<label for="poblacion">Poblacion:</label>
							<input class="form-control" type="text" name="poblacion" id="poblacion">
						</div>
						<div class="col-6 mb-3">
							<label for="colonia">Colonia:</label>
							<input class="form-control" type="text" name="colonia" id="colonia">
						</div>
					</div>
					<!-- Decima fila -->
					<div class="row">
						<div class="col-6 mb-3">
							<label for="referencia">Referencia:</label>
							<input class="form-control" type="text" name="referencia" id="referencia">
						</div>
					</div>
					<!-- Onceaba fila -->
					<div class="row">
						<div class="col-6 mb-3">
							<label for="clasificacion">Clasificacion:</label>
							<input class="form-control" type="text" name="clasificacion" id="clasificacion">
						</div>
						<div class="col-6 mb-3">
							<label for="zona">Zona:</label>
							<input class="form-control" type="text" name="zona" id="zona">
						</div>
					</div>
					<!-- Doceaba fila -->
					<div class="row">
						<div class="col-6 mb-3">
							<label for="telefono">Telefono:</label>
							<input class="form-control" type="text" name="telefono" id="telefono">
						</div>
						<div class="col-6 mb-3">
							<label for="fax">Fax:</label>
							<input class="form-control" type="text" name="fax" id="fax">
						</div>
					</div>
					<!-- Página Web -->
					<div class="row">
						<div class="col-6 mb-3">
							<label for="paginaWeb">Pagina Web:</label>
							<input class="form-control" type="text" name="paginaWeb" id="paginaWeb">
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>

	<!-- JS Para la confirmacion empresa -->
	<script src="JS/menu.js"></script>
	<script src="JS/app.js"></script>
	<script src="JS/script.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
	<script src="JS/clientes.js"></script>

</body>

</html>
<!-- 
			<script>
			var empresa = '<?php //echo $noEmpresa 
							?>'
			console.log(empresa);
		</script>	
		-->