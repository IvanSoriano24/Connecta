<?php
session_start();
if (isset($_SESSION['usuario'])) {
  $nombreUsuario = $_SESSION['usuario']["nombre"];
  $tipoUsuario = $_SESSION['usuario']["tipoUsuario"];

  //$empresa = $_SESSION['empresa']['razonSocial'];
  if (isset($_SESSION['empresa'])) {
    $empresa = $_SESSION['empresa']['razonSocial'];
    $idEmpresa = $_SESSION['empresa']['id'];
    $noEmpresa = $_SESSION['empresa']['noEmpresa'];
  }

} else {
  header('Location:../index.php');
}
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




</head>

<body>

  <div class="">


    <!-- SIDEBAR -->
    <section id="sidebar">
      <a href="#" class="brand">
        <i class='bx bxs-cloud'></i>
        <span class="text">MDCloud</span>
      </a>
      <ul class="side-menu top">
        <li class="active">
          <a href="Dashboard.php">
            <i class='bx bxs-dashboard'></i>
            <span class="text">Inicio</span>
          </a>
        </li>
        <li>
          <a href="#">
            <i class='bx bxs-shopping-bag-alt'></i>
            <span class="text">Ventas</span>
          </a>
        </li>
        <li>
          <a href="#">
            <i class='bx bxs-package'></i>
            <span class="text">Productos</span>
          </a>
        </li>
        <li>
          <a href="#">
            <i class='bx bxs-user'></i>
            <span class="text">Mis Clientes</span>
          </a>
        </li>
        <li>
          <a href="#">
            <i class='bx bxs-message-dots'></i>
            <span class="text">Mensajes</span>
          </a>
        </li>
        <li>
          <a href="#">
            <i class='bx bxs-file'></i>
            <span class="text">Reportes</span>
          </a>
        </li>

      </ul>
      <ul class="side-menu">

        <li>
          <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
            <i class='bx bxs-cog'></i>
            <span class="text">Configuración</span>
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="infoEmpresa.php">Información Empresa</a></li>

            <li><a class="dropdown-item" href="ConexioSAE.php">Conexión SAE</a></li>
          </ul>
        </li>


        <li>
          <a href="" class="logout" id="cerrarSesion">
            <i class='bx bxs-log-out-circle'></i>
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


        <div class="head-title">
          <div class="left">
            <h1>Información Empresa</h1>
            <ul class="breadcrumb">
              <li>
                <a href="Dashboard.php">Inicio</a>
              </li>
              <li><i class='bx bx-chevron-right'></i></li>
              <li>
                <a href="#">Información Empresa</a>
              </li>
            </ul>
          </div>

        </div>
        <!-- 
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
-->
        <div class="table-data">
          <div class="order">
            <div class="head">
              <h3></h3>
              <!--<i class='bx bx-search'></i> -->
              <i class='bx bx-filter'></i>
            </div>
            <form onsubmit="return validateForm()">
              <div class="form-row">
                <input type="text" name="id" id="id" value="<?php echo $idEmpresa ?>" hidden>
                <label for="noEmpresa">No. Empresa:</label>
                <input class="input-small" type="text" name="noEmpresa" id="noEmpresa" value="" readonly>
              </div>

              <div class="form-row">
                <label for="razonSocial">Razón Social:</label>
                <input class="input-m" type="text" name="razonSocial" id="razonSocial" values ="" readonly>
              </div>

              <div class="form-row">
                <label for="rfc">RFC:</label>
                <input class="input-m" type="text" name="rfc" id="rfc" placeholder="RFC" value="">
              </div>

              <div class="form-row">
                <label for="regimenFiscal">Régimen Fiscal:</label>
                <select name="regimenFiscal" id="regimenFiscal" placeholder="Selecciona opcion" value="">
                  <option value="*"></option>
                  <option value="1">Opción 1</option>
                  <!-- Agrega más opciones si es necesario -->
                </select>
              </div>

              <div class="form-row">
                <label for="Calle">Calle:</label>
                <input class="input-m" type="text" name="calle" id="calle" placeholder="Calle">
              </div>

              <div class="form-row">
                <label for="numExterior">Num. Exterior:</label>
                <input class="input-small" type="text" name="numExterior" id="numExterior" placeholder="Num. Exterior">
                <label for="numInterior">Num. Interior:</label>
                <input class="input-small" type="text" name="numInterior" id="numInterior" placeholder="Num. Interior">
                <label for="entreCalle">Entre Calle:</label>
                <input class="input-m" type="text" name="entreCalle" id="entreCalle" placeholder="Entre Calle">
              </div>

              <div class="form-row">
                <label for="colonia">Colonia:</label>
                <input class="input-m" type="text" name="colonia" id="colonia" placeholder="Colonia">
                <label for="referencia">Referencia:</label>
                <input class="input-m" type="text" name="referencia" id="referencia" placeholder="Referencia">
              </div>

              <div class="form-row">
                <label for="pais">País:</label>
                <input class="input-m" type="text" name="pais" id="pais" placeholder="País">
                <label for="estado">Estado:</label>
                <input class="input-m" type="text" name="estado" id="estado" placeholder="Estado">
                <label for="municipio">Municipio:</label>
                <input class="input-m" type="text" name="municipio" id="municipio" placeholder="Municipio">
              </div>

              <div class="form-row">
                <label for="cp">Codigo Postal:</label>
                <input class="input-m" type="text" name="codigoPostal" id="codigoPostal" placeholder="Codigo Postal">
              </div>

              <div class="form-row">
                <label for="poblacion">Población:</label>
                <input class="input-m" type="text" name="poblacion" id="poblacion" placeholder="Poblacion">
              </div>

              <div class="form-buttons">
                <button type="submit" class="btn-save" id="confirmarDatos">Guardar</button>
                <button type="button" class="btn-cancel"><a href="Dashboard.php">Cancelar</a></button>
              </div>
            </form>
          </div>
        </div>
  </div>
  </main>
  <!-- MAIN -->
  </section>
  <!-- CONTENT -->
  </div>
  </section>
  <!-- CONTENT -->

  <!-- JS Para la confirmacion empresa -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    $(document).ready(function () {
    informaEmpresa();  // Llamada a la función cuando la página de la empresa se ha cargado.
  });
  </script>
  <script src="JS/menu.js"></script>
  <script src="JS/app.js"></script>
  <script src="JS/script.js"></script>

</body>

</html>








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