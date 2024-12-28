
<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no" />
  <!-- bootstrap css -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  

  <link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Lato" />
  <link rel="stylesheet" href="css/estilosmenu.css"> 


  <title>Menu Principal</title>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</head>

<body>
  <div class="hero_area">
    <!--MENU  HEADER-->
    <nav class="menu">
      <section class="menu__container">
        <a href=""><img src="SRC/imagen.png" alt="Logo" id="logop"> </a>

        <ul class="menu__links">
          <li class="menu__item">
            <a href="Menu.php" class="menu__link txt">Inicio</a>
          </li>
          <li class="menu__item">
            <a href="productos.html" class="menu__link txt">Credito</a>
          </li>
          <li class="menu__item">
            <a href="Pedidos.php" class="menu__link txt">Pedidos</a>
          </li>
          <li class="menu__item">
            <a href="pedidos.php" class="menu__link txt">Guias</a>
          </li>
          <li class="menu__item">
            <a class="menu__link txt" id="cerrarSesion" name="cerrarSesion">Cerrar Sesión</a>
          </li>
        </ul>

        <div class="menu__hamburguer">
          <img src="assets/menu.svg" class="menu__img">
        </div>
      </section>
    </nav>
  </div>

  <div class="content-container">
    <section>
      <h2>Artículos</h2>
      <div class="table-container">
        <table id="tablaPedidos">
          <thead>
            <tr>
              <th colspan="5">
                <input type="button" id="btnCrearPedido" value="Crear Pedido">
              </th>
            </tr>
            <tr>
              <th>ID Pedido</th>
              <th>Cliente</th>
              <th>Total</th>
              <th>Fecha</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody id="datosPedidos">
            <!-- Pedidos cargados dinámicamente -->
          </tbody>
        </table>
      </div>
    </section>

    <aside style="display: none;">
      <div class="search-bar">
        <input type="text" class="form-control" placeholder="Buscar...">
      </div>
      <div class="table-container">
        <table class="table table-bordered">
          <thead>
            <tr>
              <th>Campo 1</th>
              <th>Campo 2</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Valor 1</td>
              <td>Valor 2</td>
            </tr>
            <tr>
              <td>Valor 3</td>
              <td>Valor 4</td>
            </tr>
          </tbody>
        </table>
      </div>
    </aside>
  </div>

  <!-- Footer -->
  <!-- Footer Start -->
  <div class="container-fluid pt-4 px-4">
    <div class="bg-light rounded-top p-4">
      <div class="row">
        <div class="col-12 col-sm-6 text-center text-sm-start">
          <img src="SRC/imagen.png" class="logomdconnecta" alt="Logo">
          &copy; <a href="#"> MDCloud</a>, All Right Reserved.
        </div>
        <div class="col-12 col-sm-6 text-center text-sm-end">
          De: <a> MDCONNECTA</a>
        </div>
      </div>
    </div>
  </div>
  <!-- Footer End -->
  </div>

<!-- Formulario de edición de pedido -->
<div id="formularioEditarPedido" style="display:none;">
  <h2>Editar Pedido</h2>
  <form id="editarPedidoForm">
    <label for="idPedido">ID Pedido</label>
    <input type="text" id="idPedido" disabled>

    <label for="cliente">Cliente</label>
    <input type="text" id="cliente">

    <label for="total">Total</label>
    <input type="number" id="total">

    <label for="fecha">Fecha</label>
    <input type="date" id="fecha">

    <button type="submit">Actualizar Pedido</button>
    <button type="button" id="cerrarFormulario">Cancelar</button>
  </form>
</div>

  <script src="JS/menu.js"></script>
  <script src="JS/app.js"></script>
  <script src="JS/pedidos.js"></script>
</body>
</html>