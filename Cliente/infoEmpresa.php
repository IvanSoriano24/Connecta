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

  <style>
    .btn-group .btn.active {
      background-color: #0d6efd;
      color: white;
    }
  </style>

  <title>MDConnecta</title>
</head>

<body>
  <div class="">
    <!-- SIDEBAR -->
    <?php include 'sidebar.php'; ?>
    <!-- CONTENT -->
    <div id="seccionInfo">
      <section id="content">
        <!-- NAVBAR -->
        <?php include 'navbar.php'; ?>
        <!-- MAIN -->
        <main class="text-center ">
          <div class="btn-group mb-4" role="group">
            <button type="button" class="btn btn-primary" id="btnInfo">Información General</button>
            <button type="button" class="btn btn-primary" id="btnFact">Datos de Facturación</button>
          </div>
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
            <button class="btn btn-success" id="btnAgregar">
              <i class='bx bxs-briefcase'></i> Agregar
            </button>
          </div>
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
                  <input type="hidden" name="idDocumento" id="idDocumento">
                  <input type="text" name="id" id="id" value="<?php echo $idEmpresa ?>" hidden>
                  <label for="noEmpresa">No. Empresa:</label>
                  <input class="input-small" type="numer" name="noEmpresa" id="noEmpresa" value="" readonly>
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
                    <option selected disabled>Selecciona un regimen</option>
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
        </main>
      </section>
    </div>
    <div id="seccionFact" style="display:none">
      <section id="content">
        <?php include 'navbar.php'; ?>
        <main class="text-center">
          <div class="btn-group mb-4" role="group">
            <button type="button" class="btn btn-primary" id="btnInfoFac">Información General</button>
            <button type="button" class="btn btn-primary" id="btnFactFac">Datos de Facturación</button>
          </div>
          <div class="head-title">
            <div class="left">
              <h1>Datos de Facturación</h1>
              <ul class="breadcrumb">
                <li><a href="Dashboard.php">Inicio</a></li>
                <li><i class='bx bx-chevron-right'></i></li>
                <li><a href="#">Información Empresa</a></li>
                <li><i class='bx bx-chevron-right'></i></li>
                <li><a href="#">Datos Facturación</a></li>
              </ul>
            </div>
          </div>
          <div class="table-data">
            <div class="order">
              <div class="head">
                <h3></h3>
                <a>Campos Obligatorios *</a>
              </div>
              <form id="formFacturacion" enctype="multipart/form-data">
                <input type="hidden" name="action" value="saveFac">
                <input type="hidden" name="noEmpresa" id="noEmpresa" value="">
                <input type="hidden" name="idDocumento" id="idDocumento">
                <input type="hidden" name="idFat" id="idFat" value="0">

                <div class="row g-3">
                  <div class="col-md-4">
                    <label for="cerFile" class="form-label">Archivo CER *</label>
                    <input type="file" class="form-control form-control-sm" id="cerFile" name="cerFile" accept=".cer">
                  </div>
                  <div class="col-md-4">
                    <label for="permFile" class="form-label">Archivo KEY *</label>
                    <input type="file" class="form-control form-control-sm" id="permFile" name="permFile" accept=".key">
                  </div>
                  <div class="col-md-4">
                    <label for="keyPassword" class="form-label">Contraseña KEY *</label>
                    <input type="password" class="form-control form-control-sm" id="keyPassword" name="keyPassword">
                  </div>
                </div>

                <div class="mt-4 d-flex justify-content-end">
                  <button type="submit" class="btn btn-success me-2" id="BtnguardarFac">Guardar</button>
                  <button type="button" class="btn btn-secondary">Cancelar</button>
                </div>
              </form>

            </div>
          </div>
        </main>
      </section>
    </div>
  </div>

  <!-- MAIN -->


  <!-- CONTENT -->
  </div>
  <div id="formularioEmpresa" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Agregar Empresa</h5>
          <button type="button" class="btn-close custom-close" data-dismiss="modal" aria-label="Close"
            id="cerrarModalHeader">
            <span aria-hidden="true"></span><!-- &times; -->
          </button>
        </div>
        <form onsubmit="return validateForm()">
          <div class="form-row">
            <input type="hidden" name="csrf_tokenModal" id="csrf_tokenModal" value="<?php echo $csrf_token; ?>">
            <!--<label for="noEmpresa">No. Empresa:</label>
            <select name="noEmpresaModal" id="noEmpresaModal" placeholder="Selecciona opcion" value="">
            <option selected disabled>Selecciona un numero de empresa</option>
              <option value="1">1</option>
              <option value="2">2</option>
              <option value="3">3</option>
              <option value="4">4</option>
              <option value="5">5</option>
              <option value="6">6</option>
              <option value="7">7</option>
              <option value="8">8</option>
              <option value="9">9</option>
              <option value="10">10</option>
              <option value="11">11</option>
             
            </select>
            <select name="noEmpresaModal" id="noEmpresaModal" placeholder="Selecciona opcion">
              <option selected disabled>Selecciona un número de empresa</option>
            </select>-->
          </div>

          <div class="form-row">
            <!-- <a class='bx bx-message-rounded-error'></a> -->
            <label for="razonSocial">Razón Social: <a class='bx'> *</a></label>
            <input class="input-m" type="text" name="razonSocialModal" id="razonSocialModal" values="">
          </div>

          <div class="form-row">
            <!-- <a class='bx bx-message-rounded-error'></a> -->
            <label for="rfc">RFC:<a class='bx'> *</a></label>
            <input class="input-m" type="text" name="rfcModal" id="rfcModal" placeholder="RFC" value="">
          </div>

          <div class="form-row">
            <!-- <a class='bx bx-message-rounded-error'></a> -->
            <label for="regimenFiscal">Régimen Fiscal: <a class='bx'>*</a></label>
            <select name="regimenFiscalModal" id="regimenFiscalModal" placeholder="Selecciona opcion" value="" disabled>
              <option selected disabled>Selecciona un regimen</option>
              <option>626.- Regimen Simplificado de Confianza</option>
              <!-- Agrega más opciones si es necesario -->
            </select>
          </div>

          <div class="form-row">
            <label for="Calle">Calle:</label>
            <input class="input-m" type="text" name="calleModal" id="calleModal" placeholder="Calle">
          </div>

          <div class="form-row">
            <label for="numExterior">Num. Exterior:</label>
            <input class="input-small" type="text" name="numExteriorModal" id="numExteriorModal" placeholder="Num. Exterior">
            <label for="numInterior">Num. Interior:</label>
            <input class="input-small" type="text" name="numInteriorModal" id="numInteriorModal" placeholder="Num. Interior">
            <label for="entreCalle">Entre Calle:</label>
            <input class="input-m" type="text" name="entreCalleModal" id="entreCalleModal" placeholder="Entre Calle">
          </div>

          <div class="form-row">
            <label for="colonia">Colonia:</label>
            <input class="input-m" type="text" name="coloniaModal" id="coloniaModal" placeholder="Colonia">
            <label for="referencia">Referencia:</label>
            <input class="input-m" type="text" name="referenciaModal" id="referenciaModal" placeholder="Referencia">
          </div>

          <div class="form-row">
            <label for="pais">País:</label>
            <input class="input-m" type="text" name="paisModal" id="paisModal" placeholder="País">
            <label for="estado">Estado:</label>
            <input class="input-m" type="text" name="estadoModal" id="estadoModal" placeholder="Estado">
            <label for="municipio">Municipio:</label>
            <input class="input-m" type="text" name="municipioModal" id="municipioModal" placeholder="Municipio">
          </div>

          <div class="form-row">
            <!-- <a class='bx bx-message-rounded-error'></a> -->
            <label for="cp">Codigo Postal: <a class='bx'> *</a></label>
            <input class="input-m" type="text" name="codigoPostalModal" id="codigoPostalModal" placeholder="Codigo Postal">
          </div>

          <div class="form-row">
            <label for="poblacion">Población:</label>
            <input class="input-m" type="text" name="poblacionModal" id="poblacionModal" placeholder="Poblacion">
          </div>

          <div class="form-buttons">
            <button type="submit" class="btn-save" id="guardarEmpresa">Guardar</button>
            <button type="button" class="btn-cancel" id="cerrarModalFooter">Cancelar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  </section>
  <!-- CONTENT -->

  <!-- JS Para la confirmacion empresa -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    $(document).ready(function() {
      informaEmpresa(); // Llamada a la función cuando la página de la empresa se ha cargado.
      //infoFacturacion();
    });
  </script>
  <script src="JS/menu.js"></script>
  <script src="JS/app.js"></script>
  <script src="JS/script.js"></script>
  <script>
    $(document).ready(function() {
      for (var i = 1; i <= 99; i++) {
        $('#noEmpresaModal').append('<option value="' + i + '">' + i + '</option>');
      }
    });
  </script>
  <script>
    $(function() {
      // al hacer clic en "Información General"
      $("#btnInfo").click(function() {
        $("#seccionFact").hide();
        $("#seccionInfo").show();
        $("#btnFact, #btnInfo").removeClass("active");
        $(this).addClass("active");
      });
      // al hacer clic en "Datos de Facturación"
      $("#btnFact").click(function() {
        $("#seccionInfo").hide();
        $("#seccionFact").show();
        $("#btnInfo, #btnFact").removeClass("active");
        $("#btnFactFac").addClass("active");
      });
      // inicia con primer botón activo
      $("#btnInfo").addClass("active");
    });
  </script>
  <script>
    $(function() {
      // al hacer clic en "Información General"
      $("#btnInfoFac").click(function() {
        $("#seccionFact").hide();
        $("#seccionInfo").show();
        $("#btnFactFac, #btnInfoFac").removeClass("active");
        $("#btnInfo").addClass("active");
      });
      // al hacer clic en "Datos de Facturación"
      $("#btnFactFac").click(function() {
        $("#seccionInfo").hide();
        $("#seccionFact").show();
        $("#btnInfoFac, #btnFactFac").removeClass("active");
        $(this).addClass("active");
      });
      // inicia con primer botón activo
      //$("#btnInfo").addClass("active");
    });
  </script>

</body>

</html>