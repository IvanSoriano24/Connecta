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
/* From Uiverse.io by vinodjangid07 */ 
.button {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background-color: rgb(20, 20, 20);
  border: none;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.164);
  cursor: pointer;
  transition-duration: .3s;
  overflow: hidden;
  position: relative;
}

.svgIcon {
  width: 12px;
  transition-duration: .3s;
}

.svgIcon path {
  fill: white;
}

.button:hover {
  width: 140px;
  border-radius: 50px;
  transition-duration: .3s;
  background-color: rgb(255, 69, 69);
  align-items: center;
}

.button:hover .svgIcon {
  width: 50px;
  transition-duration: .3s;
  transform: translateY(60%);
}

.button::before {
  position: absolute;
  top: -20px;
  content: "Delete";
  color: white;
  transition-duration: .3s;
  font-size: 2px;
}

.button:hover::before {
  font-size: 13px;
  opacity: 1;
  transform: translateY(30px);
  transition-duration: .3s;
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
                <h2>Carrito</h2>
                    <div class="container py-5 h-100">
                        <div class="row d-flex justify-content-center align-items-center h-100">
                            <div class="col">
                                <div class="card">
                                    <div class="card-body p-4">
                                        <div class="row">
                                            <!-- Columna de productos -->
                                            <div class="col-lg-7">
                                                <h5 class="mb-3">
                                                            <a href="Articulos.php" class="text-body">
                                                                <i class="fas fa-long-arrow-alt-left me-2"></i>
                                                                Seguir comprando
                                                            </a> 
                                                        </h5>
                                                        <hr>
                                                <div id="carrito-lista">
                                                    <!-- Aquí se generarán dinámicamente los productos -->
                                                </div>

                                            </div>

                                            <!-- Columna del resumen -->
                                            <div class="col-lg-5">
                                                <div class="card bg-primary text-white rounded-3">
                                                    <div class="card-body">
                                                        <h8 class="mb-4" id="h8">Resumen del Pedido</h8>
                                                        <hr class="my-4">
                                                        
                                                        <div class="d-flex justify-content-between">
                                                            <p class="mb-2">Folio: </p>
                                                            <input type="text" id="folioCarrito">
                                                        </div>
                                                        <!-- <div class="d-flex justify-content-between">
                                                            <p class="mb-2">Subtotal</p>
                                                            <p class="mb-2" id="subtotal">$0.00</p>
                                                        </div>
                                                        <div class="d-flex justify-content-between">
                                                            <p class="mb-2">Envío</p>
                                                            <p class="mb-2">$20.00</p>
                                                        </div>
                                                        <div class="d-flex justify-content-between mb-4">
                                                            <p class="mb-2">Total (Incl. impuestos)</p>
                                                            <p class="mb-2" id="total">$0.00</p>
                                                        </div> -->
                                                        <button type="button" class="btn btn-light btn-block btn-lg" id="btnPagar">
                                                            <div class="d-flex justify-content-between">
                                                                <span id=""></span>
                                                                <span>Pagar <i
                                                                        class="fas fa-long-arrow-alt-right ms-2"></i></span>
                                                            </div> 
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>

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

    <script src="JS/ventas.js"></script>
    <script src="JS/menu.js"></script>
    <!-- Scroll Top -->
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i
            class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- JS Para la confirmacion empresa -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/php-email-form/validate.js"></script>
    <script src="assets/vendor/aos/aos.js"></script>
    <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>

    <!-- Main JS File -->
    <script src="assets/js/main.js"></script>


    <script>
    $(document).ready(function() {
        datosPedidos();
    });
    </script>
</body>

</html>