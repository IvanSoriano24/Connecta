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
		max-height: none;
		/* Elimina la limitación de altura para que no corte el formulario */
		overflow-y: auto;
		/* Permite el desplazamiento vertical si es necesario */
	}

	/* Asegura que el contenido del modal ocupe el 90% de la altura de la ventana */
	.modal-content {
		max-height: 90vh;
		/* 90% de la altura de la ventana */
		overflow-y: auto;
		/* Si el contenido es grande, se agregará un scrollbar */
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
						<h1>Clientes</h1>
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
								<h3>Clientes</h3>
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
		<div class="modal-dialog modal-xl" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Cliente</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close" id="cerrarModal">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<ul class="nav nav-tabs" role="tablist">
						<li class="nav-item">
							<a class="nav-link active" id="datosGenerales-tab" data-toggle="tab" href="#datosGenerales"
								role="tab" aria-controls="datosGenerales" aria-selected="true">Datos Generales</a>
						</li>
						<li class="nav-item">
							<a class="nav-link active" id="datosGenerales-tab" data-toggle="tab" href="#datosGenerales"
								role="tab" aria-controls="datosGenerales" aria-selected="true">Datos Ventas</a>
						</li>
					</ul>


					<div class="tab-content">
						<!-- Datos Generales -->
						<div class="tab-pane fade show active" id="datosGenerales" role="tabpanel">
							<!-- Primera fila -->
							<div class="row">
								<div class="">
									<label for="clave" style="margin-right: 5px;">Clave: </label>
									<input class="input-mo" type="text" id="clave" style="margin-right: 20px;">

									<label for="nombre" style="margin-right: 5px;">Nombre</label>
									<input class="input-mt" type="text" name="nombre" id="nombre">
								</div>
								<div>
									<label for="estatus" style="margin-right: 5px;">Estatus</label>
									<input type="text" name="estatus" id="estatus" class="input-mt"
										style="margin-right: 5px;">

									<label for="saldo" style="margin-right: 5px;">Saldo</label>
									<input type="text" id="saldo" class="input-mt" style="margin-right: 5px;">
								</div>
							</div>
							<br>

							<!-- Segunda fila -->
							<div class="row">

							</div>

							<!-- Datos Generales -->
							<div class="tab-pane fade show active" id="datosGenerales" role="tabpanel">
								<div class="form-group row">
									<div class="col-4">
										<label for="rfc">RFC</label>
										<input type="text" id="rfc" class="form-control">
									</div>
									<div class="col-4">
										<label for="regimenFiscal">Régimen Fiscal</label>
										<input type="text" id="regimenFiscal" class="form-control">
									</div>
									<div class="col-4">
										<label for="curp">C.U.R.P.</label>
										<input type="text" id="curp" class="form-control">
									</div>
								</div>

								<div class="form-group row">
									<div class="col-6">
										<label for="calle">Calle</label>
										<input type="text" id="calle" class="form-control">
									</div>
									<div class="col-3">
										<label for="numE">Num. Ext</label>
										<input type="text" id="numE" class="form-control">
									</div>
									<div class="col-3">
										<label for="numI">Num. Int</label>
										<input type="text" id="numI" class="form-control">
									</div>
								</div>

								<div class="form-group row">
									<div class="col-4">
										<label for="entreCalle">Entre Calle</label>
										<input type="text" id="entreCalle" class="form-control">
									</div>
									<div class="col-4">
										<label for="yCalle">Y Calle</label>
										<input type="text" id="yCalle" class="form-control">
									</div>
									<div class="col-4">
										<label for="pais">País</label>
										<input type="text" id="pais" class="form-control">
									</div>
								</div>

								<div class="form-group row">
									<div class="col-4">
										<label for="nacionalidad">Nacionalidad</label>
										<input type="text" id="nacionalidad" class="form-control">
									</div>
									<div class="col-4">
										<label for="estado">Estado</label>
										<input type="text" id="estado" class="form-control">
									</div>
									<div class="col-4">
										<label for="codigoPostal">Código Postal</label>
										<input type="text" id="codigoPostal" class="form-control">
									</div>
								</div>

								<div class="form-group row">
									<div class="col-6">
										<label for="municipio">Municipio</label>
										<input type="text" id="municipio" class="form-control">
									</div>
									<div class="col-6 ">
										<label for="poblacion">Poblacion:</label>
										<input class="form-control" type="text" name="poblacion" id="poblacion">
									</div>
									<div class="col-6">
										<label for="colonia">Colonia</label>
										<input type="text" id="colonia" class="form-control">
									</div>
								</div>

								<div class="form-group row">
									<div class="col-6">
										<label for="referencia">Referencia</label>
										<input type="text" id="referencia" class="form-control">
									</div>
									<div class="col-6">
										<label for="clasificacion">Clasificación</label>
										<input type="text" id="clasificacion" class="form-control">
									</div>
								</div>

								<div class="form-group row">
									<div class="col-6">
										<label for="zona">Zona</label>
										<input type="text" id="zona" class="form-control">
									</div>
									<div class="col-6">
										<label for="telefono">Teléfono</label>
										<input type="text" id="telefono" class="form-control">
									</div>
								</div>

								<div class="form-group row">
									<div class="col-6">
										<label for="fax">Fax</label>
										<input type="text" id="fax" class="form-control">
									</div>
									<div class="col-6">
										<label for="paginaWeb">Página Web</label>
										<input type="text" id="paginaWeb" class="form-control">
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
						<button type="button" class="btn btn-primary">Guardar</button>
					</div>
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