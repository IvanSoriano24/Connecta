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
    $tipoUsuario   = $_SESSION['usuario']["tipoUsuario"];
    $claveCliente = $_SESSION['usuario']['claveUsuario'];
} else {
    header('Location:../index.php');
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>MDConnecta</title>
    <meta name="description" content="">
    <meta name="keywords" content="">


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
    <style>
        .hero-img {
            width: 100%;
            height: 280px;
            background: url('SRC/prueba.jpg') no-repeat center center/cover;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
        }

        @media (max-width: 768px) {
            .hero-img {
                height: 100%;
            }
        }

        .gallery {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            padding: 20px;
            max-width: 100%;
        }

        .gallery-item {
            position: relative;
            overflow: hidden;
            width: 100%;
            aspect-ratio: 1 / 1;
        }

        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            border-radius: 8px;
        }

        .gallery-item .overlay {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            width: 80%;
        }

        .grid-four {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            padding: 20px;
            max-width: 100%;
        }

        @media (max-width: 1024px) {
            .grid-four {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .gallery {
                max-width: 60%;
                margin: auto;
            }

            .grid-four {
                grid-template-columns: repeat(1, 1fr);
            }
        }
    </style>

    <style>
        body.modal-open .hero_area {
            filter: blur(5px);
            /* Difumina el fondo mientras un modal está abierto */
        }

        /*************************************************************/

        /*************************************************************/

        /* Asegurar que #hero sea flexbox para alinear el sidebar y el contenido */
        .hero-container {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            width: 100%;
        }

        /* Sidebar alineado a la izquierda */
        #sidebar {
            width: 280px;
            /* Ancho fijo */
            min-height: 100%;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 2px 0px 5px rgba(0, 0, 0, 0.1);
            background: inherit;
            /* Hereda el fondo del hero */
        }

        /* Contenido principal ocupa el resto del espacio */
        .hero-content-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        /* Evitar que el contenido se desplace mal */
        .hero-content h1 {
            white-space: nowrap;
            /* Evita que el texto baje de línea */
            font-size: 2.5rem;
        }

        /* Ajustes responsivos */
        @media (max-width: 992px) {
            .hero-container {
                flex-direction: column;
                align-items: center;
            }

            #sidebar {
                width: 100%;
                min-height: auto;
                text-align: center;
            }
        }
    </style>
    <!-- CSS PARA CARDS -->
    <style>
        /* ********************************************** */
        /* ESTILOS CARD MUETSRA DE MAS VENDIDOS  */
        .card {
            width: 190px;
            background: white;
            padding: .4em;
            border-radius: 6px;
        }

        .card-image {
            background-color: rgb(236, 236, 236);
            width: 100%;
            height: 130px;
            border-radius: 6px 6px 0 0;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 6px 6px 0 0;
        }

        .carousel-item {
            padding: 20px 0;
        }

        .carousel-control-prev-icon,
        .carousel-control-next-icon {
            background-color: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
        }
    </style>
</head>

<body>
    <div>

    </div>
    <div class="hero_area">
        <!-- HEADER -->
        <?php include 'HeaderEcommerce.php'; ?>

        <main class="main">

            <!-- SIDEBAR CATEGORIAS -->

            <!-- FIN SIDEBAR Categoris -->

            <!-- Hero Section -->
            <section id="hero" class="hero section">
                <div class="container hero-container">
                    <!-- Sidebar de Categorías -->
                    <nav id="sidebar" class="sidebar">
                        <div class="pricing-card">
                            <h3 class="text-center">Categorias</h3>
                            <hr>
                            <p class="description text-center">Bienvenidos a las categorías</p>
                            <!-- <a href="#" class="btn btn-primary d-block text-center">
                                Comprar
                                <i class="bi bi-arrow-right"></i>
                            </a> -->

                        </div>
                    </nav>

                    <!-- Contenido Principal -->
                    <div class="hero-content-container">
                        <div class="hero-content">
                            <div class="row align-items-center">
                                <div class="col-lg-6">
                                    <div class="hero-content" data-aos="fade-up" data-aos-delay="200">

                                        <h1 class="mb-4">
                                            GRUPO <br>
                                            <span class="accent-text"> INTERZENDA</span>

                                        </h1>

                                        <!-- Boton para comprar
                                        <div class="hero-buttons">
                                            <a href="Articulos.php" class="btn btn-primary me-0 me-sm-2 mx-1">Comprar</a>
                                            </a>
                                        </div> -->
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="hero-image" data-aos="zoom-out" data-aos-delay="300">
                                        <img src="SRC/imagen.png" alt="Logo Principal" width="700">
                                    </div>
                                </div>
                            </div>
                            <h2 class="text-center">Productos mas vendidos</h2>
                            <div id="product-list">
                                <!-- El carrusel se insertará aquí dinámicamente -->
                            </div>


                            <hr>
                            <div class="container mt-5">
                                <div class="hero-img">
                                    ACCESORIOS PARA HOMBRE
                                </div>

                                <div class="grid-four">
                                    <div class="gallery-item">
                                        <img src="SRC/prueba.jpg" alt="Individuales">
                                        <div class="overlay">INDIVIDUALES</div>
                                    </div>
                                    <div class="gallery-item">
                                        <img src="SRC/prueba.jpg" alt="Matrimoniales">
                                        <div class="overlay">MATRIMONIALES</div>
                                    </div>
                                    <div class="gallery-item">
                                        <img src="SRC/prueba.jpg" alt="Queen Size">
                                        <div class="overlay">QUEEN SIZE</div>
                                    </div>
                                    <div class="gallery-item">
                                        <img src="SRC/prueba.jpg" alt="King Size">
                                        <div class="overlay">KING SIZE</div>
                                    </div>
                                </div>

                                <div class="gallery">
                                    <div class="gallery-item">
                                        <img src="SRC/prueba.jpg" alt="Tenis Sneakers">
                                        <div class="overlay">Tenis Sneakers</div>
                                    </div>
                                    <div class="gallery-item">
                                        <img src="SRC/prueba.jpg" alt="Botas">
                                        <div class="overlay">Botas</div>
                                    </div>
                                    <!-- <div class="gallery-item">
                                    <img src="SRC/prueba.jpg" alt="Camisas">
                                    <div class="overlay">Camisas</div>
                                </div>
                                <div class="gallery-item">
                                    <img src="SRC/prueba.jpg" alt="Polos">
                                    <div class="overlay">Polos</div>
                                </div>
                                <div class="gallery-item">
                                    <img src="SRC/prueba.jpg" alt="Jeans">
                                    <div class="overlay">Jeans</div>
                                </div>
                                <div class="gallery-item">
                                    <img src="SRC/prueba.jpg" alt="Pantalones">
                                    <div class="overlay">Pantalones</div>
                                </div> -->
                                </div>
                                <div class="grid-four">
                                    <div class="gallery-item">
                                        <img src="SRC/prueba.jpg" alt="Individuales">
                                        <div class="overlay">INDIVIDUALES</div>
                                    </div>
                                    <div class="gallery-item">
                                        <img src="SRC/prueba.jpg" alt="Matrimoniales">
                                        <div class="overlay">MATRIMONIALES</div>
                                    </div>
                                    <div class="gallery-item">
                                        <img src="SRC/prueba.jpg" alt="Queen Size">
                                        <div class="overlay">QUEEN SIZE</div>
                                    </div>
                                    <div class="gallery-item">
                                        <img src="SRC/prueba.jpg" alt="King Size">
                                        <div class="overlay">KING SIZE</div>
                                    </div>
                                </div>
                            </div>


                        </div>
                    </div>
            </section>

        </main>

        <!-- FOOTER -->
        <?php include 'FooterEcommerce.php'; ?>

    </div>
    <!-- Scroll Top -->
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i
            class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- JS Para la confirmacion empresa -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>


    <script src="JS/menu.js"></script>
    <script src="JS/app.js"></script>
    <!-- <script src="JS/articulos.js"></script> -->
    <script>
        async function cargarProductos() {
            try {
                // 1️⃣ Obtener los datos del cliente para obtener LISTA_PREC
                const datosCliente = await obtenerDatosCliente();
                const listaPrecioCliente = datosCliente?.LISTA_PREC || "1"; // Usar "1" si no hay lista de precios

                // 2️⃣ Hacer la solicitud a la API de productos pasando LISTA_PREC
                const numFuncion = 17;
                const url = `../Servidor/PHP/ventas.php?numFuncion=${numFuncion}&listaPrecioCliente=${listaPrecioCliente}`;

                const response = await fetch(url, {
                    method: "GET",
                    headers: {
                        "Content-Type": "application/json",
                    },
                });

                const data = await response.json();

                if (data.sinDatos) {
                    Swal.fire({
                        title: "Sin dato",
                        text: `El sistema no encontro pedidos completados anteriores`,
                        icon: "info",
                        confirmButtonText: "Aceptar"
                    });
                    return;
                }
                if (!data.success) {
                    console.error("Error desde el servidor:", data.message);
                    return;
                }

                // 3️⃣ Mostrar los productos en la cuadrícula
                mostrarProductosCuadricula(data.productos);
            } catch (error) {
                console.error("Error en cargarProductos:", error);
                alert("Error al cargar los productos.");
            }
        }
        async function obtenerDatosCliente() {
            try {
                const response = await fetch("../Servidor/PHP/clientes.php?numFuncion=4");
                const data = await response.json();

                if (!data.success) {
                    console.error("Error al obtener datos del cliente:", data.message);
                    return {
                        cvePrecio: "1"
                    }; // Valor predeterminado si hay error
                }
                const datos = data[0];
                return datos;
                /*const cliente = data.data[0]; // Obtener el primer cliente de la respuesta
                    const cvePrecio = cliente?.LISTA_PREC || "1"; // Si LISTA_PREC es null, usar "1"

                    return { cvePrecio };*/
            } catch (error) {
                console.error("Error en la solicitud:", error);
            }
        }

        function mostrarProductosCuadricula(productos) {
            const contenedorProductos = document.getElementById("product-list");

            if (!contenedorProductos) {
                console.error("Error: No se encontró el contenedor para los productos.");
                return;
            }

            contenedorProductos.innerHTML = ""; // Limpia el contenedor antes de agregar productos

            if (!Array.isArray(productos) || productos.length === 0) {
                contenedorProductos.innerHTML = "<p class='no-products'>No hay productos para mostrar.</p>";
                return;
            }

            // Crear estructura del carrusel
            const carousel = document.createElement("div");
            carousel.id = "productCarousel";
            carousel.className = "carousel slide";
            carousel.setAttribute("data-bs-ride", "carousel");

            // Contenedor de slides
            const carouselInner = document.createElement("div");
            carouselInner.className = "carousel-inner";

            let itemsPorSlide = 3; // Número de productos por slide
            let totalSlides = Math.ceil(productos.length / itemsPorSlide); // Calcular cantidad de slides

            for (let i = 0; i < totalSlides; i++) {
                const carouselItem = document.createElement("div");
                carouselItem.className = `carousel-item ${i === 0 ? 'active' : ''}`;

                // Contenedor de productos dentro de cada slide
                const slideContent = document.createElement("div");
                slideContent.className = "d-flex justify-content-center gap-3";

                // Agregar productos al slide
                for (let j = i * itemsPorSlide; j < (i + 1) * itemsPorSlide && j < productos.length; j++) {
                    const producto = productos[j];

                    let imagenSrc = "SRC/noimg.png";
                    if (Array.isArray(producto.IMAGEN_ML) && producto.IMAGEN_ML.length > 0) {
                        imagenSrc = producto.IMAGEN_ML[0];
                    }

                    // Estructura de la tarjeta
                    const productCard = `
                <div class="card">
                    <div class="card-image">
                        <img src="${imagenSrc}" alt="${producto.DESCR}" class="product-img">
                    </div>
                    <div class="category"> Producto </div>
                    <div class="heading">
                        ${producto.DESCR}
                        <div class="author"> Precio: <span class="name">$${parseFloat(producto.PRECIO).toFixed(2)}</span></div>
                    </div>
                </div>
            `;

                    // Agregar la tarjeta al contenedor del slide
                    slideContent.innerHTML += productCard;
                }

                // Agregar los productos al slide y después al carrusel
                carouselItem.appendChild(slideContent);
                carouselInner.appendChild(carouselItem);
            }

            // Botones de navegación del carrusel
            const prevButton = `
                <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                </button>
            `;

            const nextButton = `
                <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                </button>
            `;

            // Agregar los elementos al carrusel
            carousel.appendChild(carouselInner);
            carousel.innerHTML += prevButton + nextButton;

            // Agregar el carrusel al contenedor de productos
            contenedorProductos.appendChild(carousel);
        }





        // Llamar a la función cuando la página esté lista
        document.addEventListener("DOMContentLoaded", cargarProductos);
    </script>

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