async function cargarProductos() {
    try {
      // 1️⃣ Obtener los datos del cliente para obtener LISTA_PREC
      const datosCliente = await obtenerDatosCliente();
      const listaPrecioCliente = datosCliente?.LISTA_PREC || "1"; // Usar "1" si no hay lista de precios
  
      // 2️⃣ Hacer la solicitud a la API de productos pasando LISTA_PREC
      const numFuncion = 18;
      const url = `../Servidor/PHP/ventas.php?numFuncion=${numFuncion}&listaPrecioCliente=${listaPrecioCliente}`;
  
      const response = await fetch(url, {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
        },
      });
  
      const data = await response.json();
  
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

async function mostrarProductosCuadricula(productos) {
  const contenedorProductos = document.querySelector(".product-grid");

  if (!contenedorProductos) {
    console.error("Error: No se encontró el contenedor para los productos.");
    return;
  }

  contenedorProductos.innerHTML = ""; // Limpia el contenedor antes de agregar productos

  if (!Array.isArray(productos) || productos.length === 0) {
    contenedorProductos.innerHTML =
      "<p class='no-products'>No hay productos para mostrar.</p>";
    return;
  }  

  productos.forEach((producto) => {
    const productItem = document.createElement("div");
    productItem.className = "card";
    // Generar carrusel de imágenes si el producto tiene múltiples imágenes
    let carruselHtml = "";
    if (Array.isArray(producto.IMAGEN_ML) && producto.IMAGEN_ML.length > 0) {
      carruselHtml = `
                <div id="carrusel-${
                  producto.CVE_ART
                }" class="carousel slide card-img" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        ${producto.IMAGEN_ML.map(
                          (imgUrl, index) => `
                            <div class="carousel-item ${
                              index === 0 ? "active" : ""
                            }">
                                <img src="${imgUrl}" class="d-block w-100 product-img" alt="${
                            producto.DESCR
                          }">
                            </div>
                        `
                        ).join("")}
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#carrusel-${
                      producto.CVE_ART
                    }" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#carrusel-${
                      producto.CVE_ART
                    }" data-bs-slide="next">
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
            <div class="price">$${parseFloat(producto.PRECIO).toFixed(2)}</div>
        `;

    // Agregar evento solo a la información
    infoCard.addEventListener("click", (event) => {
      event.stopPropagation(); // Evita que se active un clic en el `productItem`
      abrirModalProducto(producto, producto.PRECIO);
    });

    // Añadir elementos al `productItem`
    productItem.innerHTML = `${carruselHtml}`;
    productItem.appendChild(infoCard);

    // Agregar el producto al contenedor
    contenedorProductos.appendChild(productItem);
  });
}
async function obtenerPrecioProducto(claveProducto) {
  try {
    const data = obtenerDatosCliente();
    const listaPrecioCliente = data.LISTA_PREC || "1";
    //const claveProducto = claveProducto;
    const response = await $.get("../Servidor/PHP/ventas.php", {
      numFuncion: "6", // Cambia según la función que uses en tu PHP
      claveProducto: claveProducto,
      listaPrecioCliente: listaPrecioCliente,
    });
    if (response.success) {
      return response.precio; // Retorna el precio
    } else {
      alert(response.message); // Muestra el mensaje de error
      return null;
    }
  } catch (error) {
    console.error("Error al obtener el precio del producto:", error);
    return null;
  }
}
async function obtenerDatosCliente() {
  try {
    const response = await fetch("../Servidor/PHP/clientes.php?numFuncion=4");
    const data = await response.json();

    if (!data.success) {
      console.error("Error al obtener datos del cliente:", data.message);
      return { cvePrecio: "1" }; // Valor predeterminado si hay error
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

function abrirModalProducto(producto, precio) {
  const modal = document.getElementById("productModal");
  if (!modal) {
    console.error("Error: No se encontró el modal");
    return;
  }
  añadirEvento(producto.CVE_ART);
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
    Array.isArray(producto.IMAGEN_ML) && producto.IMAGEN_ML.length > 0
      ? producto.IMAGEN_ML.map(
          (imgUrl, index) => `
            <div class="carousel-item ${index === 0 ? "active" : ""}">
                <img src="${imgUrl}" class="d-block w-100" alt="Imagen del Producto">
            </div>
        `
        ).join("")
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
    cerrarModal2();

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

// Función para cerrar el modal
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

function añadirEvento(CVE_ART){
  $.ajax({
    url: "../Servidor/PHP/tblControl.php",
    type: "GET",
    data: { numFuncion: "1", CVE_ART: CVE_ART },
    success: function (response) {
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
    error: function () {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Error al obtener la lista de clientes.",
      });
    },
  });
}
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
      return { impuesto1: 0, impuesto2: 0, impuesto3: 0, impuesto4: 0 };
    }

    return {
      impuesto1: parseFloat(data.impuestos.IMPUESTO1) || 0,
      impuesto2: parseFloat(data.impuestos.IMPUESTO2) || 0,
      impuesto3: parseFloat(data.impuestos.IMPUESTO3) || 0,
      impuesto4: parseFloat(data.impuestos.IMPUESTO4) || 0,
    };
  } catch (error) {
    console.error("Error en la solicitud de impuestos:", error);
    return { impuesto1: 0, impuesto2: 0, impuesto3: 0, impuesto4: 0 };
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
      Array.isArray(producto.IMAGEN_ML) && producto.IMAGEN_ML.length > 0
        ? producto.IMAGEN_ML[0] // Usar la primera imagen si hay varias
        : "SRC/noimg.png"; // Imagen de respaldo si no hay

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