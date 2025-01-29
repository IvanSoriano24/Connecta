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
//Original
// function mostrarProductosCuadricula(productos) {
//     const contenedorProductos = document.querySelector(".product-grid");

//     if (!contenedorProductos) {
//         console.error("Error: No se encontró el contenedor para los productos.");
//         return;
//     }

//     contenedorProductos.innerHTML = ""; // Limpia el contenedor antes de agregar productos

//     if (!Array.isArray(productos) || productos.length === 0) {
//         contenedorProductos.innerHTML = "<p>No hay productos para mostrar.</p>";
//         return;
//     }

//     productos.forEach((producto) => {
//         const productItem = document.createElement("div");
//         productItem.className = "product-item";

//         // Generar carrusel de imágenes
//         let carruselHtml = "";
//         if (Array.isArray(producto.IMAGEN_ML) && producto.IMAGEN_ML.length > 0) {
//             carruselHtml = `
//                 <div id="carrusel-${producto.CVE_ART}" class="carousel slide" data-bs-ride="carousel">
//                     <div class="carousel-inner">
//                         ${producto.IMAGEN_ML.map((imgUrl, index) => `
//                             <div class="carousel-item ${index === 0 ? 'active' : ''}">
//                                 <img src="${imgUrl}" class="d-block w-100" alt="Imagen del Producto">
//                             </div>
//                         `).join("")}
//                     </div>
//                     <button class="carousel-control-prev" type="button" data-bs-target="#carrusel-${producto.CVE_ART}" data-bs-slide="prev">
//                         <span class="carousel-control-prev-icon" aria-hidden="true"></span>
//                         <span class="visually-hidden">Anterior</span>
//                     </button>
//                     <button class="carousel-control-next" type="button" data-bs-target="#carrusel-${producto.CVE_ART}" data-bs-slide="next">
//                         <span class="carousel-control-next-icon" aria-hidden="true"></span>
//                         <span class="visually-hidden">Siguiente</span>
//                     </button>
//                 </div>
//             `;
//         } else {
//             carruselHtml = `<img src="https://via.placeholder.com/150" alt="${producto.DESCR}" class="product-img">`;
//         }

//         productItem.innerHTML = `
//             ${carruselHtml}
//             <p class="product-name">${producto.DESCR}</p>
//             <p class="product-stock">Existencia: ${producto.EXIST}</p>
//             <button class="btn btn-agregar">Agregar al carrito</button>
//             <button class="btn btn-detalles">Detalles</button>
//         `;

//         // Agregar eventos a los botones
//         const btnAgregar = productItem.querySelector(".btn-agregar");
//         const btnDetalles = productItem.querySelector(".btn-detalles");

//         btnAgregar.onclick = () => agregarAlCarrito(producto);
//         btnDetalles.onclick = () => abrirModalProducto(producto);

//         // Agregar el producto al contenedor
//         contenedorProductos.appendChild(productItem);
//     });
// }


// -----------------------------------------------------------------------------
document.addEventListener("DOMContentLoaded", () => {
  // alert("Cargando productos...");
  cargarProductos();
});

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


// Función de ejemplo para agregar al carrito
function agregarAlCarrito(producto) {
  console.log(`Producto agregado al carrito:`, producto);
  // alert(`Producto "${producto.DESCR}" agregado al carrito.`);
}

// -----------------------------------------------------------------------------
// Función para abrir el modal con la información del producto
// function abrirModalProducto(producto) {
//   const modal = document.getElementById("productModal");
  

//   if (!modal) {
//       // alert("Error: No se encontró el modal");
//       console.error("Error: No se encontró el modal");
//       return;
//   }

//   console.log("Intentando abrir modal con el producto:", producto);

//   // Agregar información del producto
//   document.getElementById("modal-title").textContent = producto.DESCR;
//   document.getElementById("modal-price").textContent = `$${parseFloat(producto.EXIST).toFixed(2)}`;
//   document.getElementById("modal-description").textContent = producto.DESCR;
//   document.getElementById("modal-lin-prod").textContent = `Línea del Producto: ${producto.LIN_PROD}`;

//   // Generar carrusel de imágenes dentro del modal
//   let modalInner = document.getElementById("modal-carousel-inner");
//   if (Array.isArray(producto.IMAGEN_ML) && producto.IMAGEN_ML.length > 0) {
//       modalInner.innerHTML = producto.IMAGEN_ML.map((imgUrl, index) => `
//           <div class="carousel-item ${index === 0 ? 'active' : ''}">
//               <img src="${imgUrl}" class="d-block w-100" alt="Imagen del Producto">
//           </div>
//       `).join("");
//   } else {
//       modalInner.innerHTML = `
//           <div class="carousel-item active">
//               <img src="SRC/noimg.png" class="d-block w-100" alt="Imagen no disponible">
//           </div>`;
//   }

//   // Resetear el valor del input de cantidad
//   document.getElementById("cantidadProducto").value = 1;

//   // Configurar el botón "Agregar al carrito"
//   const btnAddToCart = document.getElementById("btn-add-to-cart");
//   // Eliminar cualquier evento previo
//   btnAddToCart.onclick = null;  
//   btnAddToCart.onclick = () => {
//       const cantidad = parseInt(document.getElementById("cantidadProducto").value) || 1;

//       // Agregar al carrito
//       agregarAlCarrito(producto, cantidad);
//       alert("Agregado al carrito");
//       // Mostrar SweetAlert2
//       Swal.fire({
//           title: "Producto agregado",
//           text: `${cantidad} unidad(es) de "${producto.DESCR}" se agregó correctamente al carrito.`,
//           icon: "success",
//           confirmButtonText: "Aceptar"
//       });
//       // Cerrar el modal
//       cerrarModal2();
//   };
//   // Mostrar el modal cambiando el estilo
//   modal.style.display = "flex";
//   modal.style.opacity = "1"; 
//   modal.style.visibility = "visible";

//   // alert("Modal abierto"); // ALERTA PARA DEPURACIÓN
 
// }

function abrirModalProducto(producto) {
  const modal = document.getElementById("productModal");

  if (!modal) {
      console.error("Error: No se encontró el modal");
      return;
  }

  // Agregar información del producto
  document.getElementById("modal-title").textContent = producto.DESCR;
  document.getElementById("modal-price").textContent = `$${parseFloat(producto.EXIST).toFixed(2)}`;
  document.getElementById("modal-description").textContent = producto.DESCR;
  document.getElementById("modal-lin-prod").textContent = `Línea del Producto: ${producto.LIN_PROD}`;

  // Generar carrusel de imágenes dentro del modal
  const modalInner = document.getElementById("modal-carousel-inner");
  modalInner.innerHTML = Array.isArray(producto.IMAGEN_ML) && producto.IMAGEN_ML.length > 0
      ? producto.IMAGEN_ML.map((imgUrl, index) => `
          <div class="carousel-item ${index === 0 ? 'active' : ''}">
              <img src="${imgUrl}" class="d-block w-100" alt="Imagen del Producto">
          </div>
      `).join("")
      : `<div class="carousel-item active">
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
      const cantidad = parseInt(document.getElementById("cantidadProducto").value);

      // Validar cantidad
      if (!cantidad || cantidad <= 0) {
          Swal.fire({
              title: "Cantidad no válida",
              text: "Por favor, ingresa una cantidad mayor a 0.",
              icon: "error",
              confirmButtonText: "Aceptar"
          });
          return;
      }

      // Agregar al carrito
      agregarAlCarrito(producto, cantidad);

      // Llamar a cerrar el modal
      cerrarModal2();

      // Mostrar la alerta después de cerrar el modal
      setTimeout(() => {
          Swal.fire({
              title: "Producto agregado",
              text: `${cantidad} unidad(es) de "${producto.DESCR}" se agregó correctamente al carrito.`,
              icon: "success",
              confirmButtonText: "Aceptar"
          });
      }, 300); // Pequeño retraso para evitar solapamiento
  };

  // Mostrar el modal
  modal.style.display = "flex";
  modal.style.opacity = "1";
  modal.style.visibility = "visible";
}

// Función para cerrar el modal
function cerrarModal2() {
  const modal = document.getElementById("productModal");

  if (!modal) {
      // alert("Error: No se encontró el modal");
      console.error("Error: No se encontró el modal");
      return;
  }

  console.log("Intentando cerrar modal...");

  // Ocultar el modal cambiando el estilo
  modal.style.display = "none";
  modal.style.opacity = "0";
  modal.style.visibility = "hidden";

  // alert("Modal cerrado"); // ALERTA PARA DEPURACIÓN
  console.log("Modal cerrado:", modal);
}

// Agregar eventos de cierre cuando la página carga
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

//-----------------------------------------------------------------------------


// ----------FUNCIONES CARRITO-------------------------------------------------------------------
// function agregarAlCarrito(producto, cantidad = 1) {
//   let carrito = JSON.parse(localStorage.getItem("carrito")) || [];

//   // Buscar si el producto ya está en el carrito
//   let productoExistente = carrito.find(item => item.CVE_ART === producto.CVE_ART);

//   if (productoExistente) {
//       // Si ya existe, sumamos la cantidad nueva
//       productoExistente.cantidad += cantidad;
//   } else {
//       // Si no existe, lo agregamos con la cantidad seleccionada
//       let imagenProducto = Array.isArray(producto.IMAGEN_ML) && producto.IMAGEN_ML.length > 0
//           ? producto.IMAGEN_ML[0] // Usar la primera imagen si hay varias
//           : "https://via.placeholder.com/65"; // Imagen de respaldo si no hay

//       let nuevoProducto = {
//           CVE_ART: producto.CVE_ART,
//           DESCR: producto.DESCR,
//           precio: producto.EXIST || 10, // Precio simulado si no está definido
//           cantidad: cantidad, // Usar la cantidad seleccionada
//           IMAGEN_ML: imagenProducto // Guardar la imagen
//       };

//       carrito.push(nuevoProducto);
//   }

//   // Guardamos la actualización en localStorage
//   localStorage.setItem("carrito", JSON.stringify(carrito));
//   mostrarCarrito();
// }

function agregarAlCarrito(producto, cantidad = 1) {
  let carrito = JSON.parse(localStorage.getItem("carrito")) || [];

  // Buscar si el producto ya está en el carrito
  let productoExistente = carrito.find(item => item.CVE_ART === producto.CVE_ART);

  if (productoExistente) {
      // 📌 Si ya existe, sumamos la cantidad nueva
      productoExistente.cantidad += cantidad;
  } else {
      // 📌 Si no existe, lo agregamos con la cantidad seleccionada
      let imagenProducto = Array.isArray(producto.IMAGEN_ML) && producto.IMAGEN_ML.length > 0
          ? producto.IMAGEN_ML[0] // Usar la primera imagen si hay varias
          : "SRC/noimg.png"; // Imagen de respaldo si no hay

      let nuevoProducto = {
          CVE_ART: producto.CVE_ART,
          DESCR: producto.DESCR,
          precio: producto.EXIST || 10, // Precio simulado si no está definido
          cantidad: cantidad, //  Usar la cantidad seleccionada
          IMAGEN_ML: imagenProducto // Guardar la imagen
      };

      carrito.push(nuevoProducto);
  }

  // Guardamos la actualización en localStorage
  localStorage.setItem("carrito", JSON.stringify(carrito));
  mostrarCarrito();
}
