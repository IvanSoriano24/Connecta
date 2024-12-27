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
            <a href="Pedidos.php" class="menu__link txt">Pedidos</a>
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
           -->
           <div class="menu__hamburguer">
                <img src="assets/menu.svg" class="menu__img">
            </div>
    
           </section> <!-- Final del header section -->
        </nav>
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

  

  

  <!-- Formumario 
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
            <label for="text">RFC:</label>
            <input type="text" name="rfc" id="rfc"><br>
            <label for="text">Poblacion:</label>
            <input type="text" name="poblacion" id="poblacion"><br>
            <label for="text">Regimen Fiscal:</label>
            <input type="text" name="regimenFiscal" id="regimenFiscal"><br>
            <label for="text">Num.Exterior:</label>
            <input type="text" name="numExterior" id="numExterior"><br>
            <label for="text">Num.Inerior:</label>
            <input type="text" name="numInterior" id="numInterior"><br>
            <label for="text">Entre Calle:</label>
            <input type="text" name="entreCalle" id="entreCalle"><br>
            <label for="text">Y Calle:</label>
            <input type="text" name="yCalle" id="yCalle"><br>
            <label for="text">Colonia:</label>
            <input type="text" name="colonia" id="colonia"><br>
            <label for="text">Referencia:</label>
            <input type="text" name="referencia" id="referencia"><br>
            <label for="text">Pais:</label>
            <input type="text" name="pais" id="pais"><br>
            <label for="text">Estado:</label>
            <input type="text" name="estado" id="estado"><br>
            <label for="text">Municipio:</label>
            <input type="text" name="municipio" id="municipio"><br>
            <label for="text">C.P:</label>
            <input type="text" name="cp" id="cp"><br>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger close-modal" id="cancelarModal">Cancelar</button>
          <button type="button" class="btn btn-primary" id="confirmarDatos">Confirmar</button>
        </div>
      </div>
    </div>
  </div> 
  -->

  <!-- Conceccion a SAE 
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
          <button type="button" class="btn btn-info" id="probarConexion">Probar</button>    
          <button type="button" class="btn btn-primary" id="confirmarConexion">Guardar</button>
          <button type="button" class="btn btn-danger" id="cancelarModalSae">Cancelar</button>
        </div>
      </div>
    </div>
  </div>
  -->



  <script src="JS/menu.js"></script>
  <script src="JS/app.js"></script> 
</body>
</html>