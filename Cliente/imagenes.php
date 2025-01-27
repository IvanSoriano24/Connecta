<?php
    session_start();

    if (isset($_SESSION['usuario'])) {
        if ($_SESSION['usuario']['tipoUsuario'] == 'ALMACENISTA' || $_SESSION['usuario']['tipoUsuario'] == 'VENDEDOR' ||
        $_SESSION['usuario']['tipoUsuario'] == 'FACTURISTA') {
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
<html>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- bootstrap css -->
    <link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Lato" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Inter:wght@100;200;300;400;500;600;700;800;900&family=Nunito:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Vendor CSS Files -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
    <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

    <!-- Main CSS File -->
    <link href="assets/css/main.css" rel="stylesheet">
    <link rel="stylesheet" href="CSS/articulos.css">
</head>

<body>
    <div class="hero_area">
        <!--MENU  HEADER-->
        <?php include 'HeaderEcommerce.php'; ?>
        <main class="main">
            <section id="hero" class="hero section">
                <section id="features" class="features section">
                    <!-- Section Title -->
                    <div class="container mt-4">
                        <h1 class="mb-4">Gestión de Imágenes de Artículos</h1>
                        <div id="product-list" class="row g-3">
                            <!-- Los productos con imágenes se cargarán dinámicamente aquí -->
                        </div>
                    </div>
                </section><!-- /Features Section -->
            </section><!-- /Hero Section -->
        </main>
    </div>
    <!-- FOOTER -->
    <?php include 'FooterEcommerce.php'; ?>

    <script src="JS/imagenes.js"></script>
    <script src="JS/menu.js"></script>
    <!-- Scroll Top -->
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i
            class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- JS Para la confirmacion empresa -->
    <!--<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>-->
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/aos/aos.js"></script>
    <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
    <!-- Main JS File -->
    <script src="assets/js/main.js"></script>
</body>
</html>