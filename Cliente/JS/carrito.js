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

  //const envio = 20; // Costo de envío fijo

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
      if (!datosPedido) {
        Swal.fire({
          title: "Aviso",
          text: "No se pudieron obtener los datos del pedido.",
          icon: "error",
          confirmButtonText: "Aceptar",
        });
        return;
      }
      // 3️⃣ Obtener los productos del carrito
      const partidas = await obtenerPartidasPedido(datosPedido.cliente);
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
    // 1️⃣ Obtener los datos del cliente (por ejemplo, del carrito)
    const datosCliente = await obtenerDatosClienteCarro();
    if (!datosCliente) {
      alert("No se pudieron obtener los datos del cliente.");
      return null;
    }
  
    // 2️⃣ Definir la fecha y hora actual
    const now = new Date();
    const fecha = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}-${String(now.getDate()).padStart(2, "0")} ${String(now.getHours()).padStart(2, "0")}:${String(now.getMinutes()).padStart(2, "0")}:${String(now.getSeconds()).padStart(2, "0")}`;
  
    // 3️⃣ Obtener el nuevo folio
    const nuevoFolio = await obtenerFolioSiguiente();
  
    // 4️⃣ Obtener el descuento del cliente desde Firebase
    const descuentoFirebase = await obtenerClienteDescunetoDeFirebase(datosCliente.CLAVE);
    
      //console.log(descuentoCliente);
    // 5️⃣ Fusionar los datos del cliente con los datos del pedido
    return {
      fechaAlta: fecha,         // Fecha y hora actual
      numero: nuevoFolio,
      diaAlta: fechaActual,
      almacen: "1",             // Almacén por defecto
      estatus: "E",             // Estado inicial del pedido
  
      // 📌 Datos del Cliente
      cliente: datosCliente.CLAVE,
      rfc: datosCliente.RFC,
      nombre: datosCliente.NOMBRE,
      calle: datosCliente.CALLE,
      numE: datosCliente.NUMEXT,
      numI: datosCliente.NUMINT,
      descuentoCliente: descuentoFirebase, // Descuento obtenido desde Firebase (o el valor local)
      colonia: datosCliente.COLONIA,
      codigoPostal: datosCliente.CODIGO,
      poblacion: datosCliente.LOCALIDAD,
      pais: datosCliente.PAIS,
      regimenFiscal: datosCliente.REGIMEN_FISCAL,
      claveVendedor: 24,
      vendedor: 24,
      comision: 0,
      entrega: "",
      enviar: datosCliente.CALLE,
      condicion: ""
    };
  }
  async function obtenerClienteDescunetoDeFirebase(cliente) {
    try {
      const response = await $.ajax({
        url: '../Servidor/PHP/descuento.php', // Ajusta la URL a tu endpoint PHP
        type: 'GET',
        dataType: 'json', // Se espera JSON
        data: { cliente: cliente, numFuncion: 5 }
      });
      if (response.success) {
        // Suponemos que response.data es el valor (por ejemplo, "40")
        return response.data;
      } else {
        console.error('Error en la consulta de descuentos:', response.message);
        return 0;
      }
    } catch (error) {
      console.error('Error al obtener descuento de Firebase:', error);
      return 0;
    }
  }
  async function obtenerPartidasPedido(cliente) {
    console.log("🔍 Cliente recibido en obtenerPartidasPedido:", cliente);

    // Obtener el carrito almacenado en localStorage
    const carrito = JSON.parse(localStorage.getItem("carrito")) || [];
    console.log("🛒 Carrito obtenido del localStorage:", carrito);

    let partidasData = [];

    // Obtener los descuentos registrados en Firebase para el cliente
    const descuentosFirebase = await obtenerDescuentosDeFirebase(cliente);
    console.log("📌 Descuentos obtenidos de Firebase:", descuentosFirebase);

    // Convertir el array de descuentos a un objeto (mapa) para facilitar la búsqueda
    const descuentosMap = {};
    descuentosFirebase.forEach((descuento) => {
      descuentosMap[descuento.clave] = descuento.descuento;
    });
    console.log("📌 Mapa de descuentos:", descuentosMap);

    // Recorrer cada producto del carrito y construir la partida
    carrito.forEach((producto) => {
      const partida = {
        cantidad: producto.cantidad,
        producto: producto.CVE_ART,
        unidad: producto.unidad,
        descuento: descuentosMap[producto.CVE_ART] || producto.descuento1 || 0,
        descuento2: producto.descuento2 || 0,
        ieps: producto.ieps || 0,
        impuesto2: producto.impuesto2 || 0,
        isr: producto.isr || 0, // Impuesto 3
        iva: producto.iva || 16,
        comision: producto.comision || 0,
        precioUnitario: producto.precioUnitario,
        subtotal: producto.subtotal,
        CVE_UNIDAD: producto.CVE_UNIDAD,
        COSTO_PROM: producto.COSTO_PROM,
      };
      console.log("✅ Partida generada:", partida);
      partidasData.push(partida);
    });

    console.log("📌 PartidasData final generado:", partidasData);
    return partidasData;
  }
  // Función para obtener los descuentos de Firebase para un cliente
  async function obtenerDescuentosDeFirebase(cliente) {
    try {
      const response = await $.ajax({
        url: "../Servidor/PHP/descuento.php", // Ajusta la URL a tu endpoint PHP
        type: "GET",
        dataType: "json", // Asegura que la respuesta se trate como JSON
        data: { cliente: cliente, numFuncion: 4 },
      });
      if (response.success) {
        return response.data; // Se espera un array de objetos { clave, descuento }
      } else {
        console.error("Error en la consulta de descuentos:", response.message);
        return [];
      }
    } catch (error) {
      console.error("Error al obtener descuentos de Firebase:", error);
      return [];
    }
  }
  function mostrarCarrito() {
    const carrito = JSON.parse(localStorage.getItem("carrito")) || [];
    const carritoLista = document.getElementById("carrito-lista");
    const subtotalElement = document.getElementById("subtotal");
    const totalElement = document.getElementById("total");

    carritoLista.innerHTML = ""; // Limpiar contenido previo

    if (carrito.length === 0) {
      carritoLista.innerHTML =
        "<p class='text-center' style='text-align: right;'>El carrito está vacío.</p>";
      subtotalElement.textContent = "$0.00";
      totalElement.textContent = "0.00"; // Solo el costo de envío
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
      totalTd.style = "text-align: right;";

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
    totalElement.textContent = `$${(subtotal).toFixed(2)}`; // 20 es el envío
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
