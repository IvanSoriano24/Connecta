<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no" />
    <!-- bootstrap css -->


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
    body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 20px;
    background-color: #f9f9f9;
    color: #333;
}

h1 {
    text-align: center;
    margin-bottom: 20px;
}

.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    padding: 0 10px;
}

.product-item {
    text-decoration: none;
    text-align: center;
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 10px;
    transition: box-shadow 0.3s ease;
}

.product-item img {
    max-width: 100%;
    height: auto;
    border-radius: 8px 8px 0 0;
}

.product-item p {
    margin: 10px 0 0;
    color: #333;
    font-size: 16px;
}

.product-item:hover {
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}
/* prueba */
.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 20px;
}

.product-item img {
  width: 100%;
  height: auto;
  border-radius: 8px;
}

.product-item p {
  margin-top: 10px;
  font-size: 16px;
  font-weight: bold;
}

</style>
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
                        <p>  </p>
                    </div><!-- End Section Title -->

                    <div class="container">

                        <div class="tab-content" data-aos="fade-up" data-aos-delay="200">

                            <div class="tab-pane fade active show" id="features-tab-1">
                                <div class="product-grid">
                                    <a href="producto1.html" class="product-item">
                                        <img src="https://grupointerzenda.co/wp-content/uploads/2021/02/aguja-desechable.png" alt="Aguja Estéril Desechable">
                                        <p>Aguja Estéril Desechable</p>
                                    </a>
                                    <a href="producto2.html" class="product-item">
                                        <img src="https://grupointerzenda.co/wp-content/uploads/2021/02/jeringa-esteril-desechable-300x300.png" alt="Jeringa Desechable Estéril">
                                        <p>Jeringa Desechable Estéril</p>
                                    </a>
                                    <a href="producto3.html" class="product-item">
                                        <img src="https://grupointerzenda.co/wp-content/uploads/2021/02/aguja-esteril-reusable-300x300.png" alt="Aguja Estéril de Aluminio">
                                        <p>Aguja Estéril de Aluminio</p>
                                    </a>
                                    <a href="producto4.html" class="product-item">
                                        <img src="https://grupointerzenda.co/wp-content/uploads/2021/02/jeringa-reusable-1-300x300.png" alt="Jeringa Reusable">
                                        <p>Jeringa Reusable</p>
                                    </a>
                                    <a href="producto5.html" class="product-item">
                                        <img src="https://grupointerzenda.co/wp-content/uploads/2021/02/aguja-hipodermica-reusable-1-300x300.png" alt="Aguja Hipodérmica Reusable">
                                        <p>Aguja Hipodérmica Reusable</p>
                                    </a>
                                    <a href="producto6.html" class="product-item">
                                        <img src="https://grupointerzenda.co/wp-content/uploads/2021/02/arete-de-identificacion-300x300.png" alt="Arete de Identificación para Ganado">
                                        <p>Arete de Identificación para Ganado</p>
                                    </a>
                                    <a href="producto7.html" class="product-item">
                                        <img src="https://grupointerzenda.co/wp-content/uploads/2021/02/pinza-aretadora-300x300.png" alt="Pinza Aretadora para Ganado">
                                        <p>Pinza Aretadora para Ganado</p>
                                    </a>
                                    <a href="producto8.html" class="product-item">
                                        <img src="https://grupointerzenda.co/wp-content/uploads/2021/02/valvula-para-bebedero-300x300.png" alt="Válvulas de Bebedero para Lechón y Cerdo">
                                        <p>Válvulas de Bebedero para Lechón y Cerdo</p>
                                    </a>
                                    <a href="producto9.html" class="product-item">
                                        <img src="https://grupointerzenda.co/wp-content/uploads/2021/02/bebedero-conejo-300x300.png" alt="Bebedero para Conejo">
                                        <p>Bebedero para Conejo</p>
                                    </a>
                                </div>
                            </div>
                            <!-- End tab content item -->

                            <div class="tab-pane fade" id="features-tab-2">
                            <div class="product-grid">
                                    <a href="producto1.html" class="product-item">
                                        <img src="https://grupointerzenda.co/wp-content/uploads/2021/02/aguja-desechable.png" alt="Aguja Estéril Desechable">
                                        <p>Aguja Estéril Desechable</p>
                                    </a>
                                    <a href="producto2.html" class="product-item">
                                        <img src="https://grupointerzenda.co/wp-content/uploads/2021/02/jeringa-esteril-desechable-300x300.png" alt="Jeringa Desechable Estéril">
                                        <p>Jeringa Desechable Estéril</p>
                                    </a>
                                    <a href="producto3.html" class="product-item">
                                        <img src="https://grupointerzenda.co/wp-content/uploads/2021/02/aguja-esteril-reusable-300x300.png" alt="Aguja Estéril de Aluminio">
                                        <p>Aguja Estéril de Aluminio</p>
                                    </a>
                                    <a href="producto4.html" class="product-item">
                                        <img src="https://grupointerzenda.co/wp-content/uploads/2021/02/jeringa-reusable-1-300x300.png" alt="Jeringa Reusable">
                                        <p>Jeringa Reusable</p>
                                    </a>
                                    <a href="producto5.html" class="product-item">
                                        <img src="https://grupointerzenda.co/wp-content/uploads/2021/02/aguja-hipodermica-reusable-1-300x300.png" alt="Aguja Hipodérmica Reusable">
                                        <p>Aguja Hipodérmica Reusable</p>
                                    </a>
                                    <a href="producto6.html" class="product-item">
                                        <img src="https://grupointerzenda.co/wp-content/uploads/2021/02/arete-de-identificacion-300x300.png" alt="Arete de Identificación para Ganado">
                                        <p>Arete de Identificación para Ganado</p>
                                    </a>
                                    <a href="producto7.html" class="product-item">
                                        <img src="https://grupointerzenda.co/wp-content/uploads/2021/02/pinza-aretadora-300x300.png" alt="Pinza Aretadora para Ganado">
                                        <p>Pinza Aretadora para Ganado</p>
                                    </a>
                                    <a href="producto8.html" class="product-item">
                                        <img src="https://grupointerzenda.co/wp-content/uploads/2021/02/valvula-para-bebedero-300x300.png" alt="Válvulas de Bebedero para Lechón y Cerdo">
                                        <p>Válvulas de Bebedero para Lechón y Cerdo</p>
                                    </a>
                                    <a href="producto9.html" class="product-item">
                                        <img src="https://grupointerzenda.co/wp-content/uploads/2021/02/bebedero-conejo-300x300.png" alt="Bebedero para Conejo">
                                        <p>Bebedero para Conejo</p>
                                    </a>
                                </div>
                            </div><!-- End tab content item -->

                            <div class="tab-pane fade" id="features-tab-3">
                            <div class="product-grid">
                                    <a href="producto1.html" class="product-item">
                                        <img src="https://grupointerzenda.co/wp-content/uploads/2021/02/aguja-desechable.png" alt="Aguja Estéril Desechable">
                                        <p>Aguja Estéril Desechable</p>
                                    </a>
                                    <a href="producto2.html" class="product-item">
                                        <img src="https://grupointerzenda.co/wp-content/uploads/2021/02/jeringa-esteril-desechable-300x300.png" alt="Jeringa Desechable Estéril">
                                        <p>Jeringa Desechable Estéril</p>
                                    </a>
                                    <a href="producto3.html" class="product-item">
                                        <img src="https://grupointerzenda.co/wp-content/uploads/2021/02/aguja-esteril-reusable-300x300.png" alt="Aguja Estéril de Aluminio">
                                        <p>Aguja Estéril de Aluminio</p>
                                    </a>
                                    <a href="producto4.html" class="product-item">
                                        <img src="https://grupointerzenda.co/wp-content/uploads/2021/02/jeringa-reusable-1-300x300.png" alt="Jeringa Reusable">
                                        <p>Jeringa Reusable</p>
                                    </a>
                                    <a href="producto5.html" class="product-item">
                                        <img src="https://grupointerzenda.co/wp-content/uploads/2021/02/aguja-hipodermica-reusable-1-300x300.png" alt="Aguja Hipodérmica Reusable">
                                        <p>Aguja Hipodérmica Reusable</p>
                                    </a>
                                    <a href="producto6.html" class="product-item">
                                        <img src="https://grupointerzenda.co/wp-content/uploads/2021/02/arete-de-identificacion-300x300.png" alt="Arete de Identificación para Ganado">
                                        <p>Arete de Identificación para Ganado</p>
                                    </a>
                                    <a href="producto7.html" class="product-item">
                                        <img src="https://grupointerzenda.co/wp-content/uploads/2021/02/pinza-aretadora-300x300.png" alt="Pinza Aretadora para Ganado">
                                        <p>Pinza Aretadora para Ganado</p>
                                    </a>
                                    <a href="producto8.html" class="product-item">
                                        <img src="https://grupointerzenda.co/wp-content/uploads/2021/02/valvula-para-bebedero-300x300.png" alt="Válvulas de Bebedero para Lechón y Cerdo">
                                        <p>Válvulas de Bebedero para Lechón y Cerdo</p>
                                    </a>
                                    <a href="producto9.html" class="product-item">
                                        <img src="https://grupointerzenda.co/wp-content/uploads/2021/02/bebedero-conejo-300x300.png" alt="Bebedero para Conejo">
                                        <p>Bebedero para Conejo</p>
                                    </a>
                                </div>
                            </div><!-- End tab content item -->
                        </div>
                        <br>

                        <div class="d-flex justify-content-center">

                            <ul class="nav nav-tabs" data-aos="fade-up" data-aos-delay="100">

                                <li class="nav-item">
                                    <a class="nav-link active show" data-bs-toggle="tab"
                                        data-bs-target="#features-tab-1">
                                        <h4>1</h4>
                                    </a>
                                </li><!-- End tab nav item -->

                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" data-bs-target="#features-tab-2">
                                        <h4>2</h4>
                                    </a><!-- End tab nav item -->

                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" data-bs-target="#features-tab-3">
                                        <h4>3</h4>
                                    </a>
                                </li><!-- End tab nav item -->

                            </ul>

                        </div>



                    </div>

                </section><!-- /Features Section -->

            </section><!-- /Hero Section -->


        </main>
        <!-- FOOTER -->
        <?php include 'FooterEcommerce.php'; ?>

    </div>





    <script src="JS/menu.js"></script>
    <script src="JS/app.js"></script>
    <script src="JS/pedidos.js"></script>

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
</body>

</html>