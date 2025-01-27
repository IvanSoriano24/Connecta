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
        contenedorProductos.innerHTML = "<p>No hay productos para mostrar.</p>";
        return;
    }

    productos.forEach((producto) => {
        const productItem = document.createElement("div");
        productItem.className = "product-item";

        // Generar carrusel de imágenes
        let carruselHtml = "";
        if (Array.isArray(producto.IMAGEN_ML) && producto.IMAGEN_ML.length > 0) {
            carruselHtml = `
                <div id="carrusel-${producto.CVE_ART}" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        ${producto.IMAGEN_ML.map((imgUrl, index) => `
                            <div class="carousel-item ${index === 0 ? 'active' : ''}">
                                <img src="${imgUrl}" class="d-block w-100" alt="Imagen del Producto">
                            </div>
                        `).join("")}
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#carrusel-${producto.CVE_ART}" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Anterior</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#carrusel-${producto.CVE_ART}" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Siguiente</span>
                    </button>
                </div>
            `;
        } else {
            carruselHtml = `<img src="https://via.placeholder.com/150" alt="${producto.DESCR}" class="product-img">`;
        }

        productItem.innerHTML = `
            ${carruselHtml}
            <p class="product-name">${producto.DESCR}</p>
            <p class="product-stock">Existencia: ${producto.EXIST}</p>
            <button class="btn btn-agregar">Agregar al carrito</button>
            <button class="btn btn-detalles">Detalles</button>
        `;

        // Agregar eventos a los botones
        const btnAgregar = productItem.querySelector(".btn-agregar");
        const btnDetalles = productItem.querySelector(".btn-detalles");

        btnAgregar.onclick = () => agregarAlCarrito(producto);
        btnDetalles.onclick = () => abrirModalProducto(producto);

        // Agregar el producto al contenedor
        contenedorProductos.appendChild(productItem);
    });
}
/*function mostrarProductosCuadricula(productos) {
    const contenedorProductos = document.querySelector(".product-grid");

    if (!contenedorProductos) {
        console.error("Error: No se encontró el contenedor para los productos.");
        return;
    }

    contenedorProductos.innerHTML = ""; // Limpia el contenedor antes de agregar productos

    if (!Array.isArray(productos) || productos.length === 0) {
        contenedorProductos.innerHTML = "<p>No hay productos para mostrar.</p>";
        return;
    }

    productos.forEach((producto) => {
        const productItem = document.createElement("div");
        productItem.className = "product-item";

        productItem.innerHTML = `
            <img src="${producto.IMAGEN_ML}" alt="${producto.DESCR}" class="product-img">
            <p class="product-name">${producto.DESCR}</p>
            <p class="product-stock">Existencia: ${producto.EXIST}</p>
            <a href="detalleProducto.php?cveArt=${encodeURIComponent(producto.CVE_ART)}" class="btn btn-detalles">Ver Detalles</a>
        `;

        contenedorProductos.appendChild(productItem);
    });
}*/

// Función para abrir el modal
function abrirModalProducto(producto) {
    const modal = document.getElementById("productModal");
    document.getElementById("modal-title").textContent = producto.DESCR;
    document.getElementById("modal-image").src = producto.IMAGEN_ML || "https://via.placeholder.com/150";
    document.getElementById("modal-description").textContent = `Descripción: ${producto.DESCR}`;
    document.getElementById("modal-existencia").textContent = `Existencia: ${producto.EXIST}`;
    document.getElementById("modal-lin-prod").textContent = `Línea del Producto: ${producto.LIN_PROD}`;

    const btnAddToCart = document.getElementById("btn-add-to-cart");
    btnAddToCart.onclick = () => {
        agregarAlCarrito(producto);
        cerrarModal();
    };

    modal.style.display = "block";
}

// Función para cerrar el modal
function cerrarModal() {
    const modal = document.getElementById("productModal");
    modal.style.display = "none";
}

// Agregar eventos de cierre
document.addEventListener("DOMContentLoaded", () => {
const modal = document.getElementById("productModal");
const closeModalButton = document.getElementById("closeModal");

// Cerrar modal al hacer clic en el botón "Cerrar"
closeModalButton.addEventListener("click", cerrarModal);

// Cerrar modal al hacer clic fuera del contenido
window.addEventListener("click", (event) => {
if (event.target === modal) {
  cerrarModal();
}
});
});



// Mostrar productos en la cuadrícula
document.addEventListener("DOMContentLoaded", () => {
const contenedorProductos = document.querySelector(".product-grid");

productos.forEach((producto) => {
const productItem = document.createElement("div");
productItem.className = "product-item";

const img = document.createElement("img");
img.src = producto.IMAGEN_ML || "https://via.placeholder.com/150";
img.alt = producto.DESCR;

const nombre = document.createElement("p");
nombre.textContent = producto.DESCR;

const existencia = document.createElement("p");
existencia.textContent = `Existencia: ${producto.EXIST}`;

const btnDetalles = document.createElement("button");
btnDetalles.textContent = "Detalles";
btnDetalles.className = "btn-detalles";
btnDetalles.addEventListener("click", () => abrirModalProducto(producto));

productItem.appendChild(img);
productItem.appendChild(nombre);
productItem.appendChild(existencia);
productItem.appendChild(btnDetalles);

contenedorProductos.appendChild(productItem);
});
});

// -----------------------------------------------------------------------------
document.addEventListener("DOMContentLoaded", () => {
    // alert("Cargando productos...");
    cargarProductos();
});
    

// ----------FUNCIONES CARRITO-------------------------------------------------------------------

function agregarAlCarrito(producto) {
    let carrito = JSON.parse(localStorage.getItem("carrito")) || [];

    const productoExistente = carrito.find((item) => item.CVE_ART === producto.CVE_ART);

    if (productoExistente) {
        productoExistente.cantidad += 1;
    } else {
        producto.cantidad = 1;
        carrito.push(producto);
    }

    localStorage.setItem("carrito", JSON.stringify(carrito));
    alert(`Producto agregado al carrito: ${producto.DESCR}`);
}
  