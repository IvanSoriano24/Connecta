<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- bootstrap css -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link href="css/style1.css" rel="stylesheet" />
  <link rel="stylesheet" href="CSS/selec.css">

    <title>info</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div>
        <div>
        <a href="Menu.php"><input type="button" value="Regresar"></a>
        </div>
        <input type="button" value="Info" id="Empresa">
        <input type="button" value="SAE" id="SAE">
    </div>

<!-- Formumario -->
    <div class="modal fade" id="infoEmpresa" tabindex="-1" aria-labelledby="empresaModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false" style="display:none;">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body">
          <h2 class="card-title text-center">Informacion  de Empresa</h2>
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
            <button type="button" class="btn btn-danger" id="cancelarModal">Cancelar</button>
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
          <form >
            <label for="text">host</label>
            <input type="text" name="host" id="host"><br>
            <label for="text">usuario</label>
            <input type="text" name="nomEmpresa" id="noEmpresa"><br>
            <label for="text">password</label>
            <input type="password" name="razonSocial" id="razonSocial"><br>
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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>



  <script src="JS/menu.js"></script>
</body>
</html>