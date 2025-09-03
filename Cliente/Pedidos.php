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
    <title>Pedidos</title>
    <!-- bootstrap css -->

    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/selec.css">
    <link rel="stylesheet" href="CSS/tablas.css">
    <link href="CSS/style1.css" rel="stylesheet" />
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
    .btnVizualizarPedido{
        cursor:pointer;
    }
</style>
<!-- <style>
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
</style> -->

<body>
    <div class="hero_area">
        <!--MENU  HEADER-->
        <?php include 'HeaderEcommerce.php'; ?>

        <main class="text-center">
            <section id="hero" class="hero section">
                <!-- Contact Section -->
                <!-- <section id="" class="contact"> -->

                <!-- Section Title -->
                <!-- <div class="container section-title" data-aos="fade-up">
                        <h2>Pedidos</h2>
                        <p></p> -->
                <section class="intro">
                    <div class="table-responsive table-scroll" data-mdb-perfect-scrollbar="true"
                        style="position: relative; height: 500px">
                        
                        <div class="container mt-7">
                            <div class="container section-title" data-aos="fade-up">
                                <h2>Pedidos</h2>
                                <p></p>
                                <!-- Table -->
                                <!-- <h2 class="mb-5">Pedidos</h2><div class="row"> -->
                                <div class="col">
                                    <div class="card shadow">
                                        <!-- <div class="card-header border-0">
                                                                                <h3 class="mb-0">Card tables</h3>
                                                                            </div> -->
                                        <div class="table-responsive">
                                            <table class="table align-items-center table-flush">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th scope="col">Clave</th>
                                                        <th scope="col">Cantidad</th>
                                                        <th scope="col">Total</th>
                                                        <th scope="col">Visualizar</th>

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

                    <!-- MODAL DETALLES PEDIDO -->
                    <div class="modal fade" id="modalDetallesPedido" tabindex="-1" aria-labelledby="modalDetallesLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title  w-100 text-center" id="modalDetallesLabel" style="color:#ffffff">Detalles
                                        del Pedido</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="color:#ffffff"
                                        aria-label="Cerrar"></button>
                                </div>
                                <div class="modal-body">
                                    <!-- TABLA  -->
                                    <div class="container mt-7">
                                        <div class="container section-title" data-aos="fade-up">
                                          
                                            <!-- Table -->
                                            <!-- <h2 class="mb-5">Pedidos</h2><div class="row"> -->
                                            <div class="col">
                                                <div class="card shadow">
                                                    <!-- <div class="card-header border-0">
                                                                                <h3 class="mb-0">Card tables</h3>
                                                                            </div> -->
                                                    <div class="table-responsive">
                                                        <table class="table align-items-center table-flush">
                                                            <thead class="thead-light">
                                                                <tr>
                                                                    <th scope="col">Producto</th>
                                                                    <th scope="col">Descripción</th>
                                                                    <th scope="col">Cantidad</th>
                                                                    <th scope="col">Subtotal</th>

                                                                </tr>
                                                            </thead>
                                                            <tbody id="">
                                                                <!-- Las partidas se insertarán aquí dinámicamente -->
                                                            </tbody>
                                                            <tfoot>
                                                                <tr>
                                                                    <td colspan="3" class="text-end">
                                                                        <strong>Total:</strong>
                                                                    </td>
                                                                    <td>
                                                                        <!-- Aquí se insertará el total -->
                                                                    </td>
                                                                </tr>
                                                            </tfoot>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Salir</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
    </div><!-- End Section Title -->
    </section><!-- /Contact Section -->

    <!-- </section> -->
    <!-- /Fin Section -->


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