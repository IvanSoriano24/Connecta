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
	$correo = $_SESSION['usuario']["correo"];
	if ($_SESSION['usuario']['tipoUsuario'] == 'ADMIISTRADOR') {
		header('Location:Dashboard.php');
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
	<title>MDConnecta</title>
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
			<?php include 'navbar.php'; ?>
			<!-- MAIN -->
			<main class="text-center my-5 hero_area">
				<!--<form method="POST" action="../Servidor/PHP/whatsappp.php">
			<button type="submit">Realizar Pedido</button>
			</form>-->
			<?php
				/*echo $contrasena;
				if($contrasena === ""){
					echo "Es vacio";
				}else{
					echo "No es vacio";
				}*/
				
			?>
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
                <h2 class="card-title text-center txth2"><?php echo $tipoUsuario; ?></h2>
            </div>
            <div class="modal-body">
                <select class="form-select" id="empresaSelect" name="empresaSelect">
                    <option value="" selected disabled class="txt">Selecciona una Empresa</option>
                </select>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-danger" id="cerrarSesionModal">Cerrar Sesion</button>
                <button type="button" class="btn btn-primary txt" id="confirmarEmpresa">Confirmar</button>
            </div>
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

				if (!empresaSeleccionada) {
					Swal.fire({
						title: 'Error',
						text: 'Por favor, selecciona una empresa.',
						icon: 'error'
					});
					return;
				}

				const empresaOption = document.querySelector(`#empresaSelect option[value="${empresaSeleccionada}"]`);

				if (!empresaOption) {
					Swal.fire({
						title: 'Error',
						text: 'No se pudo obtener la información de la empresa.',
						icon: 'error'
					});
					return;
				}

				const noEmpresa = empresaOption.getAttribute('data-no-empresa');
				const razonSocial = empresaOption.getAttribute('data-razon-social');
				const claveUsuario = empresaOption.getAttribute('data-clave-vendedor');
				const claveSae = empresaOption.getAttribute('data-clave-sae');
				const contrasena = empresaOption.getAttribute('data-contrasena');
				// Verificar en PHP si la empresa tiene conexión a SAE
				fetch('../Servidor/PHP/sae.php', {
						method: 'POST',
						headers: {
							'Content-Type': 'application/json'
						},
						body: JSON.stringify({
							action: 'verificar',
							claveSae: claveSae
						})
					})
					.then(response => response.json())
					.then(response => {
						if (response.success && response.tieneConexion) {
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
									claveUsuario: claveUsuario,
									claveSae: claveSae,
									contrasena: contrasena
								};
								
								// Llamar la función sesionEmpresa para registrar sesión
								sesionEmpresa(idEmpresarial);

							});

						} else {
							Swal.fire({
								title: 'Error',
								text: 'La empresa seleccionada no tiene conexión a SAE.',
								icon: 'error'
							});
							Swal.fire({
								title: 'Error',
								text: 'La empresa seleccionada no tiene conexión a SAE.',
								icon: 'error'
							}).then(() => { // Espera a que el usuario cierre la alerta antes de ejecutar el código siguiente
								var tipoUsuario = '<?php echo $tipoUsuario ?>';
								if (tipoUsuario == 'ADMINISTRADOR') {
									seleccionarEmpresa(noEmpresa);
									const modal = bootstrap.Modal.getInstance(document.getElementById('empresaModal'));
									modal.hide();

									// Guardar los datos en la variable global
									idEmpresarial = {
										id: empresaSeleccionada,
										noEmpresa: noEmpresa,
										razonSocial: razonSocial,
										claveUsuario: claveUsuario,
										claveSae: claveSae,
										contrasena: contrasena
									};

									// Llamar la función sesionEmpresa para registrar sesión
									sesionNoEmpresa(idEmpresarial);

									// Redirigir a la página de creación de conexión después de que el usuario cierre la alerta
									window.location.href = "crearConexionSae.php";
									//window.location.reload();
								}
							});
						}
					})
					.catch(error => {
						console.error('Error al procesar la solicitud:', error);
						Swal.fire({
							title: 'Error',
							text: 'No se pudo verificar la conexión a SAE.',
							icon: 'error'
						});
					});
			});
		</script>
		<script>
			function sesionNoEmpresa(idEmpresarial) {
				var id = idEmpresarial.id;
				var noEmpresa = idEmpresarial.noEmpresa;
				var razonSocial = idEmpresarial.razonSocial;
				var claveUsuario = idEmpresarial.claveUsuario;
				var claveSae = idEmpresarial.claveSae;
				var contrasena = idEmpresarial.contrasena;
				$.post('../Servidor/PHP/empresas.php', {
					action: 'sesion',
					id: id,
					noEmpresa: noEmpresa,
					razonSocial: razonSocial,
					claveUsuario: claveUsuario,
					claveSae: claveSae,
					contrasena: contrasena
				}, function(response) {
					if (response.success) {
						if (response.data && response.data.id && response.data.noEmpresa && response.data.razonSocial) {
							console.log(response.data);
							// alert(response.data);
						} else {
							alert(response.message || 'Error al guardar la sesión de empresa.');
						}
					}
				}).fail(function(jqXHR, textStatus, errorThrown) {
					console.log("Error en la solicitud: " + textStatus + ", " + errorThrown);
					alert('Error al comunicar con el servidor.');
				});
			}
		</script>
		<script src="JS/menu.js"></script>
		<script src="JS/app.js"></script>
		<script src="JS/script.js"></script>
		<script>
			var correo = '<?php echo $correo ?>';
			var contrasena = '<?php echo $contrasena ?>';

			//console.log(correo);
			//console.log(contrasena);
		</script>
</body>

</html>