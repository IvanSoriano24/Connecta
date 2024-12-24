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
session_destroy();*/
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no" />
  <!-- bootstrap css -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link href="css/style1.css" rel="stylesheet" />
  <link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Lato" />
  <link rel="stylesheet" href="CSS/selec.css">

  <title>Menu Principal</title>
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
    <!-- header section strats -->
    <header class="header_section">
      <div class="container-fluid">
        <nav class="navbar navbar-expand-lg custom_nav-container ">
          <a class="navbar-brand" href="Menu.php">
            <div id="divLogo">
              <img src="SRC/imagen.png" alt="Logo" id="logop">
            </div>
            <span>
              <!-- TEXTO LOGO-->
            </span>
          </a>
          <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>

          <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <div class="d-flex mx-auto flex-column flex-lg-row align-items-center">
              <ul class="navbar-nav  ">
                <li class="nav-item active">
                  <a class="nav-link txt" href="cliente.html">Cliente <span class="sr-only"></span></a>
                </li>
                <li class="nav-item">
                  <a class="nav-link txt" href="productos.html">Productos </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link txt" href="pedidos.html">Pedidos </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link txt" id="cerrarSesion">Cerrar Sesion</a>
                </li>
                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle txt" href="#" id="configuracionDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Configuración
                  </a>
                  <ul class="dropdown-menu" aria-labelledby="configuracionDropdown">
                    <li><a class="dropdown-item" id="Empresa">Empresa</a></li>
                    <li><a class="dropdown-item" id="SAE">SAE</a></li>
                  </ul>
                </li>
                <!-- Agregar otro titulo
                <li class="nav-item">
                  <a class="nav-link" href="logo.html"> Logo</a>
                </li>
                   </a>
                 -->

            </div>
          </div>
           <!-- BOTON MDCLOUD /////////////////////////////////////
          <a class="navbar-brand" href="">
            <div id="divPrincipal">
              <img src="SRC/imagen.png" alt="Logo" id="logop">
            </div>
            <span>
          
            </span>
          </a>

          <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
           //////////////////////////////////////////-->
        </nav>
      </div>
    </header> <!-- Final del header section -->
  </div>



    
    

  <div class="modal fade" id="empresaModal" tabindex="-1" aria-labelledby="empresaModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="text-center mb-4">
          <h1 class="display-4 text-primary txth1">Bienvenid@</h1>
          <!-- LA PARTE MOSTRARA SU PERFIL -->
        </div>
        <div class="modal-body">
          <h2 class="card-title text-center txth2">Selecciona Empresa</h2>
          <select class="form-select" id="empresaSelect" name="empresaSelect">
            <option value="" selected disabled class="txt">Selecciona una empresa</option>
          </select>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" id="confirmarEmpresa"> Confirmar</button>
        </div>
      </div>
    </div>
  </div>

  

  <!-- Formumario -->
  <div class="modal fade" id="infoEmpresa" tabindex="-1" aria-labelledby="empresaModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false" style="display:none;">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body">
          <h2 class="card-title text-center">Informacion de Empresa</h2>
          <form action="">
            <label for="text">Id</label>
            <input type="text" name="id" id="id"><br>
            <label for="text">Numero de Empresa</label>
            <input type="text" name="nomEmpresa" id="noEmpresa"><br>
            <label for="text">Razon Social</label>
            <input type="text" name="razonSocial" id="razonSocial"><br>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger close-modal" id="cancelarModal">Cancelar</button>
          <button type="button" class="btn btn-primary" id="confirmarDatos">Confirmar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Conceccion a SAE -->
  <div class="modal fade" id="infoConexion" tabindex="-1" aria-labelledby="empresaModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body">
          <h2 class="card-title text-center">Informacion de Conexion</h2>
          <form>
            <label for="text">host</label>
            <input type="text" name="host" id="host"><br>
            <label for="text">usuario</label>
            <input type="text" name="usuarioSae" id="usuarioSae"><br>
            <label for="text">password</label>
            <input type="password" name="password" id="password"><br>
            <label for="text">Nombre de base de datos</Base></label>
            <input type="text" name="nombreBase" id="nombreBase">
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger" id="cancelarModalSae">Cancelar</button>
          <button type="button" class="btn btn-primary" id="confirmarConexion">Guardar</button>
        </div>
      </div>
    </div>
  </div>


         <!-- IMAGEN GRUPO INTERZENDA CENTRADA GRANDE  -->
  <main class="hero_area">
      <img src="SRC/imagen.png" alt="Logo central" id="main-logo">
        <div class="maintxt text-center"> GRUPO <br> INTERZENDA</div>
  </main>
  


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

          alert(`Has seleccionado:   ${empresaSeleccionada}`);
          const modal = bootstrap.Modal.getInstance(document.getElementById('empresaModal'));
          modal.hide();
          idEmpresarial = {
                    id: empresa.id,
                    noEmpresa: empresa.noEmpresa,
                    razonSocial: empresa.razonSocial
                }
        } else {
          alert('Por favor, selecciona una empresa.');
        }
    });
  </script>


  <script src="JS/menu.js"></script>
</body>

</html>