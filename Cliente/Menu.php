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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

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


  <!-- IMAGEN GRUPO INTERZENDA CENTRADA GRANDE  -->

  <main class="text-center my-5 hero_area">
    <img src="SRC/imagen.png" alt="Logo Principal" width="700">
    <h1 class="mt-4 txth2">GRUPO <br> INTERZENDA</h1>
  </main>

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



  <!-- JS Para la confirmacion empresa -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>


  <script src="JS/menu.js"></script>
  <script src="JS/app.js"></script>
</body>

</html>