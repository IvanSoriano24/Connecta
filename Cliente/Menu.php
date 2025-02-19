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
} else {
  header('Location:../index.php');
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Menu Principal</title>
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
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Inter:wght@100;200;300;400;500;600;700;800;900&family=Nunito:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

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
    body.modal-open .hero_area {
      filter: blur(5px);
      /* Difumina el fondo mientras un modal está abierto */
    }

    /*************************************************************/
    /* Contenedor con scroll horizontal */
    /* Contenedor con scroll horizontal */
    .product-container {
      width: 100%;
      overflow-x: auto;
      padding: 10px;
      scrollbar-width: thin;
      scrollbar-color: #ccc #f1f1f1;
      position: relative;
    }

    /* Estilos personalizados para scrollbar en Chrome, Edge y Safari */
    .product-container::-webkit-scrollbar {
      height: 8px;
    }

    .product-container::-webkit-scrollbar-thumb {
      background-color: #007bff;
      border-radius: 5px;
    }

    .product-container::-webkit-scrollbar-track {
      background-color: #f1f1f1;
    }

    /* Fila de productos con flexbox */
    .product-grid {
      display: flex;
      flex-wrap: nowrap;
      gap: 20px;
      padding-bottom: 10px;
    }

    /* Estilos de cada tarjeta de producto */
    .product-card {
      flex: 0 0 calc(33.33% - 20px);
      max-width: calc(33.33% - 20px);
      border: 1px solid #ddd;
      border-radius: 10px;
      background-color: #fff;
      box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
      padding: 15px;
      text-align: center;
      transition: transform 0.3s ease-in-out;
    }

    .product-card:hover {
      transform: scale(1.05);
    }

    /* Imagen del producto */
    .product-card img {
      width: 100%;
      height: 180px;
      object-fit: cover;
      border-bottom: 1px solid #ddd;
    }

    /* Título del producto */
    .product-card h3 {
      font-size: 16px;
      margin: 10px 0;
      color: #333;
    }

    /* Precio del producto */
    .product-card .price {
      font-size: 14px;
      font-weight: bold;
      color: #28a745;
    }

    /* Botón de compra */
    .product-card .btn {
      display: block;
      width: 100%;
      padding: 8px;
      margin-top: 10px;
      background-color: #007bff;
      color: #fff;
      text-decoration: none;
      border-radius: 5px;
      transition: background-color 0.3s;
    }

    .product-card .btn:hover {
      background-color: #0056b3;
    }

    /* Ajuste responsivo */
    @media (max-width: 992px) {
      .product-card {
        flex: 0 0 calc(50% - 20px);
        max-width: calc(50% - 20px);
      }
    }

    @media (max-width: 576px) {
      .product-card {
        flex: 0 0 100%;
        max-width: 100%;
      }
    }
    /*************************************************************/
  </style>

</head>

<body>
  <div>

  </div>
  <div class="hero_area">
    <!-- HEADER -->
    <?php include 'HeaderEcommerce.php'; ?>

    <main class="main">

      <!-- Hero Section -->
      <section id="hero" class="hero section">

        <div class="container" data-aos="fade-up" data-aos-delay="100">

          <div class="row align-items-center">
            <div class="col-lg-6">
              <div class="hero-content" data-aos="fade-up" data-aos-delay="200">

                <h1 class="mb-4">
                  GRUPO <br>
                  <span class="accent-text"> INTERZENDA</span>
                </h1>

                <div class="hero-buttons">
                  <a href="Articulos.php" class="btn btn-primary me-0 me-sm-2 mx-1">Comprar</a>
                  </a>
                </div>
              </div>
            </div>

            <div class="col-lg-6">
              <div class="hero-image" data-aos="zoom-out" data-aos-delay="300">
                <img src="SRC/imagen.png" alt="Logo Principal" width="700">
              </div>
            </div>
          </div>

          <div class="row stats-row gy-4 mt-5" data-aos="fade-up" data-aos-delay="500">
            <div class="container">
              <div class="tab-content" data-aos="fade-up" data-aos-delay="200">
                <div class="tab-pane fade active show" id="features-tab-1">
                  <div class="container">
                    <div class="product-container">
                      <div class="product-grid" id="product-list">
                        <!-- Los productos se cargarán dinámicamente aquí -->
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>

      </section><!-- /Hero Section -->

    </main>

    <!-- FOOTER -->
    <?php include 'FooterEcommerce.php'; ?>

  </div>
  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- JS Para la confirmacion empresa -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>


  <script src="JS/menu.js"></script>
  <script src="JS/app.js"></script>
  <!-- <script src="JS/articulos.js"></script> -->
  <script>
    function cargarProductos() {
      const numFuncion = 17; // Identificador para el caso en PHP
      const xhr = new XMLHttpRequest();
      xhr.open("GET", "../Servidor/PHP/ventas.php?numFuncion=" + numFuncion, true);
      xhr.setRequestHeader("Content-Type", "application/json");
      xhr.onload = function() {
        if (xhr.status === 200) {
          try {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
              mostrarProductosCuadricula(response.productos);
            } else {
              alert("Error desde el servidor: " + response.message);
            }
          } catch (error) {
            alert("Error al analizar JSON: " + error.message);
          }
        } else {
          alert("Error en la respuesta HTTP: " + xhr.status);
        }
      };

      xhr.onerror = function() {
        alert("Hubo un problema con la conexión.");
      };

      xhr.send();
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

      productos.forEach((producto) => {
        const productItem = document.createElement("div");
        productItem.className = "product-card";

        // Generar el carrusel de imágenes si hay múltiples imágenes
        let carruselHtml = "";
        if (Array.isArray(producto.IMAGEN_ML) && producto.IMAGEN_ML.length > 0) {
          carruselHtml = `
                <div id="carrusel-${producto.CVE_ART}" class="carousel slide card-img" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        ${producto.IMAGEN_ML.map((imgUrl, index) => `
                            <div class="carousel-item ${index === 0 ? 'active' : ''}">
                                <img src="${imgUrl}" class="d-block w-100 product-img" alt="${producto.DESCR}">
                            </div>
                        `).join("")}
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#carrusel-${producto.CVE_ART}" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#carrusel-${producto.CVE_ART}" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    </button>
                </div>
            `;
        } else {
          carruselHtml = `<div class="card-img">
                <img src="SRC/noimg.png" alt="${producto.DESCR}" class="product-img">
            </div>`;
        }

        // Crear la información del producto
        productItem.innerHTML = `
            ${carruselHtml}
            <h3>${producto.DESCR}</h3>
            <p class="price">$${parseFloat(producto.EXIST).toFixed(2)}</p>
            <a href="#" class="btn">Comprar</a>
        `;

        // Agregar el producto al contenedor
        contenedorProductos.appendChild(productItem);
      });
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