<?php
session_start();
if (isset($_SESSION['usuario'])) {
  $nombreUsuario = $_SESSION['usuario']["nombre"];
  $tipoUsuario = $_SESSION['usuario']["tipoUsuario"];
  $correo = $_SESSION['usuario']["correo"];

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
  <!-- Script -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <title>MDConnecta</title>
</head>

<body>

  <div class="">
    <!-- SIDEBAR -->
    <?php include 'sidebar.php'; ?>
    <!-- CONTENT -->
    <section id="content">
      <!-- NAVBAR -->
      <?php include 'navbar.php'; ?>
      <!-- MAIN -->
      <main class="text-center ">


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
              <!-- <i class='bx bx-filter' > Campos Obligatorios </i> -->
              <a class=''> Campos Obligatorios * </a>

            </div>
            <form onsubmit="return validateForm()">
              <div class="form-row">
              <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="text" name="id" id="id" value="<?php echo $idEmpresa ?>" hidden>
                <label for="noEmpresa">No. Empresa:</label>
                <input class="input-small" type="text" name="noEmpresa" id="noEmpresa" value="" readonly>
              </div>

              <div class="form-row">
                <!-- <a class='bx bx-message-rounded-error'></a> -->
                <label for="razonSocial">Razón Social: <a class='bx'> *</a></label>
                <input class="input-m" type="text" name="razonSocial" id="razonSocial" values="" readonly>
              </div>

              <div class="form-row">
                <!-- <a class='bx bx-message-rounded-error'></a> -->
                <label for="rfc">RFC:<a class='bx'> *</a></label>
                <input class="input-m" type="text" name="rfc" id="rfc" placeholder="RFC" value="">
              </div>

              <div class="form-row">
                <!-- <a class='bx bx-message-rounded-error'></a> -->
                <label for="regimenFiscal">Régimen Fiscal: <a class='bx'>*</a></label>
                <select name="regimenFiscal" id="regimenFiscal" placeholder="Selecciona opcion" value="">

                  <option>626.- Regimen Simplificado de Confianza</option>
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
                <!-- <a class='bx bx-message-rounded-error'></a> -->
                <label for="cp">Codigo Postal: <a class='bx'> *</a></label>
                <input class="input-m" type="text" name="codigoPostal" id="codigoPostal" placeholder="Codigo Postal">
              </div>

              <div class="form-row">
                <label for="poblacion">Población:</label>
                <input class="input-m" type="text" name="poblacion" id="poblacion" placeholder="Poblacion">
                <button class='bx bx-help-circle' id="Ayuda"></button>
              </div>

              <div class="form-buttons">
                <button type="submit" class="btn-save" id="confirmarDatos">Guardar</button>
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
      informaEmpresa();  // Llamada a la función cuando la página de la empresa se ha cargado.
    });
  </script>
  <script src="JS/menu.js"></script>
  <script src="JS/app.js"></script>
  <script src="JS/script.js"></script>

</body>

</html>