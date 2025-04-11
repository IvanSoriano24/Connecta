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
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>

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
    <link rel="stylesheet" href="CSS/carrito.css">
</head>

<style>
.div {
  margin: 0 auto;
  font-size: 1rem;
  background: #ffffff;
  border-radius: 5px;
  position: relative;
  width: 200px; /* Tamaño reducido */
  display: flex;
  align-items: center; /* Alineación centrada */
  border: 1px solid #ccc; /* Borde añadido */
}

.div .icon {
  padding: 0.5em;
  display: flex;
  align-items: center;
  justify-content: center;
}

.div input[type="text"] {
  padding: 0.5em; /* Tamaño reducido */
  border: 1px solid #ccc; /* Borde añadido */
  flex: 1;
  font-size: 0.875rem; /* Tamaño de fuente reducido */
  font-family: inherit;
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
                        <div class="row d-flex justify-content-center align-items-center">
                            <div class="col-lg-10">
                                <div class="card shadow-lg">
                                    <div class="card-body p-5">
                                        <h3 class="text-center mb-4">🛒 Carrito de Compras</h3>

                                        <div class="div" hidden>
                                            <span class="icon">
                                                <i class='bx bx-file'></i>
                                            </span>
                                            <input type="text" id="folioCarrito" placeholder="Folio del Carrito"
                                                readonly />
                                        </div>
                                        <br>

                                        <div class="table-responsive">
                                            <table class="table table-hover table-striped text-center align-middle">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th scope="col">Vista</th>
                                                        <th scope="col">Producto</th>
                                                        <th scope="col">Precio</th>
                                                        <th scope="col">Cantidad</th>
                                                        <th scope="col">Total</th>
                                                        <th scope="col">Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="carrito-lista">
                                                    <!-- Los productos se generarán aquí dinámicamente -->
                                                </tbody>
                                            </table>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between align-items-center">

                                            <h4>Subtotal: <span id="subtotal">$0.00</span></h4>
                                            <h3>Total: <span id="total">$0.00</span></h3>
                                        </div>
                                        <button class="btn btn-primary btn-lg w-100 mt-4" id="btnPagar">💳 Pagar
                                            Ahora</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </section><!-- /Contact Section -->
            </section><!-- /Fin Section -->


        </main>
        <!-- FOOTER -->
        <?php include 'FooterEcommerce.php'; ?>

    </div>




    <script src="JS/carrito.js"></script>
    <!-- <script src="JS/articulos.js"></script>-->
    <script src="JS/menu.js"></script>
    <!-- <script src="JS/ventas.js"></script> -->
    <!-- Scroll Top -->
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i
            class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- JS Para la confirmacion empresa -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://code.jquery.com/jquery-1.10.2.min.js"></script>


    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/php-email-form/validate.js"></script>
    <script src="assets/vendor/aos/aos.js"></script>
    <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>

    <!-- Main JS File -->
    <script src="assets/js/main.js"></script>

</html>