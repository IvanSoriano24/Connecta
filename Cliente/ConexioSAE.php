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

  <div class="hero_area">
    <?php include 'sidebar.php'; ?>

    <!-- CONTENT -->
    <section id="content">
      <!-- NAVBAR -->
      <?php include 'navbar.php'; ?>
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
                <label for="claveSae">Sae:</label>
                <select class="input-mt" name="claveSae" id="claveSae">
                  <option value="01">1</option>
                  <option value="02">2</option>
                  <option value="03">3</option>
                  <option value="04">4</option>
                  <option value="05">5</option>
                  <option value="06">6</option>
                  <option value="07">7</option>
                  <option value="08">8</option>
                  <option value="09">9</option>
                </select>
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
    const numeroEmpresa = '<?php echo $noEmpresa ?>'; // Este número debería ser el que obtienes de la sesión o base de datos.
    const inputNombreBase = document.getElementById('nombreBase');
    inputNombreBase.addEventListener('blur', function() {
      if (this.value.trim() !== '') {
        const claveSae = document.getElementById('claveSae').value;
        // Verifica si el número de empresa ya está al final
        if (!this.value.endsWith(claveSae)) {
          this.value = this.value + claveSae; // Agrega el número de empresa al final
        }
      }
    });
  </script>
  <script src="JS/menu.js"></script>
  <script src="JS/app.js"></script>
  <script src="JS/script.js"></script>
  <script>
    $(document).ready(function() {
      const claveSae = '<?php echo $claveSae ?>';
      informaSaeInicio(claveSae);
    });
  </script>
</body>

</html>