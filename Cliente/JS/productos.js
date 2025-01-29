function cargarProductos() {
    const numFuncion = 11; // Identificador del caso correspondiente en PHP
    const xhr = new XMLHttpRequest();
    xhr.open("GET", "../Servidor/PHP/ventas.php?numFuncion=" + numFuncion, true);
    xhr.setRequestHeader("Content-Type", "application/json");
    xhr.onload = function () {
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
  
    xhr.onerror = function () {
      alert("Hubo un problema con la conexión.");
    };
  
    xhr.send();
  }

  function mostrarProductosCuadricula(productos) {
    const contenedorProductos = document.querySelector(".product-grid");
  
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
        productItem.className = "card";
  
        // Generar carrusel de imágenes si el producto tiene múltiples imágenes
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
  
        // Crear la sección de información
        const infoCard = document.createElement("div");
        infoCard.className = "info-card";
        infoCard.innerHTML = `
            <div class="text-product">
                <h9>${producto.DESCR}</h9>
                <p class="category">Detalles</p>
            </div>
            <div class="price">$${parseFloat(producto.EXIST).toFixed(2)}</div>
        `;
  
        // Agregar evento solo a la información
        infoCard.addEventListener("click", (event) => {
            event.stopPropagation(); // Evita que se active un clic en el `productItem`
            abrirModalProducto(producto);
        });
  
        // Añadir elementos al `productItem`
        productItem.innerHTML = `${carruselHtml}`;
        productItem.appendChild(infoCard);
  
        // Agregar el producto al contenedor
        contenedorProductos.appendChild(productItem);
    });
  }





  document.addEventListener("DOMContentLoaded", () => {
    // alert("Cargando productos...");
    cargarProductos();
  });