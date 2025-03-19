<?php
session_start();

if (isset($_SESSION['usuario'])) {
    if (
        $_SESSION['usuario']['tipoUsuario'] == 'ALMACENISTA' || $_SESSION['usuario']['tipoUsuario'] == 'VENDEDOR' ||
        $_SESSION['usuario']['tipoUsuario'] == 'FACTURISTA'
    ) {
        header('Location:Dashboard.php');
        exit();
    }
    $nombreUsuario = $_SESSION['usuario']["nombre"];
    $tipoUsuario = $_SESSION['usuario']["tipoUsuario"];
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

<body>
    <div class="hero_area">
        <!--MENU  HEADER-->
        <?php include 'HeaderEcommerce.php'; ?>

        <main class="text-center">
            <section id="hero" class="hero section">
                <!-- Contact Section -->
                <section id="" class="contact ">

                    <!-- Section Title -->
                    <div class="container section-title" data-aos="fade-up">
                        <h2>Pedidos</h2>
                        <p></p>
                        <section class="intro">
                            <div class="bg-image h-100" style="background-color: #f5f7fa;">
                                <div class="mask d-flex align-items-center h-100">
                                    <div class="container">
                                        <div class="row justify-content-center">
                                            <div class="col-12">
                                                <div class="card">
                                                    <div class="card-body p-0">
                                                        <div class="table-responsive table-scroll"
                                                            data-mdb-perfect-scrollbar="true"
                                                            style="position: relative; height: 500px">
                                                            <table class="table table-striped mb-0">
                                                                <thead style="background-color:rgb(49, 118, 222);">
                                                                    <tr>
                                                                        <th scope="col"
                                                                            style="background-color: #0d77bd; color: white; ">
                                                                            Clave</th>
                                                                        <th scope="col"
                                                                            style="background-color: #0d77bd; color: white; ">
                                                                            Cantidad</th>
                                                                        <th scope="col"
                                                                            style="background-color: #0d77bd; color: white; ">
                                                                            Total</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody id="datosPedidos">
                                                                    <tr>

                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div><!-- End Section Title -->
                </section><!-- /Contact Section -->
            </section><!-- /Fin Section -->


        </main>
        <!-- FOOTER -->
        <?php include 'FooterEcommerce.php'; ?>

    </div>
    <script src="JS/pedidos.js"></script>
    <script src="JS/menu.js"></script>
    <!-- Scroll Top -->
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i
            class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- JS Para la confirmacion empresa -->
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