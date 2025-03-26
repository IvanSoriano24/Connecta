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
    <link rel="stylesheet" href="CSS/tablas.css">
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

<body>
    <div class="hero_area">
        <!--MENU HEADER-->
        <?php include 'HeaderEcommerce.php'; ?>

        <main class="text-center">
            <section id="hero" class="hero section">
                <!-- Contact Section -->
                <section id="" class="contact ">

                    <main class="container my-5">
                        <div class="card shadow">
                            <div class="card-header bg-primary text-white">
                                <h3 class="mb-0" style="color:#ffffff">Descuentos</h3>
                            </div>
                            <div class="card-body">
                                <!-- Selección de Cliente y Descuento Global -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="descuentosClientes" class="form-label">Seleccionar Cliente:</label>
                                        <select name="descuentosClientes" id="descuentosClientes" class="form-select">
                                            <option value="">Selecciona un Cliente</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="descuentoCliente" class="form-label">Descuento Cliente:</label>
                                        <input type="text" id="descuentoCliente" class="form-control"
                                            style="text-align: right;">
                                    </div>
                                </div>
                                <!-- Tabla de Productos -->
                                <!-- <div class="table-responsive table-scroll"
                                                            data-mdb-perfect-scrollbar="true"
                                                            style="position: relative; height: 500px">
                                    <table class="table table-striped">
                                        <thead class="table-dark sticky-top">
                                            <tr>
                                                <th>Clave</th>
                                                <th>Descripción</th>
                                                <th>Descuento</th>
                                            </tr>
                                        </thead>
                                        <tbody id="datosDescuentos">
                                            Las filas se cargarán dinámicamente
                                        </tbody>
                                    </table>
                                </div> -->
                        
                                
                                <div class="container mt-7">
                                    <div class="container section-title" data-aos="fade-up">
                                        <!-- Table -->
                                        <div class="col">
                                            <div class="card shadow">
                                                <div class="table-responsive">
                                                    <table class="table align-items-center table-flush">
                                                        <thead class="thead-light">
                                                            <tr>
                                                                <th scope="col">Clave</th>
                                                                <th scope="col">Descripcion</th>
                                                                <th scope="col">Descuento</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="datosDescuentos">
                                                            <tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Botón para guardar -->
                                <div class="d-flex justify-content-end mt-3">
                                    <button type="button" class="btn btn-success" id="guardarDescuentos">Guardar
                                        Descuentos</button>
                                </div>
                            </div>
                        </div>
                    </main>
    </div>
    </div>
    </section>
    </div><!-- End Section Title -->
    </section><!-- /Contact Section -->
    </section><!-- /End Section -->
    </main>
    <!-- FOOTER -->
    <?php include 'FooterEcommerce.php'; ?>
    </div>
    <script src="JS/descuentos.js"></script>
    <script src="JS/menu.js"></script>
    <!-- Scroll Top -->
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i
            class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- JS for company confirmation -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>

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