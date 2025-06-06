<?php
//Iniciar sesion
    session_start();
    //Validar si hay una sesion
    if (isset($_SESSION['usuario'])) {
        //Si la sesion iniciada no es de un Cliente o un Administrador, redirigir a MDConnecta
        if ($_SESSION['usuario']['tipoUsuario'] == 'ALMACENISTA' || 
            $_SESSION['usuario']['tipoUsuario'] == 'VENDEDOR' ||
            $_SESSION['usuario']['tipoUsuario'] == 'FACTURISTA') {
            header('Location:Dashboard.php');
            exit();
        }
        //Obtener datos del usuario
        $nombreUsuario = $_SESSION['usuario']["nombre"];
        $tipoUsuario   = $_SESSION['usuario']["tipoUsuario"];
    } else {
        //Si no hay una secion, redirigir al inicio de sesion
        header('Location:../index.php');
    }
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no" />
    <!-- bootstrap css -->

    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/selec.css">
    <link href="css/style1.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Lato" />
    <link rel="stylesheet" href="CSS/selec.css">
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

<style>
    .intro {
        height: 100%;
    }

    table td,
    table th {
        text-overflow: ellipsis;
        white-space: nowrap;
        overflow: hidden;
    }

    thead th {
        color: #fff;
    }

    .card {
        border-radius: .5rem;
    }

    .table-scroll {
        border-radius: .5rem;
    }

    .table-scroll table thead th {
        font-size: 1.25rem;
    }

    thead {
        top: 0;
        position: sticky;
    }
</style>
<style>
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
        .card{
            cursor: pointer;
        }
    </style>

<body>
    <div class="hero_area">
        <!--MENU  HEADER-->
        <?php include 'HeaderEcommerce.php'; ?>

        <main class="text-center">
            <section id="hero" class="hero section">
                <!-- Contact Section -->
                <section class="container section-title" data-aos="fade-up">

                    <div class="container py-5">
                        <!-- Contenedor de los saldos -->
                        <div class="row d-flex justify-content-center align-items-center">
                            <div class="col-lg-6">
                                <div class="card shadow-lg">
                                    <div class="card-body">
                                        <h3 class="card-title text-center mb-4">Tu Crédito</h3>
                                        <form>
                                            <div class="mb-3">
                                                <label for="credito" class="form-label">Crédito:</label>
                                                <input type="text" id="credito" style="text-align: right;" class="form-control" readonly1>
                                            </div>
                                            <div class="mb-3">
                                                <label for="saldo" class="form-label">Saldo:</label>
                                                <input type="text" id="saldo" style="text-align: right;" class="form-control" readonly1>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <br>
                        <h2 class="text-center">Productos Sugeridos</h2>
                        <div id="product-list" class="product-list">
                            <!-- El carrusel se insertará aquí dinámicamente -->
                        </div>
                    </div>

                </section><!-- /Contact Section -->
            </section><!-- /Fin Section -->

        </main>
        <!-- FOOTER -->
        <?php include 'FooterEcommerce.php'; ?>
    </div>
    <!-- Modal de productos recomendados -->
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
                    <!-- Boton para añadir el producto al carrito -->
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
    <!-- Scripts de JS para el funcionamiento del sistema -->
    <script src="JS/credito.js"></script>
    <script src="JS/menu.js"></script>
    <!-- Scroll Top -->
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i
            class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- <script src="assets/vendor/php-email-form/validate.js"></script> -->
    <script src="assets/vendor/aos/aos.js"></script>
    <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>

    <!-- Main JS File -->
    <script src="assets/js/main.js"></script>
</body>

</html>