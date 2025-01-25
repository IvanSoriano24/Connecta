

function cargarProductos() {
    const numFuncion = 11; // Identificador del caso correspondiente en PHP
    const xhr = new XMLHttpRequest();
    xhr.open("GET", "../Servidor/PHP/ventas.php?numFuncion=" + numFuncion, true);
    xhr.setRequestHeader("Content-Type", "application/json");

    xhr.onload = function() {
        // alert("Estado HTTP: " + xhr.status); // Mostrar el código de estado HTTP
        if (xhr.status === 200) {
            // alert("Respuesta recibida: " + xhr.responseText); // Mostrar la respuesta completa del servidor
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    //   alert("Productos recibidos: " + response.productos.length);
                    mostrarProductosCuadricula(response.productos); // Llama a la función para renderizar
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
    const contenedorProductos = document.querySelector(".product-grid");

    if (!contenedorProductos) {
        alert("Error: No se encontró el contenedor para los productos.");
        return;
    }

    // Limpiar el contenedor antes de agregar productos
    contenedorProductos.innerHTML = "";

    if (!Array.isArray(productos) || productos.length === 0) {
        alert("No hay productos para mostrar.");
        return;
    }

    // Iterar sobre los productos para generar el contenido dinámico
    productos.forEach((producto, index) => {
        const productItem = document.createElement("div");
        productItem.className = "product-item";

        const img = document.createElement("img");
        img.src = producto.IMAGEN_ML ||
            "ruta/imagen_por_defecto.png"; // Imagen por defecto si IMAGEN_ML es null
        img.alt = producto.DESCR;

        const nombre = document.createElement("p");
        nombre.textContent = producto.DESCR;

        const existencia = document.createElement("p");
        existencia.textContent = `Existencia: ${producto.EXIST}`;

        // Crear botón "Agregar"
        const btnAgregar = document.createElement("button");
        btnAgregar.textContent = "Agregar Carrito";
        btnAgregar.className = "btn-agregar";
        
        btnAgregar.onclick = function () {
            agregarAlCarrito(producto); 
            alert(`Producto agregado: ${producto.CVE_ART} - ${producto.DESCR}`);// Llama a la función para agregar al carrito
          };
          
       

        // Crear botón "Detalles"
        const btnDetalles = document.createElement("button");
        btnDetalles.textContent = "Detalles";
        btnDetalles.className = "btn-detalles";
        btnDetalles.onclick = function() {
            abrirModalProducto(producto);
        };

        productItem.appendChild(img);
        productItem.appendChild(nombre);
        productItem.appendChild(existencia);
        productItem.appendChild(btnAgregar);
        productItem.appendChild(btnDetalles);

        contenedorProductos.appendChild(productItem);
    });

    // alert("Productos renderizados correctamente con botones.");
}

// Función para abrir el modal
function abrirModalProducto(producto) {
    const modal = document.getElementById("productModal");
    const modalTitle = document.getElementById("modal-title");
    const modalImage = document.getElementById("modal-image");
    const modalDescription = document.getElementById("modal-description");
    const modalExistencia = document.getElementById("modal-existencia");
    const modalLinProd = document.getElementById("modal-lin-prod");
    const btnAddToCart = document.getElementById("btn-add-to-cart");
  
    // Rellenar información del modal
    modalTitle.textContent = producto.DESCR;
    modalImage.src = producto.IMAGEN_ML || "https://via.placeholder.com/150";
    modalDescription.textContent = `Descripción: ${producto.DESCR}`;
    modalExistencia.textContent = `Existencia: ${producto.EXIST}`;
    modalLinProd.textContent = `Línea del Producto: ${producto.LIN_PROD}`;
  
    // Agregar funcionalidad al botón "Agregar al carrito"
    btnAddToCart.onclick = function () {
      agregarAlCarrito(producto);
      cerrarModal();
    };
  
    // Mostrar el modal
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
    // Obtener el carrito actual desde localStorage
    let carrito = JSON.parse(localStorage.getItem("carrito")) || [];
  
    // Verificar si el producto ya está en el carrito
    const productoExistente = carrito.find((item) => item.CVE_ART === producto.CVE_ART);
  
    if (productoExistente) {
      productoExistente.cantidad += 1; // Si ya está, incrementamos la cantidad
    } else {
      producto.cantidad = 1; // Si no está, lo agregamos con cantidad inicial
      carrito.push(producto);
    }
  
    // Guardar el carrito actualizado en localStorage
    localStorage.setItem("carrito", JSON.stringify(carrito));
    alert(`Producto agregado al carrito: ${producto.DESCR}`);
  }
  