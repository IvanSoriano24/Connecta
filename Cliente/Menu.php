<?php
session_start();/*

if (isset($_SESSION['usuario'])) {
  if($_SESSION['usuario']['tipoUsuario'] == 'CLIENTE'){
    header('Location:Dashboard.php');
    exit();
  }
  $nombreUsuario = $_SESSION['usuario']["nombre"];
  $tipoUsuario = $_SESSION['usuario']["tipoUsuario"];

} else {
  header('Location:../index.php');
}*/
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
  <link rel="stylesheet" href="css/estilosmenu.css"> 

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
    <!--MENU  HEADER-->
    <nav class="menu">
      <section class="menu__container">
      <a href=""><img src="SRC/imagen.png" alt="Logo" id="logop"> </a>

        <ul class="menu__links">
          <li class="menu__item">
            <a href="cliente.html" class="menu__link txt">Inicio</a>
          </li>
          <li class="menu__item">
            <a href="productos.html" class="menu__link txt">Credito</a>
          </li>
          <li class="menu__item">
            <a href="pedidos.php" class="menu__link txt">Pedidos</a>
          </li>
          <li class="menu__item">
            <a href="pedidos.html" class="menu__link txt">Guias</a>
          </li>
          <li class="menu__item">
            <a class="menu__link txt" id="cerrarSesion" name="cerrarSesion">Cerrar Sesión</a>
          </li>
           <!--
          <li class="menu__item menu__item--show">
            <a href="#" class="menu__link  txt">Configuración <img src="assets/arrow.svg" class="menu__arrow"></a>
            <ul class="menu__nesting">
              <li class="menu__inside">
                <a href="#" class="menu__link menu__link--inside" id="Empresa">Empresa</a>
              </li>
              <li class="menu__inside">
                <a href="#" class="menu__link menu__link--inside" id="SAE">SAE</a>
              </li>  
            </ul> 
          </li>
                  -->
        </ul>

            </div>
          </div>
           <!--
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
           ¿-->
        </nav>
      </div>
    </header> <!-- Final del header section -->
  </div>


    <!-- IMAGEN GRUPO INTERZENDA CENTRADA GRANDE  -->

    <main class="text-center my-5 hero_area">
        <img src="SRC/imagen.png" alt="Logo Principal" width="700">
        <h1 class="mt-4 txth2">GRUPO <br> INTERZENDA</h1>
    </main>

     <!-- Footer -->
    <footer>
        <img src="SRC/imagen.png" class="logomdconnecta" alt="Logo">
    </footer>

   <!--MODAL SELECCION EMPRESA
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
   </div>
     -->

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
</body>
</html>