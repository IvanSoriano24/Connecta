// document.addEventListener("DOMContentLoaded", () => {
//   const carritoLista = document.getElementById("carrito-lista");
//   const btnVaciarCarrito = document.getElementById("vaciar-carrito");

//   // Función para mostrar los productos del carrito
//   function mostrarCarrito() {
//     const carrito = JSON.parse(localStorage.getItem("carrito")) || [];
//     carritoLista.innerHTML = ""; // Limpiar contenido previo

//     if (carrito.length === 0) {
//       carritoLista.innerHTML = "<li>El carrito está vacío.</li>";
//       return;
//     }

//     // Generar lista para cada producto
//     carrito.forEach((producto) => {
//       const item = document.createElement("li");
//       item.className = "carrito-item";

//       // Imagen del producto
//       const img = document.createElement("img");
//       img.src = producto.IMAGEN_ML || "https://via.placeholder.com/60";
//       img.alt = producto.DESCR;

//       // Información del producto
//       const info = document.createElement("div");
//       info.className = "info";

//       const title = document.createElement("h2");
//       title.textContent = producto.DESCR;

//       const cantidad = document.createElement("p");
//       cantidad.textContent = `Cantidad: ${producto.cantidad}`;

//       const lineaProd = document.createElement("p");
//       lineaProd.textContent = `Línea: ${producto.LIN_PROD || "N/A"}`;

//       info.appendChild(title);
//       info.appendChild(cantidad);
//       info.appendChild(lineaProd);

//       // Botón de eliminar
//       const btnEliminar = document.createElement("button");
//       btnEliminar.className = "eliminar-producto";
//       btnEliminar.innerHTML = "&#10005;"; // Icono "X"
//       btnEliminar.addEventListener("click", () => eliminarProducto(producto.CVE_ART));

//       // Agregar todo al elemento de la lista
//       item.appendChild(img);
//       item.appendChild(info);
//       item.appendChild(btnEliminar);

//       // Agregar al contenedor principal
//       carritoLista.appendChild(item);
//     });
//   }

//   // Función para eliminar un producto del carrito
//   function eliminarProducto(id) {
//     let carrito = JSON.parse(localStorage.getItem("carrito")) || [];
//     carrito = carrito.filter((producto) => producto.CVE_ART !== id);
//     localStorage.setItem("carrito", JSON.stringify(carrito));
//     mostrarCarrito();
//   }

//   // Vaciar el carrito
//   btnVaciarCarrito.addEventListener("click", () => {
//     localStorage.removeItem("carrito");
//     mostrarCarrito();
//   });

//   // Mostrar el carrito al cargar la página
//   mostrarCarrito();
// });

// document.addEventListener("DOMContentLoaded", () => {
//   const carritoLista = document.getElementById("carrito-lista");
//   const subtotalElement = document.getElementById("subtotal");
//   const totalElement = document.getElementById("total");
//   const checkoutTotal = document.getElementById("checkout-total");

//   const envio = 20; // Costo de envío fijo

//   // Función para mostrar los productos del carrito
//   function mostrarCarrito() {
//     const carrito = JSON.parse(localStorage.getItem("carrito")) || [];
//     carritoLista.innerHTML = ""; // Limpiar contenido previo

//     if (carrito.length === 0) {
//       carritoLista.innerHTML = "<p class='text-center'>El carrito está vacío.</p>";
//       subtotalElement.textContent = "$0.00";
//       totalElement.textContent = `$${envio.toFixed(2)}`;
//       checkoutTotal.textContent = `$${envio.toFixed(2)}`;
//       return;
//     }

//     let subtotal = 0;

//     carrito.forEach((producto) => {
//       const card = document.createElement("div");
//       card.className = "card mb-3";

//       const cardBody = document.createElement("div");
//       cardBody.className = "card-body";

//       const row = document.createElement("div");
//       row.className = "d-flex justify-content-between";

//       // Imagen del producto
//       const imageContainer = document.createElement("div");
//       const img = document.createElement("img");
//       img.src = producto.IMAGEN_ML || "https://via.placeholder.com/65";
//       img.alt = producto.DESCR;
//       img.className = "img-fluid rounded-3";
//       imageContainer.appendChild(img);

//       // Detalles del producto
//       const details = document.createElement("div");
//       details.className = "ms-3";
//       const title = document.createElement("h5");
//       title.textContent = producto.DESCR;
//       const lineaProd = document.createElement("p");
//       lineaProd.textContent = `Línea: ${producto.LIN_PROD || "N/A"}`;
//       lineaProd.className = "small mb-0";
//       details.appendChild(title);
//       details.appendChild(lineaProd);

//       // Cantidad y precio
//       const cantidadContainer = document.createElement("div");
//       const cantidad = document.createElement("h5");
//       cantidad.textContent = producto.cantidad;
//       cantidad.className = "fw-normal mb-0";
//       const precio = document.createElement("h5");
//       const precioProducto = producto.precio || "10"; // Precio predeterminado si no está definido
//       subtotal += precioProducto * producto.cantidad;
//       precio.textContent = `$${(precioProducto * producto.cantidad).toFixed(2)}`;
//       cantidadContainer.appendChild(cantidad);
//       cantidadContainer.appendChild(precio);

//       // Botón de eliminar
//       const eliminar = document.createElement("a");
//       eliminar.href = "#!";
//       eliminar.style.color = "#cecece";
//       eliminar.innerHTML = `
//           <button class="button">
//           <svg viewBox="0 0 448 512" class="svgIcon" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
//           <path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"></path>
//            </svg>
//               </button>`;
//       eliminar.addEventListener("click", () => eliminarProducto(producto.CVE_ART));

//       row.appendChild(imageContainer);
//       row.appendChild(details);
//       row.appendChild(cantidadContainer);
//       row.appendChild(eliminar);

//       cardBody.appendChild(row);
//       card.appendChild(cardBody);
//       carritoLista.appendChild(card);
//     });

//     // Actualizar totales
//     subtotalElement.textContent = `$${subtotal.toFixed(2)}`;
//     totalElement.textContent = `$${(subtotal + envio).toFixed(2)}`;
//     checkoutTotal.textContent = `$${(subtotal + envio).toFixed(2)}`;
//   }

//   // Función para eliminar un producto
//   function eliminarProducto(id) {
//     let carrito = JSON.parse(localStorage.getItem("carrito")) || [];
//     carrito = carrito.filter((producto) => producto.CVE_ART !== id);
//     localStorage.setItem("carrito", JSON.stringify(carrito));
//     mostrarCarrito();
//   }

//   // Mostrar el carrito al cargar la página
//   mostrarCarrito();
// });

function mostrar() {}

document.addEventListener("DOMContentLoaded", () => {
  mostrarFolio();
  $("#btnPagar").click(function () {
    pagarPedido();
  });
  
  const carritoLista = document.getElementById("carrito-lista");
  const totalElement = document.getElementById("total");
  const checkoutTotal = document.getElementById("checkout-total");
  const folioInput = document.getElementById("folioCarrito");

  const envio = 20; // Costo de envío fijo

  // Obtener folio del servidor
  async function obtenerFolioSiguiente() {
    try {
      const formData = new FormData();
      formData.append("numFuncion", "3");
      formData.append("accion", "obtenerFolioSiguiente");
      const response = await fetch("../Servidor/PHP/ventas.php", {
        method: "POST",
        body: formData,
      });

      const data = await response.json();
      return data.success ? data.folioSiguiente : "Error";
    } catch (error) {
      console.error("Error al obtener el folio:", error);
      return "Error";
    }
  }

  async function mostrarFolio() {
    const folio = await obtenerFolioSiguiente();
    folioInput.value = folio;
  }

  async function pagarPedido() {
    const nuevoFolio = await obtenerFolioSiguiente();
    alert(`Pago realizado con folio: ${nuevoFolio}`);
  }

  function mostrarCarrito() {
    const carrito = JSON.parse(localStorage.getItem("carrito")) || [];
    const carritoLista = document.getElementById("carrito-lista");
    const subtotalElement = document.getElementById("subtotal");
    const totalElement = document.getElementById("total");

    carritoLista.innerHTML = ""; // Limpiar contenido previo

    if (carrito.length === 0) {
        carritoLista.innerHTML = "<p class='text-center'>El carrito está vacío.</p>";
        subtotalElement.textContent = "$0.00";
        totalElement.textContent = "$20.00"; // Solo el costo de envío
        return;
    }

    let subtotal = 0;

    carrito.forEach((producto) => {
        const row = document.createElement("tr");

        // 📌 Asegurar imagen válida
        const imagenUrl = producto.IMAGEN_ML && typeof producto.IMAGEN_ML === "string"
            ? producto.IMAGEN_ML
            : "https://via.placeholder.com/65"; // Imagen de respaldo

        const imagenTd = document.createElement("td");
        const img = document.createElement("img");
        img.src = imagenUrl;
        img.alt = producto.DESCR;
        img.className = "img-fluid rounded-3";
        img.style.width = "65px"; // Ajustar tamaño
        imagenTd.appendChild(img);

        const descripcionTd = document.createElement("td");
        descripcionTd.textContent = producto.DESCR;

        const precioTd = document.createElement("td");
        const precioProducto = producto.precio || 10; // Precio predeterminado si no está definido
        precioTd.textContent = `$${precioProducto.toFixed(2)}`;

        const cantidadTd = document.createElement("td");
        cantidadTd.textContent = producto.cantidad; // Mostrar cantidad correcta

        const totalTd = document.createElement("td");
        const totalProducto = precioProducto * producto.cantidad;
        subtotal += totalProducto;
        totalTd.textContent = `$${totalProducto.toFixed(2)}`;

        // Botón de eliminar
        const eliminarTd = document.createElement("td");
        const eliminarBtn = document.createElement("button");
        eliminarBtn.innerHTML = `<i class="fas fa-trash"></i>`;
        eliminarBtn.className = "btn btn-danger btn-sm";
        eliminarBtn.addEventListener("click", () => eliminarProducto(producto.CVE_ART));
        eliminarTd.appendChild(eliminarBtn);

        // Agregamos las celdas a la fila
        row.appendChild(imagenTd);
        row.appendChild(descripcionTd);
        row.appendChild(precioTd);
        row.appendChild(cantidadTd);
        row.appendChild(totalTd);
        row.appendChild(eliminarTd);

        // Agregamos la fila a la tabla
        carritoLista.appendChild(row);
    });

    // Actualizar totales
    subtotalElement.textContent = `$${subtotal.toFixed(2)}`;
    totalElement.textContent = `$${(subtotal + 20).toFixed(2)}`; // 20 es el envío
}



  // Función para eliminar un producto del carrito
  function eliminarProducto(id) {
    let carrito = JSON.parse(localStorage.getItem("carrito")) || [];

    // Filtramos el carrito para eliminar el producto con el ID correspondiente
    carrito = carrito.filter((producto) => producto.CVE_ART !== id);

    // Guardamos el nuevo carrito en localStorage
    localStorage.setItem("carrito", JSON.stringify(carrito));

    // Volvemos a mostrar el carrito actualizado
    mostrarCarrito();
  }

  mostrarCarrito();
  mostrarFolio();
});
