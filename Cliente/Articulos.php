<?php
    session_start();

    if (isset($_SESSION['usuario'])) {
        if ($_SESSION['usuario']['tipoUsuario'] == 'ALMACENISTA' || 
            $_SESSION['usuario']['tipoUsuario'] == 'VENDEDOR' ||
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

    <!-- Agregar estilos para el scroll del contenedor de productos -->
    <style>
        /* Limita la altura de la grilla de productos y activa el scroll vertical */
        .product-grid {
            max-height: 80vh; /* 80% de la altura de la ventana, ajústalo según tu necesidad */
            overflow-y: auto;
            padding-right: 15px; /* Opcional, para dar espacio al scrollbar */
        }

        .carousel-control-prev-icon,
        .carousel-control-next-icon {
            background-color: rgba(0, 0, 0, 0.6) !important;
            /* Fondo semitransparente negro */
            padding: 0.75rem;
            /* Espaciado interno para agrandar el icono */
            border-radius: 50%;
            /* Bordes redondeados */
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.5);
            /* Sombra para mayor contraste */
        }

        .category{
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="hero_area">
        <!--MENU HEADER-->
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
                                <div class="container">
                                    <!-- El contenedor con la clase "product-grid" ahora tendrá scroll si es necesario -->
                                    <div class="row product-grid">
                                        <!-- Los productos se cargarán dinámicamente aquí -->
                                    </div>
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

    <!-- Modal (si lo utilizas para mostrar detalles de producto) -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <!-- Header del Modal -->
            <div class="modal-header">
                <h2 id="modal-title">Nombre del Producto</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div class="modal-image">
                    <div id="modal-carousel" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner" id="modal-carousel-inner">
                            <!-- Imágenes del producto se insertarán aquí -->
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#modal-carousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#modal-carousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        </button>
                    </div>
                </div>
                <div class="modal-details">
                    <p class="modal-price" id="modal-price">$0.00</p>
                    <div class="modal-add-cart">
                        <input type="number" id="cantidadProducto" value="1" min="1">
                        <button id="btn-add-to-cart" class="btn btn-primary">Añadir al carrito</button>
                    </div>
                    <br><br>
                    <h3>Descripción</h3>
                    <p id="modal-description">Descripción del producto aquí.</p>
                    <h4>Información adicional</h4>
                    <p id="modal-lin-prod">Línea del Producto: </p>
                </div>
            </div>
        </div>
    </div>

    <script src="JS/articulos.js"></script>
    <script src="JS/carrito.js"></script>
    <script src="JS/menu.js"></script>
    <!-- Scroll Top -->
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center">
        <i class="bi bi-arrow-up-short"></i>
    </a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/aos/aos.js"></script>
    <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>

    <!-- Main JS File -->
    <script src="assets/js/main.js"></script>
</body>
</html>
