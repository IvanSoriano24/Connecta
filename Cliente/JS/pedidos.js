function cargarPedidos() {
  $.get(
    "../Servidor/PHP/pedido.php",
    { numFuncion: "4" },
    function (response) {
      if (response.success && response.data) {
        const pedidos = response.data;
        const pedidosTable = document.getElementById("datosPedidos");
        pedidosTable.innerHTML = ""; // Limpia la tabla antes de agregar nuevos datos

        // Recorrer los pedidos y agregarlos a la tabla
        pedidos.forEach((pedido) => {
          const row = document.createElement("tr");

          // Asegúrate de que las propiedades existan en cada objeto
          row.innerHTML = `
            <td>${pedido.Clave || "Sin pedidos"}</td>
            <td>$${pedido.Subtotal ? pedido.Subtotal.toFixed(2) : "0.00"}</td>
            <td>$${
              pedido.ImporteTotal ? pedido.ImporteTotal.toFixed(2) : "0.00"
            }</td>
            <td>
              <button class="btnVizualizarPedido" name="btnVizualizarPedido"  data-id="${
                pedido.Clave
              }">
                <i class="fa fa-eye" aria-hidden="true"></i>
              </button>
            </td>
          `;
          pedidosTable.appendChild(row);
        });
        /*
<td>
              <button class="btnEditarPedido" name="btnEditarPedido" data-id="${pedido.id}">Editar</button>
              <button class="btnCancelarPedido" data-id="${pedido.id}">Cancelar</button>
            </td>
*/
        // Agregar eventos a los botones dinámicos
        agregarEventosBotones();
      } else {
        //alert(response.message || 'Error al obtener los pedidos.');
        console.error("Error en la solicitud:", textStatus, errorThrown);
        console.log("Detalles:", jqXHR.responseText);
      }
    },
    "json"
  );
}

function vizualizarPedido(idPedido) {
  //limpiarBody();
  $.get(
    "../Servidor/PHP/pedido.php",
    {
      numFuncion: "5",
      idPedido: idPedido,
    },
    function (response) {
      if (response.success && response.partidas) {
        const pedido = response.partidas;
        let tbodyHTML = "";
// Mostrar el modal
$("#modalDetallesPedido").modal("show");
        // Recorrer cada partida y generar la fila correspondiente
        pedido.forEach(function (item) {
          tbodyHTML += "<tr>";

          // En la columna de Producto se mostrará la primera imagen del arreglo IMAGEN_ML
          let imagenHTML = "";
          if (Array.isArray(item.IMAGEN_ML) && item.IMAGEN_ML.length > 0) {
            imagenHTML = `<img src="${item.IMAGEN_ML[0]}" alt="Imagen del producto" style="max-width: 100px;">`;
          } else {
            imagenHTML = `<img src="SRC/noimg.png" alt="Imagen no disponible" style="max-width: 100px;">`;
          }
          tbodyHTML += "<td>" + imagenHTML + "</td>";

          // Columna de Descripción
          tbodyHTML += "<td>" + (item.DESCR || "") + "</td>";
          // Columna de Cantidad
          tbodyHTML += "<td>" + item.CANT + "</td>";
          // Columna de Subtotal
          tbodyHTML += "<td>" + item.TOT_PARTIDA + "</td>";
          tbodyHTML += "</tr>";
        });

        // Rellenar el cuerpo de la tabla
        $("#modalDetallesPedido table tbody").html(tbodyHTML);

        // Asignar el total en el pie de la tabla usando el IMPORTE de cualquier registro (se toma el primero)
        if (pedido.length > 0) {
          let total = pedido[0].IMPORTE;
          $("#modalDetallesPedido table tfoot").html(
            "<tr><td colspan='3' class='text-end'><strong>Total:</strong></td><td>" +
              total +
              "</td></tr>"
          );
        }
      } else {
        console.error(
          "Error al cargar los datos del pedido:",
          response.message
        );
      }
    },
    "json"
  ).fail(function (jqXHR, textStatus, errorThrown) {
    console.error("Error en la solicitud:", textStatus, errorThrown);
    console.error("Respuesta del servidor:", jqXHR.responseText);
  });
}
function limpiarBody() {
  document
    .getElementById("modalDetallesPedido table")
    .querySelector("tbody").innerHTML = "";
}
// Función para agregar eventos a los botones "Editar" y "Cancelar"
function agregarEventosBotones() {
  // Evento para editar pedido
  $(".btnVizualizarPedido").on("click", function () {
    const idPedido = $(this).data("id");
    vizualizarPedido(idPedido);
  });

  /*// Evento para cancelar pedido
    $('.btnCancelarPedido').on('click', function () {
      const idPedido = $(this).data('id');
      const confirmacion = confirm('¿Estás seguro de que quieres cancelar este pedido?');
      if (confirmacion) {
          $.post('../Servidor/PHP/pedido.php', {
              numFuncion: '3',  // La función 3 es para cancelar el pedido
              idPedido: idPedido
          }, function (response) {
              if (response.success) {
                location.reload();
                alert('Pedido cancelado con éxito');
              } else {
                  alert('Error al cancelar el pedido: ' + response.message);
                  console.log('Error al cancelar el pedido: ' + response.message);
              }
          }, 'json').fail(function (jqXHR, textStatus, errorThrown) {
              console.error('Error en la solicitud:', textStatus, errorThrown);
              console.error('Respuesta del servidor:', jqXHR.responseText); // Verifica la respuesta del servidor
              console.log('Error al cancelar el pedido. Verifica la consola para más detalles.');
              alert('Error al cancelar el pedido. Verifica la consola para más detalles.');
          });
      }
  });  */
}

$(document).ready(function () {
  cargarPedidos();
  /*// Evento para crear pedido
    $('#btnCrearPedido').on('click', function () {
      altaPedido();
      alert('Abrir formulario para crear pedido.');
      // Lógica para abrir el modal o formulario...
    });

    $('#guardarFactura').on('click', function () {
      altaPedido();
    });*/

  $("#editarPedidoForm").on("submit", function (event) {
    event.preventDefault(); // Evitar la recarga de la página
    // Obtener los valores del formulario
    const idPedido = $("#idPedido").val();
    const pedido = $("#pedidos").val();
    const cliente = $("#cliente").val();
    const total = $("#total").val();
    const fecha = $("#fecha").val();
    // Crear un objeto con los datos actualizados
    const datosActualizados = {
      cliente: cliente,
      pedido: pedido,
      total: total,
      fecha: fecha,
    };
    // Realizar la solicitud para actualizar el pedido
    $.ajax({
      url: "../Servidor/PHP/pedido.php",
      method: "POST",
      data: {
        numFuncion: "5", // Código para la actualización
        id: idPedido,
        datos: datosActualizados,
      },
      success: function (response) {
        if (response.success) {
          alert("Pedido actualizado con éxito");
          cargarPedidos(); // Recargar la lista de pedidos
          $("#formularioEditarPedido").hide(); // Ocultar el formulario
        } else {
          alert("Error al actualizar el pedido");
        }
      },
    });
  });
  $("#cerrarFormulario").on("click", function () {
    $("#formularioEditarPedido").hide();
  });
});
