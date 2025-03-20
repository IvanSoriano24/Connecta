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

          mostrarSugerencias(res.data.LIMCRED, res.data.SALDO);
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

function mostrarSugerencias(credito, saldo) {
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
          let mensajeTotal =
            productosSugeridos.length > 0
              ? `<p>Total sugerido: $${acumulado.toFixed(2)}</p>`
              : "";

          // Construir el HTML para mostrar la lista de productos
          let html = '<ul style="list-style: none; padding: 0; margin: 0;">';
          listaProductos.forEach((prod) => {
            html += `<li style="margin-bottom: 10px;">
                       <strong>${prod.DESCR}</strong> - Precio: $${parseFloat(
              prod.PREC
            ).toFixed(2)} - Cantidad: ${prod.CANT} - Total: ${
              prod.PREC * prod.CANT
            }
                     </li>`;
          });
          html += "</ul>" + mensajeTotal;

          Swal.fire({
            title: "Productos Sugeridos",
            html: html,
            icon: "info",
            confirmButtonText: "Aceptar",
          });
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

$(document).ready(function () {
  obtenerCredito();
});
