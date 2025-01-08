<?php
session_start();
if (isset($_SESSION['usuario'])) {
	if ($_SESSION['usuario']['tipoUsuario'] == 'CLIENTE') {
		header('Location:Menu.php');
		exit();
	}
	$nombreUsuario = $_SESSION['usuario']["usuario"];
	$nombre = $_SESSION['usuario']["nombre"];
	$tipoUsuario = $_SESSION['usuario']["tipoUsuario"];
	/*if ($_SESSION['usuario']['tipoUsuario'] == 'ADMIISTRADOR') {
		header('Location:Dashboard.php');
	}*/
	$mostrarModal = isset($_SESSION['empresa']) ? false : true;
	//$empresa = $_SESSION['empresa']['razonSocial'];
	if (isset($_SESSION['empresa'])) {
		$empresa = $_SESSION['empresa']['razonSocial'];
		$idEmpresa = $_SESSION['empresa']['id'];
		$noEmpresa = $_SESSION['empresa']['noEmpresa'];
		$claveVendedor = $_SESSION['empresa']['claveVendedor'];
	}
} else {
	header('Location:../index.php');
} ?>
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
	<?php include 'sidebar.php'; ?>
		<!-- CONTENT -->
		<section id="content">
            <!-- MAIN -->
            <main class="text-center my-5 hero_area">
                
            </main>
            <!-- MAIN -->
        </section>
        <!-- CONTENT -->
	</div>
	</section>
	<!-- CONTENT -->
	</div>
	<div class="modal fade" id="empresaModal" tabindex="-1" aria-labelledby="empresaModalLabel" aria-hidden="true"
		data-bs-backdrop="static" data-bs-keyboard="false" class="modal <?php echo $mostrarModal ? '' : 'd-none'; ?>">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="text-center mb-4">
					<h1 class="display-4 text-primary txth1">Bienvenido</h1>
					<h2 class="card-title text-center txth2"> <?php echo $tipoUsuario; ?> </h2>
				</div>
				<div class="modal-body">
					<select class="form-select" id="empresaSelect" name="empresaSelect">
						<option value="" selected disabled class="txt">Selecciona una Empresa</option>
					</select>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-primary txt" id="confirmarEmpresa"> Confirmar</button>
				</div>
			</div>
		</div>
		<!-- JS Para la confirmacion empresa -->
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
		<script>
			document.getElementById('empresaModal').addEventListener('shown.bs.modal', function() {
				var usuario = '<?php echo $nombreUsuario; ?>';
				cargarEmpresa(usuario);
			});
			document.addEventListener('DOMContentLoaded', function() {
				const empresaSeleccionada = <?php echo json_encode(isset($_SESSION['empresa']) ? $_SESSION['empresa'] : null); ?>;
				if (empresaSeleccionada === null) {
					const empresaModal = new bootstrap.Modal(document.getElementById('empresaModal'));
					empresaModal.show();
				}
			});
			document.getElementById('confirmarEmpresa').addEventListener('click', function() {
				const empresaSeleccionada = document.getElementById('empresaSelect').value;
				if (empresaSeleccionada != null) {

					const empresaOption = document.querySelector(`#empresaSelect option[value="${empresaSeleccionada}"]`);

					// Verificar que empresaOption no sea null
					if (empresaOption) {
						// Obtener los datos adicionales de la empresa utilizando los atributos data-*
						const noEmpresa = empresaOption.getAttribute('data-no-empresa');
						const razonSocial = empresaOption.getAttribute('data-razon-social');
						const claveVendedor = empresaOption.getAttribute('data-clave-vendedor');

						// Usar SweetAlert en lugar de alert
						Swal.fire({
							title: 'Has seleccionado:',
							text: `${noEmpresa} - ${razonSocial}`,
							icon: 'success'
						}).then(() => {
							seleccionarEmpresa(noEmpresa);
							const modal = bootstrap.Modal.getInstance(document.getElementById('empresaModal'));
							modal.hide();

							// Guardar los datos en la variable global
							idEmpresarial = {
								id: empresaSeleccionada,
								noEmpresa: noEmpresa,
								razonSocial: razonSocial,
								claveVendedor: claveVendedor
							};
							sesionEmpresa(idEmpresarial);
						});
					}
				} else {
					// Usar SweetAlert en lugar de alert
					Swal.fire({
						title: 'Error',
						text: 'Por favor, selecciona una empresa.',
						icon: 'error'
					});
				}
			});
		</script>
		<script src="JS/menu.js"></script>
		<script src="JS/app.js"></script>
		<script src="JS/script.js"></script>
</body>
</html>