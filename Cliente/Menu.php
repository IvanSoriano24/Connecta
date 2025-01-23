<?php
    session_start();

    if (isset($_SESSION['usuario'])) {
        if ($_SESSION['usuario']['tipoUsuario'] == 'ADMIISTRADOR') {
            header('Location:Dashboard.php');
            exit();
        }
        $nombreUsuario = $_SESSION['usuario']["nombre"];
        $tipoUsuario   = $_SESSION['usuario']["tipoUsuario"];
    } else {
        header('Location:../index.php');
    }
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Menu Principal</title>
  <meta name="description" content="">
  <meta name="keywords" content="">

  
  <link href="css/style1.css" rel="stylesheet" />
  <link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Lato" />
  <link rel="stylesheet" href="CSS/selec.css">
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Inter:wght@100;200;300;400;500;600;700;800;900&family=Nunito:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="assets/css/main.css" rel="stylesheet">

  <style>
    body.modal-open .hero_area {
      filter: blur(5px);
      /* Difumina el fondo mientras un modal está abierto */
    }
  </style>

</head>

<body>
  <div class="hero_area">
    <!-- HEADER -->
    <?php include 'HeaderEcommerce.php'; ?>

    <main class="main">

    <!-- Hero Section -->
    <section id="hero" class="hero section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row align-items-center">
          <div class="col-lg-6">
            <div class="hero-content" data-aos="fade-up" data-aos-delay="200">
              
              <h1 class="mb-4">
               GRUPO <br>
                <span class="accent-text"> INTERZENDA</span>
              </h1>

              <div class="hero-buttons">
                <a href="Articulos.php" class="btn btn-primary me-0 me-sm-2 mx-1">Comprar</a>
                </a>
              </div>
            </div>
          </div>

          <div class="col-lg-6">
            <div class="hero-image" data-aos="zoom-out" data-aos-delay="300">
            <img src="SRC/imagen.png" alt="Logo Principal" width="700">
            </div>
          </div>
        </div>

        <div class="row stats-row gy-4 mt-5" data-aos="fade-up" data-aos-delay="500">
          <div class="col-lg-3 col-md-6">
            <div class="stat-item">
              <div class="stat-icon">
                <i class="bi bi-trophy"></i>
              </div>
              <div class="stat-content">
                <h4>40 años de experiencia </h4>
                <p class="mb-0">En el mercado de insumos veterinarios</p>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-md-6">
            <div class="stat-item">
              <div class="stat-icon">
                <i class="bi bi-award"></i>
              </div>
              <div class="stat-content">
                <h4>Calidad</h4>
                <p class="mb-0">  </p>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-md-6">
            <div class="stat-item">
              <div class="stat-icon">
                <i class="bi bi-shield-check"></i>
              </div>
              <div class="stat-content">
                <h4>Seguridad</h4>
                <p class="mb-0">   </p>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-md-6">
            <div class="stat-item">
              <div class="stat-icon">
                <i class="fas fa-handshake"></i>
              </div>
              <div class="stat-content">
                <h4>Confianza</h4>
                <!-- <p class="mb-0"> </p> -->
              </div>
            </div>
          </div>
        </div>

      </div>

    </section><!-- /Hero Section -->

  </main>

    <!-- FOOTER -->
    <?php include 'FooterEcommerce.php'; ?>

  </div>       
  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <!-- JS Para la confirmacion empresa -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>


  <script src="JS/menu.js"></script>
  <script src="JS/app.js"></script>
         


  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>

  <!-- Main JS File -->
  <script src="assets/js/main.js"></script>

</body>

</html>