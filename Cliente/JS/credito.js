function obtenerCredito() {
  $.ajax({
    url: "../Servidor/PHP/creditos.php",
    type: "GET",
    data: { numFuncion: "1" },
    success: function (response) {
      try {
        const res =
          typeof response === "string" ? JSON.parse(response) : response;
        if (res.success) {
          // Formatea el crédito y el saldo con comas, dos decimales y añade el signo de pesos
          const creditoFormateado = `$${parseFloat(
            res.data.LIMCRED || 0
          ).toLocaleString("es-MX", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
          })}`;
          const saldoFormateado = `$${parseFloat(
            res.data.SALDO || 0
          ).toLocaleString("es-MX", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
          })}`;

          document.getElementById("credito").value = creditoFormateado;
          document.getElementById("saldo").value = saldoFormateado;

          if(saldoFormateado >= creditoFormateado){
            Swal.fire({
              icon: "error",
              title: "Error",
              text: "Saldo mayor a tu credito.",
              confirmButtonText: "Aceptar",
            });
          }else{
            mostrarSugerencias(res.data.LIMCRED, res.data.SALDO, res.data.CLAVE);
          }
        } else if (res.saldo) {
          Swal.fire({
            title: "Saldo vencido",
            html: res.message,
            icon: "error",
            confirmButtonText: "Aceptar",
          });
        } else {
          Swal.fire({ title: "Error", text: res.message, icon: "error" });
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

function mostrarSugerencias(credito, saldo, claveUsuario) {
  // Calculamos el crédito disponible (suponiendo que "credito" es el límite y "saldo" lo usado)
  let disponible = parseFloat(credito) - parseFloat(saldo);

  $.ajax({
    url: "../Servidor/PHP/creditos.php",
    type: "GET",
    data: { numFuncion: "2" },
    success: function (response) {
      try {
        // Convertir la respuesta a objeto, si es necesario
        const res =
          typeof response === "string" ? JSON.parse(response) : response;
        if (res.success) {
          let productos = res.productos;
          let acumulado = 0;
          let productosSugeridos = [];
          let articulos = [];

          // Recorremos los productos de manera secuencial para acumular su total
          for (let i = 0; i < productos.length; i++) {
            let product = productos[i];
            let precio = parseFloat(product.PREC);
            let cantidad = parseFloat(product.CANT);
            let totalProducto = precio * cantidad;
            // Si al agregar el producto el total acumulado no excede el crédito disponible, lo incluimos
            if (acumulado + totalProducto <= disponible) {
              productosSugeridos.push(product);
              acumulado += totalProducto;
            } else {
              // Si un producto no cumple la condición, detenemos el filtrado
              break;
            }
          }

          // Si no se pudo acumular ningún producto, dejamos de filtrar y mostramos todos
          let listaProductos =
            productosSugeridos.length > 0 ? productosSugeridos : productos;
          listaProductos.forEach((prod) => {
            articulos = prod.CVE_ART;
          });

          cargarProductos(res.productos.LISTA_PREC, articulos, claveUsuario);
        } else if (res.sinDatos) {
          Swal.fire({
            title: "Sin dato",
            text: `El sistema no encontro pedidos completados anteriores`,
            icon: "info",
            confirmButtonText: "Aceptar",
          });
          return;
        } else if (res.saldo) {
          Swal.fire({
            title: "Saldo vencido",
            html: res.message,
            icon: "error",
            confirmButtonText: "Aceptar",
          });
        } else {
          Swal.fire({ title: "Error", text: res.message, icon: "error" });
        }
      } catch (error) {
        console.error("Error al procesar la respuesta:", error);
      }
    },
    error: function () {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Error al obtener la lista de productos.",
      });
    },
  });
}

async function cargarProductos(LISTA_PREC, articulos, claveUsuario) {
  try {
    // 1️⃣ Obtener los datos del cliente para obtener LISTA_PREC
    const listaPrecioCliente = LISTA_PREC || "1"; // Usar "1" si no hay lista de precios

    // 2️⃣ Hacer la solicitud a la API de productos pasando LISTA_PREC
    const numFuncion = 3;
    const url = `../Servidor/PHP/creditos.php?numFuncion=${numFuncion}&listaPrecioCliente=${listaPrecioCliente}&articulos=${articulos}`;

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
    Swal.fire({
      title: "Aviso",
      text: "Error al cargar los productos.",
      icon: "error",
      confirmButtonText: "Aceptar",
    });
    //alert("Error al cargar los productos.");
  }
}
function mostrarProductosCuadricula(productos) {
  // Usa el contenedor con id "product-list" (puedes cambiarlo según tu HTML)
  const contenedorProductos = document.getElementById("product-list");

  if (!contenedorProductos) {
      console.error("Error: No se encontró el contenedor para los productos.");
      return;
  }

  contenedorProductos.innerHTML = ""; // Limpia el contenedor

  if (!Array.isArray(productos) || productos.length === 0) {
      contenedorProductos.innerHTML = "<p class='no-products'>No hay productos para mostrar.</p>";
      return;
  }

  // Crear la estructura del carrusel
  const carousel = document.createElement("div");
  carousel.id = "productCarousel";
  carousel.className = "carousel slide";
  carousel.setAttribute("data-bs-ride", "carousel");

  // Contenedor para los slides
  const carouselInner = document.createElement("div");
  carouselInner.className = "carousel-inner";

  const itemsPorSlide = 3; // Número de productos por slide
  const totalSlides = Math.ceil(productos.length / itemsPorSlide);

  for (let i = 0; i < totalSlides; i++) {
      const carouselItem = document.createElement("div");
      carouselItem.className = `carousel-item ${i === 0 ? 'active' : ''}`;

      // Contenedor de productos dentro del slide, con layout flex
      const slideContent = document.createElement("div");
      slideContent.className = "d-flex justify-content-center gap-3";

      // Agregar productos al slide
      for (let j = i * itemsPorSlide; j < (i + 1) * itemsPorSlide && j < productos.length; j++) {
          const producto = productos[j];

          // Seleccionar la primera imagen disponible, o usar imagen por defecto
          let imagenSrc = "SRC/noimg.png";
          if (Array.isArray(producto.IMAGEN_ML) && producto.IMAGEN_ML.length > 0) {
              imagenSrc = producto.IMAGEN_ML[0];
          }

          // Para pasar el objeto producto y el precio a la función abrirModalProducto,
          // lo convertimos a JSON y lo codificamos
          const productoJSON = encodeURIComponent(JSON.stringify(producto));

          // Estructura de la tarjeta. Se añade un atributo onclick que invoca la función abrirModalProducto.
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

          slideContent.innerHTML += productCard;
      }

      carouselItem.appendChild(slideContent);
      carouselInner.appendChild(carouselItem);
  }

  // Botones de navegación
  const prevButton = `
      <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
          <span class="carousel-control-prev-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Anterior</span>
      </button>
  `;

  const nextButton = `
      <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
          <span class="carousel-control-next-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Siguiente</span>
      </button>
  `;

  // Agregar el contenedor de slides y los botones al carrusel
  carousel.appendChild(carouselInner);
  carousel.innerHTML += prevButton + nextButton;

  // Agregar el carrusel al contenedor principal
  contenedorProductos.appendChild(carousel);
}
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
function añadirEvento(CVE_ART) {
  $.ajax({
    url: "../Servidor/PHP/tblControl.php",
    type: "GET",
    data: { numFuncion: "1", CVE_ART: CVE_ART },
    success: function (response) {
      try {
        const res = typeof response === "string" ? JSON.parse(response) : response;
        if (res.success) {
          //console.log("Evento guardado correctamente.");
        } else {
          //console.log("Evento no guardado:", res.message);
        }
      } catch (error) {
        console.error("Error al procesar la respuesta:", error);
      }
    },
    error: function (xhr, status, error) {
      console.error("Error en la solicitud AJAX:", status, error);
    },
  });
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
  let productoExistente = carrito.find(
    (item) => item.CVE_ART === producto.CVE_ART
  );

  if (productoExistente) {
    // 📌 Si ya existe, sumamos la cantidad nueva
    productoExistente.cantidad += cantidad;
  } else {
    // 📌 Si no existe, lo agregamos con la cantidad seleccionada
    let imagenProducto =
      Array.isArray(producto.IMAGEN_ML) && producto.IMAGEN_ML.length > 0
        ? producto.IMAGEN_ML[0] // Usar la primera imagen si hay varias
        : "SRC/noimg.png"; // Imagen de respaldo si no hay

    const datosProducto = await completarPrecioProducto(
      producto,
      cantidad,
      precio
    );
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
$(document).ready(function () {
  obtenerCredito();
});
