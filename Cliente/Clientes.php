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
		<div class="modal-dialog modal-lg" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Agregar Usuario</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close" id="cerrarModal">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<form class="form-container" onsubmit="return validateForm()">
					<!-- Primera fila -->
					<div>
						<label for="factura">Clave: </label>
						<input type="text">
						<label for="numero">Nombre</label>
						<input class="input-m" type="text" name="numero" id="numero">
						<label for="fecha">Estatus </label>
						<button value="Activo"></button>
						<label for="saldo">Saldo</label>
						<input type="text">
					</div>
					<!-- Segunda fila -->
					<div>
						<label for="rfc">RFC <a class='bx'></a></label>
						<input class="input-mt" type="text" name="rfc" id="rfc">
						<label for="nombre">Calle <a class='bx'> *</a></label>
						<input class="input-mt" type="text" name="nombre" id="nombre">
						<label for="nombre">Num. ext</label>
						<input class="input-mt" type="text" name="nombre" id="nombre">
					</div>
					<!-- Tercera fila -->
					<div>
						<label for="calle">Regimen fiscal </label>
						<input class="input-mt" type="text" name="calle" id="calle">
						<label for="numE">Num. int. </label>
						<input class="input-mt" type="text" name="numE" id="numE">
					</div>
					<!-- Cuarta fila -->
					<div>
						<label for="colonia">C.U.R.P.:</label>
						<input class="input-mt" type="text" name="colonia" id="colonia">
						<label for="descuento">Entre Calle </label>
						<input class="input-mt" type="text" name="descuento" id="descuento">
					</div>
					<!-- Quinta fila -->
					<div>
						<label for="codigoPostal">Y calle:<a class='bx'>*</a></label>
						<input class="input-mt" type="text" name="codigoPostal" id="codigoPostal">
						<label for="poblacion">Nacionalidad:</label>
						<input class="input-mt" type="text" name="poblacion" id="poblacion">
						<label for="pais">Estado: <a class='bx'>*</a></label>
						<input class="input-mt" type="text" name="pais" id="pais">
						<label for="pais">Poblacion: <a class='bx'>*</a></label>
						<input class="input-mt" type="text" name="pais" id="pais">
					</div>
					<!-- Sexta fila -->
					<div>
						<label for="regimenFiscal">Pais: <a class='bx'> *</a></label>
						<input class="input-m" type="text" name="regimenFiscal" id="regimenFiscal">
						<label for="entrega">Codigo Postal </label>
						<input class="input-mt" type="date" name="entrega" id="entrega">
						<label for="vendedor">Municipio: </label>
						<input class="input-mt" type="text" name="vendedor" id="vendedor">
						<label for="vendedor">Colonia: </label>
						<input class="input-mt" type="text" name="vendedor" id="vendedor">
					</div>
					<!-- Septima Fila -->
					<div>
						<label for="condicion">Referencia </label>
						<input class="input-mt" type="text" name="condicion" id="condicion">
					</div>
					<!-- Octava Fila -->
					<div>
						<label for="enviar">Clasificacion</label>
						<input class="input-mt" type="text" name="enviar" id="enviar">

						<label for="almacen">Telefono </label>
						<input class="input-mt" type="text" name="almacen" id="almacen"
							style="background-color: #e0e0e0; margin-left: 10px;" value="1" readonly>

					</div>
					<!-- Novena Fila -->
					<div>
						<label for="destinatario">Zona </label>
						<input class="input-mt" type="text" name="destinatario" id="destinatario"
							style="background-color: #e0e0e0; margin-left: 10px;" value="" readonly>
						<label for="enviar">Fax</label>
						<input class="input-mt" type="text" name="enviar" id="enviar">l>
					</div>
					<div>
						<label for="enviar">Pagina Web</label>
						<input class="input-mt" type="text" name="enviar" id="enviar">
					</div>
					<!-- Sección de botones -->
					<div class="form-buttons">
						<button type="submit" class="btn-save" id="guardarFactura">Guardar</button>
						<button type="button" class="btn-cancel">Cancelar</button>
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
			var empresa = '<?php //echo $noEmpresa ?>'
			console.log(empresa);
		</script>	
		-->