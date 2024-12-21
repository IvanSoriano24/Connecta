
<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no" />
  <!-- bootstrap css -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">  
  <link href="css/style1.css" rel="stylesheet" />
  <link rel="stylesheet" href="CSS/selec.css">

  <title>Menu Principal</title>

  <style>
    body.modal-open .hero_area {
     filter: blur(5px); /* Difumina el fondo mientras un modal está abierto */
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
                  <a class="nav-link" href="cliente.html">Cliente <span class="sr-only"></span></a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="productos.html">Productos </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="pedidos.html">Pedidos </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="infoEmpresa.html">Info Empresa </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="index.php" >Cerrar Sesion </a>
                </li>
                <!-- Agregar otro titulo
                <li class="nav-item">
                  <a class="nav-link" href="logo.html"> Logo</a>
                </li>
                   </a>
                 -->

            </div>
          </div>
          <a class="navbar-brand" href="">
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
        </nav>
      </div>
    </header> <!-- Final del header section -->
  </div>

  <div class="modal fade" id="empresaModal" tabindex="-1" aria-labelledby="empresaModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="text-center mb-4">
            <h1 class="display-4 text-primary">Bienvenid@</h1>
              <!-- LA PARTE MOSTRARA SU PERFIL -->
        </div>
        <div class="modal-body">
        <h2 class="card-title text-center">Selecciona Empresa</h2>
          <select class="form-select" id="empresaSelect"> 
                <option selected disabled> </option>
                <option value="Emp1">Emp 1</option>
                <option value="Emp2">Emp 2</option>
                <option value="Emp3">Emp 3</option>
          </select>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" id="confirmarEmpresa"> Confirmar</button>
        </div>
      </div>
    </div>
  </div>

    <!-- JS Para la confirmacion empresa -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
  document.addEventListener('DOMContentLoaded', function () {
    const empresaModal = new bootstrap.Modal(document.getElementById('empresaModal'));
    empresaModal.show();
  });

  document.getElementById('confirmarEmpresa').addEventListener('click', function () {
    const empresaSeleccionada = document.getElementById('empresaSelect').value;
    if (empresaSeleccionada) {
      alert(`Has seleccionado:   ${empresaSeleccionada}`);
      const modal = bootstrap.Modal.getInstance(document.getElementById('empresaModal'));
      modal.hide();
    } else {
      alert('Por favor, selecciona una empresa.');
    }
  });
</script>
    
 </body>
 
</html>
    

  