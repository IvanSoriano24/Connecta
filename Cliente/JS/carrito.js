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
      formData.append("claveSae", "01");
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
    const fechaActual =
      new Date().toISOString().slice(0, 10).replace("T", " ") + " 00:00:00.000";

    try {
      // 1️⃣ Mostrar un mensaje de carga
      Swal.fire({
        title: "Procesando pedido...",
        text: "Por favor, espera mientras se completa la compra.",
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
          Swal.showLoading();
        },
      });

      // 2️⃣ Obtener los datos completos del pedido
      const datosPedido = await obtenerDatosPedido(fechaActual);
      console.log(datosPedido);
      if (!datosPedido) {
        Swal.fire({
          title: "Error",
          text: "No se pudieron obtener los datos del pedido.",
          icon: "error",
          confirmButtonText: "Aceptar",
        });
        return;
      }

      // 3️⃣ Obtener los productos del carrito
      const partidas = obtenerPartidasPedido();

      // 4️⃣ Crear `FormData`
      const formData = new FormData();
      formData.append("numFuncion", "19");

      // 📌 Agregar datos del pedido
      for (const key in datosPedido) {
        formData.append(`formularioData[${key}]`, datosPedido[key]);
      }

      // 📌 Agregar las partidas
      partidas.forEach((partida, index) => {
        for (const key in partida) {
          formData.append(`partidasData[${index}][${key}]`, partida[key]);
        }
      });

      console.log("Pedido a enviar (FormData):", formData); // Para depuración

      // 5️⃣ Enviar la solicitud al backend
      const response = await fetch("../Servidor/PHP/ventas.php", {
        method: "POST",
        body: formData,
      });

      // 📌 Verificar el tipo de respuesta
      const contentType = response.headers.get("content-type");

      if (contentType && contentType.includes("application/pdf")) {
        // 📌 Si la respuesta es un PDF, abrirlo en una nueva ventana
        const pdfBlob = await response.blob();
        const pdfUrl = URL.createObjectURL(pdfBlob);
        window.open(pdfUrl, "_blank");

        Swal.fire({
          title: "Pedido completado",
          text: "El pedido ha sido generado exitosamente. Puedes descargar tu remisión.",
          icon: "success",
          confirmButtonText: "Aceptar",
        });
      } else {
        // 📌 Si la respuesta es JSON, manejarla como éxito o error
        const result = await response.json();
        if (result.success) {
          Swal.fire({
            title: "¡Pedido realizado con éxito!",
            text: `Tu pedido ha sido registrado correctamente con el folio: ${datosPedido.numero}`,
            icon: "success",
            confirmButtonText: "Aceptar",
          });

          // 🗑️ Borrar carrito después de confirmar el pedido
          localStorage.removeItem("carrito");

          // 🔄 Actualizar la vista del carrito
          mostrarCarrito();
        } else if (result.autorizacion) {
          Swal.fire({
            title: "Saldo vencido",
            text:
              result.message ||
              "El pedido se procesó pero debe ser autorizado.",
            icon: "warning",
            confirmButtonText: "Entendido",
          }).then(() => {
            // Redirigir al usuario o realizar otra acción
            //window.location.href = "Ventas.php";
          });
        } else if (result.credit) {
          Swal.fire({
            title: "Error al guardar el pedido",
            html: `
              <p>${result.message || "Ocurrió un error inesperado."}</p>
              <p><strong>Saldo actual:</strong> ${
                result.saldoActual?.toFixed(2) || "N/A"
              }</p>
              <p><strong>Límite de crédito:</strong> ${
                result.limiteCredito?.toFixed(2) || "N/A"
              }</p>
            `,
            icon: "error",
            confirmButtonText: "Aceptar",
          });
        } else if (result.exist) {
          Swal.fire({
            title: "Error al guardar el pedido",
            html: `
              <p>${
                result.message ||
                "No hay suficientes existencias para algunos productos."
              }</p>
              <p><strong>Productos sin existencias:</strong></p>
              <ul>
                ${result.productosSinExistencia
                  .map(
                    (producto) => `
                    <li>
                      <strong>Producto:</strong> ${producto.producto}, 
                      <strong>Existencias Totales:</strong> ${
                        producto.existencias || 0
                      }, 
                      <strong>Apartados:</strong> ${producto.apartados || 0}, 
                      <strong>Disponibles:</strong> ${producto.disponible || 0}
                    </li>
                  `
                  )
                  .join("")}
              </ul>
            `,
            icon: "error",
            confirmButtonText: "Aceptar",
          });
        } else {
          Swal.fire({
            title: "Error al realizar el pedido",
            text: result.message,
            icon: "error",
            confirmButtonText: "Aceptar",
          });
        }
      }
    } catch (error) {
      console.error("Error en pagarPedido:", error);
      Swal.fire({
        title: "Error inesperado",
        text: "Hubo un problema al procesar el pedido. Inténtalo de nuevo más tarde.",
        icon: "error",
        confirmButtonText: "Aceptar",
      });
    }
  }
  async function obtenerDatosPedido(fechaActual) {
    // 1️⃣ Obtener los datos del cliente
    const datosCliente = await obtenerDatosClienteCarro();
    if (!datosCliente) {
      alert("No se pudieron obtener los datos del cliente.");
      return null;
    }

    // Definir la fecha y hora actual
    const now = new Date();

    // 2️⃣ Obtener la fecha actual en formato SQL
    const fecha = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(
      2,
      "0"
    )}-${String(now.getDate()).padStart(2, "0")} ${String(
      now.getHours()
    ).padStart(2, "0")}:${String(now.getMinutes()).padStart(2, "0")}:${String(
      now.getSeconds()
    ).padStart(2, "0")}`;

    // 3️⃣ Obtener el nuevo folio
    const nuevoFolio = await obtenerFolioSiguiente();

    // 4️⃣ Fusionar datos del cliente con los datos del pedido
    return {
      fechaAlta: fecha, // Fecha y hora
      numero: nuevoFolio,
      diaAlta: fechaActual,
      almacen: "1", // Definir un almacén por defecto
      estatus: "E", // Estado inicial del pedido

      // 📌 **Datos del Cliente**
      cliente: datosCliente.CLAVE,
      rfc: datosCliente.RFC,
      nombre: datosCliente.NOMBRE,
      calle: datosCliente.CALLE,
      numE: datosCliente.NUMEXT,
      numI: datosCliente.NUMINT,
      descuento: datosCliente.DESCUENTO || 0, // Si no hay descuento, poner 0
      colonia: datosCliente.COLONIA,
      codigoPostal: datosCliente.CODIGO,
      poblacion: datosCliente.LOCALIDAD,
      pais: datosCliente.PAIS,
      regimenFiscal: datosCliente.REGIMEN_FISCAL,
      claveVendedor: 0,
      vendedor: 0,
      comision: 0,
      entrega: "",
      enviar: datosCliente.CALLE, // Puedes cambiarlo según la lógica de negocio
      condicion: "",
    };
  }
  function obtenerPartidasPedido() {
    const carrito = JSON.parse(localStorage.getItem("carrito")) || [];
    let partidasData = [];

    carrito.forEach((producto) => {
      const partida = {
        cantidad: producto.cantidad,
        producto: producto.CVE_ART,
        unidad: producto.unidad,
        descuento1: producto.descuento1 || 0,
        descuento2: producto.descuento2 || 0,
        ieps: producto.ieps || 0,
        impuesto2: producto.impuesto2 || 0,
        isr: producto.isr || 0, // Impuesto 3
        iva: producto.iva || 0,
        comision: producto.comision || 0,
        precioUnitario: producto.precioUnitario,
        subtotal: producto.subtotal,
        CVE_UNIDAD: producto.CVE_UNIDAD,
        COSTO_PROM: producto.COSTO_PROM,
      };
      partidasData.push(partida);
    });
    //console.log(partidasData);
    return partidasData;
  }

  function mostrarCarrito() {
    const carrito = JSON.parse(localStorage.getItem("carrito")) || [];
    const carritoLista = document.getElementById("carrito-lista");
    const subtotalElement = document.getElementById("subtotal");
    const totalElement = document.getElementById("total");

    carritoLista.innerHTML = ""; // Limpiar contenido previo

    if (carrito.length === 0) {
      carritoLista.innerHTML =
        "<p class='text-center'>El carrito está vacío.</p>";
      subtotalElement.textContent = "$0.00";
      totalElement.textContent = "$20.00"; // Solo el costo de envío
      return;
    }

    let subtotal = 0;

    carrito.forEach((producto) => {
      const row = document.createElement("tr");

      // 📌 Asegurar imagen válida
      const imagenUrl =
        producto.IMAGEN_ML && typeof producto.IMAGEN_ML === "string"
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
      const precioProducto = parseFloat(producto.precioUnitario) || 10; // 🛠️ Usamos `precioUnitario`
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
      eliminarBtn.addEventListener("click", () =>
        eliminarProducto(producto.CVE_ART)
      );
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
  async function obtenerDatosClienteCarro() {
    try {
      const response = await fetch("../Servidor/PHP/clientes.php?numFuncion=4");
      const data = await response.json();

      if (!data.success) {
        console.error("Error al obtener datos del cliente:", data.message);
        return null; // En caso de error, devolver null
      }

      return data.data[0]; // Retorna el primer cliente obtenido
    } catch (error) {
      console.error("Error en la solicitud de datos del cliente:", error);
      return null;
    }
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
