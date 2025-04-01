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
    <link rel="stylesheet" href="CSS/articulos.css">

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
                console.log(data.productos);
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

                    // Usando JSON.stringify para pasar el objeto producto y el precio
                    // Se envuelve el JSON con comillas simples para evitar conflictos
                    const productoJSON = encodeURIComponent(JSON.stringify(producto));

                    // Estructura de la tarjeta con atributo onclick
                    const productCard = `
                <div class="card" onclick='abrirModalProducto(JSON.parse(decodeURIComponent("${productoJSON}")), ${producto.PRECIO})'>
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

        function abrirModalProducto(producto, precio) {
            const modal = document.getElementById("productModal");
            if (!modal) {
                console.error("Error: No se encontró el modal");
                return;
            }
            //añadirEvento(producto.CVE_ART);
            // Agregar información del producto
            document.getElementById("modal-title").textContent = producto.DESCR;
            document.getElementById("modal-price").textContent = `$${parseFloat(
                precio
            ).toFixed(2)}`;
            document.getElementById("modal-description").textContent = producto.DESCR;
            document.getElementById(
                "modal-lin-prod"
            ).textContent = `Línea del Producto: ${producto.LIN_PROD}`;

            // Generar carrusel de imágenes dentro del modal
            const modalInner = document.getElementById("modal-carousel-inner");
            modalInner.innerHTML =
                Array.isArray(producto.IMAGEN_ML) && producto.IMAGEN_ML.length > 0 ?
                producto.IMAGEN_ML.map(
                    (imgUrl, index) => `
            <div class="carousel-item ${index === 0 ? "active" : ""}">
                <img src="${imgUrl}" class="d-block w-100" alt="Imagen del Producto">
            </div>
            `
                ).join("") :
                `<div class="carousel-item active">
                <img src="SRC/noimg.png" class="d-block w-100" alt="Imagen no disponible">
           </div>`;

            // Resetear el valor del input de cantidad
            document.getElementById("cantidadProducto").value = 1;

            // Configurar el botón "Agregar al carrito"
            const btnAddToCart = document.getElementById("btn-add-to-cart");

            // Eliminar cualquier evento previo
            btnAddToCart.onclick = null;

            // Asignar nuevo evento al botón
            btnAddToCart.onclick = () => {
                const cantidad = parseInt(
                    document.getElementById("cantidadProducto").value
                );

                // Validar cantidad
                if (!cantidad || cantidad <= 0) {
                    Swal.fire({
                        title: "Cantidad no válida",
                        text: "Por favor, ingresa una cantidad mayor a 0.",
                        icon: "error",
                        confirmButtonText: "Aceptar",
                    });
                    return;
                }

                // Agregar al carrito
                agregarAlCarrito(producto, cantidad, precio);

                // Llamar a cerrar el modal
                //cerrarModal2();

                // Mostrar la alerta después de cerrar el modal
                setTimeout(() => {
                    Swal.fire({
                        title: "Producto agregado",
                        text: `${cantidad} unidad(es) de "${producto.DESCR}" se agregó correctamente al carrito.`,
                        icon: "success",
                        confirmButtonText: "Aceptar",
                    });
                }, 300); // Pequeño retraso para evitar solapamiento
            };

            // Mostrar el modal
            modal.style.display = "flex";
            modal.style.opacity = "1";
            modal.style.visibility = "visible";
        }

        function cerrarModal2() {
            const modal = document.getElementById("productModal");

            if (!modal) {
                // alert("Error: No se encontró el modal");
                console.error("Error: No se encontró el modal");
                return;
            }

            // Ocultar el modal cambiando el estilo
            modal.style.display = "none";
            modal.style.opacity = "0";
            modal.style.visibility = "hidden";
        }

        function añadirEvento(CVE_ART) {
            $.ajax({
                url: "../Servidor/PHP/tblControl.php",
                type: "GET",
                data: {
                    numFuncion: "1",
                    CVE_ART: CVE_ART
                },
                success: function(response) {
                    try {
                        const res =
                            typeof response === "string" ? JSON.parse(response) : response;
                        if (res.success) {
                            console.log("Evento Guardado");
                        } else {
                            console.log("Evento No Guardado");
                        }
                    } catch (error) {
                        console.error("Error al procesar la respuesta de clientes:", error);
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "Error al obtener la lista de clientes.",
                    });
                },
            });
        }

        async function obtenerImpuesto(cveEsqImpu) {
            try {
                const response = await fetch("../Servidor/PHP/ventas.php", {
                    method: "POST",
                    body: new URLSearchParams({
                        cveEsqImpu: cveEsqImpu,
                        numFuncion: "7",
                    }),
                });

                const data = await response.json();

                if (!data.success) {
                    console.error("Error del servidor:", data.message);
                    return {
                        impuesto1: 0,
                        impuesto2: 0,
                        impuesto3: 0,
                        impuesto4: 0
                    };
                }

                return {
                    impuesto1: parseFloat(data.impuestos.IMPUESTO1) || 0,
                    impuesto2: parseFloat(data.impuestos.IMPUESTO2) || 0,
                    impuesto3: parseFloat(data.impuestos.IMPUESTO3) || 0,
                    impuesto4: parseFloat(data.impuestos.IMPUESTO4) || 0,
                };
            } catch (error) {
                console.error("Error en la solicitud de impuestos:", error);
                return {
                    impuesto1: 0,
                    impuesto2: 0,
                    impuesto3: 0,
                    impuesto4: 0
                };
            }
        }
        async function completarPrecioProducto(producto, cantidad, precioBase) {
            try {
                const CVE_ESQIMPU = producto.CVE_ESQIMPU || "1"; // Si no tiene esquema de impuestos, se asigna "1"

                // Obtener impuestos
                const impuestos = await obtenerImpuesto(CVE_ESQIMPU);

                // Calcular el precio final con impuestos
                const impuestoTotal =
                    (precioBase *
                        (impuestos.impuesto1 + impuestos.impuesto4 + impuestos.impuesto3)) /
                    100;
                const precioFinal = precioBase + impuestoTotal;

                return {
                    impuestos,
                };
            } catch (error) {
                console.error("Error al calcular el precio del producto:", error);
            }
        }
        async function agregarAlCarrito(producto, cantidad = 1, precio) {
            let carrito = JSON.parse(localStorage.getItem("carrito")) || [];
            // Buscar si el producto ya está en el carrito
            let productoExistente = carrito.find((item) => item.CVE_ART === producto.CVE_ART);

            if (productoExistente) {
                // 📌 Si ya existe, sumamos la cantidad nueva
                productoExistente.cantidad += cantidad;
            } else {
                // 📌 Si no existe, lo agregamos con la cantidad seleccionada
                let imagenProducto =
                    Array.isArray(producto.IMAGEN_ML) && producto.IMAGEN_ML.length > 0 ?
                    producto.IMAGEN_ML[0] // Usar la primera imagen si hay varias
                    :
                    "SRC/noimg.png"; // Imagen de respaldo si no hay

                const datosProducto = await completarPrecioProducto(producto, cantidad, precio);
                let nuevoProducto = {
                    CVE_ART: producto.CVE_ART,
                    DESCR: producto.DESCR,
                    precioUnitario: parseFloat(precio) || 10, // 🛠️ Guardamos con un nombre estándar
                    cantidad: cantidad,
                    IMAGEN_ML: imagenProducto,
                    unidad: producto.UNI_MED,
                    descuento1: 0,
                    descuento2: 0,
                    ieps: datosProducto.impuestos.impuesto1,
                    impuesto2: datosProducto.impuestos.impuesto2,
                    isr: datosProducto.impuestos.impuesto3,
                    iva: datosProducto.impuestos.impuesto4,
                    comision: 0,
                    subtotal: cantidad * (parseFloat(precio) || 10),
                    CVE_UNIDAD: producto.CVE_UNIDAD,
                    COSTO_PROM: producto.COSTO_PROM,
                };
                carrito.push(nuevoProducto);
            }

            // Guardamos la actualización en localStorage
            localStorage.setItem("carrito", JSON.stringify(carrito));
            //mostrarCarrito(); // Asegurar que la vista se actualice
        }
        // Llamar a la función cuando la página esté lista
        document.addEventListener("DOMContentLoaded", cargarProductos);
        document.addEventListener("DOMContentLoaded", () => {
            const closeModalButton = document.querySelector(".close");
            const modal = document.getElementById("productModal");

            if (!closeModalButton) {
                // alert("Error: No se encontró el botón de cerrar");
                console.error("Error: No se encontró el botón de cerrar");
                return;
            }

            closeModalButton.addEventListener("click", () => {
                // alert("Botón de cerrar clickeado");
                console.log("Botón de cerrar clickeado");
                cerrarModal2();
            });

            // Cerrar modal al hacer clic fuera del contenido
            window.addEventListener("click", (event) => {
                if (event.target === modal) {
                    // alert("Clic fuera del modal, cerrando...");
                    console.log("Clic fuera del modal, cerrando...");
                    cerrarModal2();
                }
            });
        });
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