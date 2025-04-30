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

	$csrf_token  = $_SESSION['csrf_token'];
} else {
	header('Location:../index.php');
}
?>

<!DOCTYPE html>
<html lang="es">

<style>
	.form-row .form-group {
		padding-bottom: 15px;
		/* Ajusta el espacio entre los elementos */
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

	<title>MDConnecta</title>
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

</head>
<body>
	<div class="hero_area">
		<!-- SIDEBAR -->
		<?php include 'sidebar.php'; ?>
		<!-- CONTENT -->
		<section id="content">
			<?php include 'navbar.php'; ?>
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
						<input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo $csrf_token; ?>">
						<div class="order">
							<!-- HTML -->
							<div class="head search-head">
								<h3>Clientes</h3>
								<div class="input-group">
									<i class='bx bx-search search-icon'></i>
									<input
										id="searchTerm"
										class="search-input"
										type="text"
										placeholder="Buscar clientes..."
										onkeyup="doSearch()" />
								</div>
							</div>
							<table id="clientes">
								<thead>
									<tr>
										<th>Clave</th>
										<th>Nombre</th>
										<th>Calle</th>
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
			<div class="modal-content" style="max-height: 90vh; overflow-y: auto;">
				<div class="modal-header">
					<h5 class="modal-title">Clientes</h5>
					<i class="cerrar-modal bx bx-x"
						style="position: absolute; top: 10px; right: 10px; font-size: 34px; " data-dismiss="modal"
						aria-label="Close"></i>
				</div>
				<!-- ESTATICO -->
				<div class="modal-static">
					<div class="form-group row">
						<div class="col-4">
							<label for="clave" style="margin-right: 5px;">Clave:</label>
							<input class="input-mo form-control" type="text" id="clave" readonly1>
						</div>
						<div class="col-4">
							<label for="nombre" style="margin-right: 5px;">Nombre:</label>
							<input class="input-mt form-control" type="text" name="nombre" id="nombre" readonly1>
						</div>
					</div>

					<div class="form-group row">
						<div class="col-4">
							<label for="estatus" style="margin-right: 5px;">Estatus:</label>
							<input class="input-mo form-control text-center" type="text" id="estatus" readonly1>
						</div>
						<div class="col-4">
							<label for="saldo" style="margin-right: 5px;">Saldo:</label>
							<input type="text" id="saldo" class="input-mt form-control text-end" readonly1>
						</div>
					</div>
				</div>

				<div class="modal-body">
					<!-- Navegacion Pestañas -->
					<div class="btn-group mb-3" role="group">
						<button class="btn  btn-secondary" id="btnDatosGenerales">Datos Generales</button>
						<button class="btn  btn-secondary" id="btnDatosVentas">Datos Ventas</button>
					</div>
					<br>

					<!-- Datos Generales -->
					<div id="formDatosGenerales" style="display: block;">
						<h5 class="section-title">Datos Generales</h5>
						<div class="tab-pane fade show active" role="tabpanel">
							<!-- Datos Generales -->
							<div class="tab-pane fade show active" id="datosGenerales" role="tabpanel">
								<div class="form-group row">
									<div class="col-4">
										<label for="rfc">RFC</label>
										<input type="text" id="rfc" class="input-m" readonly1>
									</div>
									<div class="col-4">
										<label for="regimenFiscal">Régimen Fiscal</label>
										<input type="text" id="regimenFiscal" class="form-control" readonly1>
									</div>
									<div class="col-4">
										<label for="curp">C.U.R.P.</label>
										<input type="text" id="curp" class="form-control" readonly1>
									</div>
								</div>

								<div class="form-group row">
									<div class="col-6">
										<label for="calle">Calle</label>
										<input type="text" id="calle" class="form-control" readonly1>
									</div>
									<div class="col-3">
										<label for="numE">Num. Ext</label>
										<input type="text" id="numE" class="form-control" readonly1>
									</div>
									<div class="col-3">
										<label for="numI">Num. Int</label>
										<input type="text" id="numI" class="form-control" readonly1>
									</div>
								</div>

								<div class="form-group row">
									<div class="col-4">
										<label for="entreCalle">Entre Calle</label>
										<input type="text" id="entreCalle" class="form-control" readonly1>
									</div>
									<div class="col-4">
										<label for="yCalle">Y Calle</label>
										<input type="text" id="yCalle" class="form-control" readonly1>
									</div>
									<div class="col-4">
										<label for="pais">País</label>
										<input type="text" id="pais" class="form-control" readonly1>
									</div>
								</div>

								<div class="form-group row">
									<div class="col-4">
										<label for="nacionalidad">Nacionalidad</label>
										<input type="text" id="nacionalidad" class="form-control" readonly1>
									</div>
									<div class="col-4">
										<label for="estado">Estado</label>
										<input type="text" id="estado" class="form-control" readonly1>
									</div>
									<div class="col-4">
										<label for="codigoPostal">Código Postal</label>
										<input type="text" id="codigoPostal" class="form-control" readonly1>
									</div>
								</div>

								<div class="form-group row">
									<div class="col-6">
										<label for="municipio">Municipio</label>
										<input type="text" id="municipio" class="form-control" readonly1>
									</div>
									<div class="col-6 ">
										<label for="poblacion">Poblacion:</label>
										<input class="form-control" type="text" name="poblacion" id="poblacion"
											readonly1>
									</div>
									<div class="col-6">
										<label for="colonia">Colonia</label>
										<input type="text" id="colonia" class="form-control" readonly1>
									</div>
								</div>

								<div class="form-group row">
									<div class="col-6">
										<label for="referencia">Referencia</label>
										<input type="text" id="referencia" class="form-control" readonly1>
									</div>
									<div class="col-6">
										<label for="clasificacion">Clasificación</label>
										<input type="text" id="clasificacion" class="form-control" readonly1>
									</div>
								</div>

								<div class="form-group row">
									<div class="col-6">
										<label for="zona">Zona</label>
										<input type="text" id="zona" class="form-control" readonly1>
									</div>
									<div class="col-6">
										<label for="telefono">Teléfono</label>
										<input type="text" id="telefono" class="form-control" readonly1>
									</div>
								</div>

								<div class="form-group row">
									<div class="col-6">
										<label for="fax">Fax</label>
										<input type="text" id="fax" class="form-control" readonly1>
									</div>
									<div class="col-6">
										<label for="paginaWeb">Página Web</label>
										<input type="text" id="paginaWeb" class="form-control" readonly1>
									</div>
								</div>
							</div>
						</div>
					</div>


					<!-- FIN DATOS GENERALES -->
					<div id="formDatosVentas" style="display: none;">
						<h5 class="section-title">Datos Ventas</h5>
						<div class="form-group row">
							<!-- Contenedor de información de crédito -->
							<div class="col-md-6">
								<div class="container mt-4"
									style="border: 2px solid rgb(240, 240, 240); padding: 20px; background-color: #f9f9f9;">
									<h6 class="mb-4">Información Crédito</h6>
									<form>
										<div class="form-row">
											<div class="form-group col-md-6">
												<div class="form-check">
													<input type="checkbox" class="form-check-input" id="manejoCredito" disabled>
													<label class="form-check-label" for="manejoCredito">Manejo de Crédito</label>
												</div>
											</div>

											<div class="form-group col-md-6">
												<label for="diaRevision">Día de Revisión:</label>
												<input type="number" class="input-fo" id="diaRevision" disabled
													readonly1>
											</div>
										</div>

										<div class="form-row mb-3">
											<div class="form-group col-md-6">
												<label for="diasCredito">Días de Crédito</label>
												<input type="number" class="input-fo" id="diasCredito" style="text-align: right;" disabled
													readonly1>
											</div>

											<div class="form-group col-md-6">
												<label for="diaPago">Día de Pago</label>
												<input type="text" class="input-fo" id="diaPago" disabled readonly1>
											</div>
										</div>

										<div class="form-row mb-3">
											<div class="form-group col-md-6">
												<label for="limiteCredito">Límite de Crédito</label>
												<input type="text" class="input-fo" id="limiteCredito" disabled
													readonly1>
											</div>

											<div class="form-group col-md-6">
												<label for="saldoVentas">Saldo</label>
												<input type="text" class="input-fo" id="saldoVentas" style="margin-right: 5px; text-align: right;" readonly1>
											</div>
										</div>

										<div class="form-group">
											<label for="metodoPago">Método de Pago</label>
											<input type="text" class="form-control" id="metodoPago" readonly1>
										</div>

										<div class="form-group">
											<label for="numeroCuenta">Número de Cuenta</label>
											<input type="text" class="form-control" id="numeroCuenta" readonly1>
										</div>
									</form>
								</div>
							</div>

							<!-- Campos fuera del contenedor  -->
							<div class="col-md-6">
								<div class="form-row mb-3">
									<div class="form-group col-md-6">
										<label for="vendedor">Clave Vendedor:</label>
										<div class="input-group" style="margin-right: 20px;">
											<input type="text" class="input-fo" id="vendedor" style="text-align: right;" readonly1>
											<!-- <div class="input-group-append">
												<span class="input-group-text"></span>
											</div> -->
										</div>
									</div>
									<div class="form-group col-md-6">
										<label for="vendedor">Vendedor:</label>
										<div class="input-group" style="margin-right: 20px;">
											<input type="text" class="input-fo" id="vendedorNombre" style="text-align: right;" readonly1>
											<!-- <div class="input-group-append">
												<span class="input-group-text"></span>
											</div> -->
										</div>
									</div>

								</div>

								<div class="form-row mb-3">
									<div class="form-group col-md-6">
										<label for="descuento">% Descuento:</label>
										<div class="input-group" style="margin-right: 20px;">
											<input type="number" class="input-fo" id="descuento" style="text-align: right;" readonly1>
											<!-- <div class="input-group-append">
												<span class="input-group-text">%</span>
											</div> -->
										</div>
									</div>
									<div class="form-group col-md-6">
										<label for="listaPrecios">Lista de Precios:</label>
										<div class="input-group" style="margin-right: 20px;">
											<input type="text" class="input-fo" id="listaPrecios" style="text-align: right;" readonly1>
										</div>
									</div>
								</div>
							</div>


						</div>
					</div>

				</div>
				<div class="modal-footer">
					<button type="button" class="cerrar-modal btn-cancel">Cancelar</button>
				</div>
			</div>
		</div>
	</div>

	<script>
		// Selecciona el checkbox y los campos de entrada
		const manejoCreditoCheckbox = document.getElementById('manejoCredito');
		const inputFields = ['diasCredito', 'limiteCredito', 'diaRevision', 'diaPago'].map(id => document.getElementById(id));

		// Añade un evento para activar/desactivar los campos
		manejoCreditoCheckbox.addEventListener('change', () => {
			const isChecked = manejoCreditoCheckbox.checked;

			// Activa o desactiva los campos según el estado del checkbox
			inputFields.forEach(input => {
				input.disabled = !isChecked;
			});
		});
	</script>


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