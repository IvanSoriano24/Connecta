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
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <title>AdminHub</title>





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
          <a href="Ventas.php">
            <i class='bx bxs-shopping-bag-alt'></i>
            <span class="text">Ventas</span>
          </a>
        </li>
        <li>
          <a href="Productos.php">
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
      <nav style="display: flex; justify-content: flex-end;">

        <section id="navbar">
          <a class="navbar-brand" href="#"></a>
          <!-- Botón alineado a la derecha -->
          <button class="btn btn-secondary" style="background-color: #49A1DF; color: white;">
            <i class='bx bxs-user'></i>
            <a class="brand" href="Usuarios.php" style="color: white;">Usuarios</a>
          </button>
        </section>
      </nav>
      <!-- fin NAVBAR -->

      <!-- MAIN -->

      <main class="text-center ">


        <div class="head-title">
          <div class="left">
            <h1>SAE</h1>
            <ul class="breadcrumb">
              <li>
                <a href="Dashboard.php">Inicio</a>
              </li>
              <li><i class='bx bx-chevron-right'></i></li>
              <li>
                <a href="#">SAE</a>
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
            <form action="">
              <div class="form-row">
                <label for="noEmpresa">No. Empresa:</label>
                <input class="input-small" type="text" name="noEmpresa" id="noEmpresa" value="<?php echo $noEmpresa ?>"
                  readonly>
                <input class="input-small" type="text" name="idDocumento" id="idDocumento" value="" hidden>
              </div>
              <div class="form-row">
                <label for="host">Host:</label>
                <input class="input-mt" type="text" name="host" id="host" value="">
              </div>
              <div class="form-row">
                <label for="puerto">Puerto:</label>
                <input class="input-small" type="text" name="puerto" id="puerto" value="">
              </div>
              <div class="form-row">
                <label for="usuarioSae">Usuario:</label>
                <input class="input-mt" type="text" name="usuarioSae" id="usuarioSae">
                <label for="txt">Password:</label>
                <input class="input-mt" type="password" name="password" id="password">
                <div class="password-container">
                  <!-- <button type="button" class="show-password">Mostrar</button> -->
                </div>
              </div>
              <div class="form-row">
              </div>
              <div class="form-row">
                <label for="nombreBase">Nombre Base Datos:</label>
                <input class="input-mt" type="text" name="nombreBase" id="nombreBase" value="">
              </div>
              <div class="form-buttons">
                <button type="button" class="btn-probarco" id="probarConexion">Probar Conexion</button>
                <button type="submit" class="btn-save" id="confirmarConexion">Guardar</button>
                <button type="button" class="btn-cancel">Cancelar</button>
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
      informaSae();
    });
  </script>
  <script>
    const numeroEmpresa = '<?php echo $noEmpresa ?>';  // Este número debería ser el que obtienes de la sesión o base de datos.
    const inputNombreBase = document.getElementById('nombreBase');
    inputNombreBase.addEventListener('blur', function () {
      if (this.value.trim() !== '') {
        // Verifica si el número de empresa ya está al final
        if (!this.value.endsWith(numeroEmpresa)) {
          this.value = this.value + numeroEmpresa;  // Agrega el número de empresa al final
        }
      }
    });
  </script>
  <script src="JS/menu.js"></script>
  <script src="JS/app.js"></script>
  <script src="JS/script.js"></script>

</body>

</html>