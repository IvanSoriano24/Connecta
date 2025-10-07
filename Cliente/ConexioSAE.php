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

    <script src="JS/sideBar.js?n=1"></script>

  <!-- My CSS -->
  <link rel="stylesheet" href="CSS/style.css">

  <link rel="stylesheet" href="CSS/selec.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- Titulo y Logo -->
  <title>MDConnecta</title>
  <link rel="icon" href="SRC/logoMDConecta.png" />
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
            </div>
          <!-- Formulario para la Coexion -->
            <form action="">
              <div class="form-row">
                <label for="noEmpresa">No. Empresa:</label>
                <input class="input-small" type="text" name="noEmpresa" id="noEmpresa" value="<?php echo $noEmpresa ?>"
                  readonly>
                <input class="input-small" type="text" name="idDocumento" id="idDocumento" value="" hidden>
                <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo $csrf_token; ?>">
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
                  <button type="button" class="show-password" id="togglePassword">Mostrar</button>
                </div>
              </div>
              <div class="form-row">
              </div>
              <div class="form-row">
                <label for="nombreBase">Nombre Base Datos:</label>
                <input class="input-mt" type="text" name="nombreBase" id="nombreBase" value="">
                <div id="divClaveBancaria" class="form-row" hidden>
                  <label for="txt">Clave Bancaria:</label>
                  <input class="input-mt" type="text" name="claveBancaria" id="claveBancaria" readonly>
                </div>
              </div>
              <div class="form-row">
                <label for="nombreBase">Nombre Banco:</label>
                <input class="input-mt" type="text" name="nombreBanco" id="nombreBanco" value="">
              </div>
              <!-- Botones (revisar el archivo menu.js) -->
              <div class="form-buttons">
                <!-- Boton para probar conexion -->
                <button type="button" class="btn-probarco" id="probarConexion">Probar Conexion</button>
                <!-- Boton para guardar conexion -->
                <button type="button" class="btn-save" id="confirmarConexion">Guardar</button>
                <!-- Boton para cancelar -->
                <button type="button" class="btn-cancel" onclick="window.location.href='Dashboard.php';">Cancelar</button>
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

  <!-- JS Para el funcionamiento del sistema -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="JS/menu.js?n=1"></script>
  <script src="JS/app.js?n=1"></script>
  <script src="JS/script.js?n=1"></script>
  <!-- Funcion para mostrar la contraseña -->
  <script>
    document.getElementById("togglePassword").addEventListener("click", function() {
      let passwordInput = document.getElementById("password");

      if (passwordInput.type === "password") {
        passwordInput.type = "text";
      } else {
        passwordInput.type = "password";
      }
    });
  </script>
  <!-- Funcion para mostrar los datos de conexion al cargar la pagina -->
  <script>
    $(document).ready(function() {
      const claveSae = '<?php echo $claveSae ?>';
      informaSaeInicio(claveSae);
    });
  </script>
</body>

</html>