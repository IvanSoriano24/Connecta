<?php
//$tipoUsuario = 2;
session_start();
if (isset($_SESSION['usuario'])) {
  $nombreUsuario = $_SESSION['usuario']["nombre"];
  $tipoUsuario = $_SESSION['usuario']["tipoUsuario"];

  //$empresa = $_SESSION['empresa']['id'];

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
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
	<!-- Boxicons -->
	<link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>

	<!-- My CSS -->
	<link rel="stylesheet" href="CSS/style.css">
	<link rel="stylesheet" href="CSS/style1.css">

	<title>AdminHub</title>
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


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
				<a href="#">
					<i class='bx bxs-dashboard' ></i>
					<span class="text">Inicio</span>
				</a>
			</li>
			<li>
				<a href="#">
					<i class='bx bxs-shopping-bag-alt' ></i>
					<span class="text">Mis pedidos</span>
				</a>
			</li>
			<li>
				<a href="#">
					<i class='bx bxs-doughnut-chart' ></i>
					<span class="text">Datos</span>
				</a>
			</li>
			<li>
				<a href="#">
					<i class='bx bxs-message-dots' ></i>
					<span class="text">Mensajes</span>
				</a>
			</li>
			<li>
				<a href="#">
					<i class='bx bxs-group' ></i>
					<span class="text">Clientes</span>
				</a>
			</li>
		</ul>
		<ul class="side-menu">
			<li>
				<a href="#">
					<i class='bx bxs-cog' ></i>
					<span class="text">Configuracion</span>
				</a>
			</li>
			<li>
				<a href="#" class="logout">
					<i class='bx bxs-log-out-circle' ></i>
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
		
			<!--
			<div class="head-title">
				<div class="left">
					<h1>Dashboard</h1>
					<ul class="breadcrumb">
						<li>
							<a href="#">Dashboard</a>
						</li>
						<li><i class='bx bx-chevron-right' ></i></li>
						<li>
							<a class="active" href="#">Home</a>
						</li>
					</ul>
				</div>
				<a href="#" class="btn-download">
					<i class='bx bxs-cloud-download' ></i>
					<span class="text">Download PDF</span>
				</a>
			</div>

			<ul class="box-info">
				<li>
					<i class='bx bxs-calendar-check' ></i>
					<span class="text">
						<h3>1020</h3>
						<p>New Order</p>
					</span>
				</li>
				<li>
					<i class='bx bxs-group' ></i>
					<span class="text">
						<h3>2834</h3>
						<p>Visitors</p>
					</span>
				</li>
				<li>
					<i class='bx bxs-dollar-circle' ></i>
					<span class="text">
						<h3>$2543</h3>
						<p>Total Sales</p>
					</span>
				</li>
			</ul>
			 MAIN -->

			<!-- 
			<div class="table-data">
				<div class="order">
					<div class="head">
						<h3>Recent Orders</h3>
						<i class='bx bx-search' ></i>
						<i class='bx bx-filter' ></i>
					</div>
					<table>
						<thead>
							<tr>
								<th>User</th>
								<th>Date Order</th>
								<th>Status</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>
									<img src="img/people.png">
									<p>John Doe</p>
								</td>
								<td>01-10-2021</td>
								<td><span class="status completed">Completed</span></td>
							</tr>
							<tr>
								<td>
									<img src="img/people.png">
									<p>John Doe</p>
								</td>
								<td>01-10-2021</td>
								<td><span class="status pending">Pending</span></td>
							</tr>
							<tr>
								<td>
									<img src="img/people.png">
									<p>John Doe</p>
								</td>
								<td>01-10-2021</td>
								<td><span class="status process">Process</span></td>
							</tr>
							<tr>
								<td>
									<img src="img/people.png">
									<p>John Doe</p>
								</td>
								<td>01-10-2021</td>
								<td><span class="status pending">Pending</span></td>
							</tr>
							<tr>
								<td>
									<img src="img/people.png">
									<p>John Doe</p>
								</td>
								<td>01-10-2021</td>
								<td><span class="status completed">Completed</span></td>
							</tr>
						</tbody>
					</table>
				</div>
				<div class="todo">
					<div class="head">
						<h3>Todos</h3>
						<i class='bx bx-plus' ></i>
						<i class='bx bx-filter' ></i>
					</div>
					<ul class="todo-list">
						<li class="completed">
							<p>Todo List</p>
							<i class='bx bx-dots-vertical-rounded' ></i>
						</li>
						<li class="completed">
							<p>Todo List</p>
							<i class='bx bx-dots-vertical-rounded' ></i>
						</li>
						<li class="not-completed">
							<p>Todo List</p>
							<i class='bx bx-dots-vertical-rounded' ></i>
						</li>
						<li class="completed">
							<p>Todo List</p>
							<i class='bx bx-dots-vertical-rounded' ></i>
						</li>
						<li class="not-completed">
							<p>Todo List</p>
							<i class='bx bx-dots-vertical-rounded' ></i>
						</li>
					</ul>
				</div>
			</div>

			 -->
		</main>
		<!-- MAIN -->
	</section>
	<!-- CONTENT -->
</div>

	
	



    <div class="modal fade" id="empresaModal" tabindex="-1" aria-labelledby="empresaModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
       <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="text-center mb-4">
                 <h1 class="display-4 text-primary txth1">Bienvenid@</h1>
                 </div>
             <div class="modal-body">
              <h2 class="card-title text-center txth2">Selecciona Empresa</h2>
                <select class="form-select" id="empresaSelect" name="empresaSelect">
                 <option value="" selected disabled class="txt">Selecciona una empresa</option>
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
      cargarEmpresa();
    });
    document.addEventListener('DOMContentLoaded', function() {
      const empresaModal = new bootstrap.Modal(document.getElementById('empresaModal'));
      empresaModal.show();
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

            alert(`Has seleccionado: ${noEmpresa} - ${razonSocial}`);

            const modal = bootstrap.Modal.getInstance(document.getElementById('empresaModal'));
            modal.hide();

            // Guardar los datos en la variable global
            idEmpresarial = {
                id: empresaSeleccionada,
                noEmpresa: noEmpresa,
                razonSocial: razonSocial
            };
          }
      } else {
        alert('Por favor, selecciona una empresa.');
      }
    });
  </script>


    <script src="JS/menu.js"></script>
    <script src="JS/app.js"></script> 
	<script src="JS/script.js"></script>
</body>
</html>