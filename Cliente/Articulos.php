




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
                    <div class="container section-title" data-aos="fade-up">
                        <h2>Productos</h2>
                        <p>Encuentra los productos que necesitas en nuestro catálogo.</p>
                    </div><!-- End Section Title -->

                    <div class="container">
                        <div class="tab-content" data-aos="fade-up" data-aos-delay="200">
                            <div class="tab-pane fade active show" id="features-tab-1">
                                <div class="product-grid">
                                    <!-- Los productos se cargarán dinámicamente aquí -->
                                </div>
                            </div>
                        </div>
                    </div>

                </section><!-- /Features Section -->
            </section><!-- /Hero Section -->
        </main>
        <!-- FOOTER -->
        <?php include 'FooterEcommerce.php'; ?>

    </div>

    <div id="productModal" class="modal">
        <div class="modal-content">
            <button id="closeModal" class="close-button">Cerrar</button>
            <div class="modal-body">
                <div class="modal-image">
                    <img id="modal-image" src="" alt="Imagen del Producto">
                </div>
                <div class="modal-details">
                    <h2 id="modal-title">Título del Producto</h2>
                    <p id="modal-description">Descripción del Producto</p>
                    <p id="modal-existencia">Existencia: </p>
                    <p id="modal-lin-prod">Línea del Producto: </p>
                    <button id="btn-add-to-cart" class="btn-agregar">Agregar al carrito</button>
                </div>
            </div>
        </div>
    </div>


    <script src="JS/articulos.js"></script>
    <script src="JS/menu.js"></script>

    <!-- Scroll Top -->
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i
            class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- JS Para la confirmacion empresa -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  
    <script src="assets/vendor/aos/aos.js"></script>
    <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>


    <!-- Main JS File -->
    <script src="assets/js/main.js"></script>


</body>

</html>