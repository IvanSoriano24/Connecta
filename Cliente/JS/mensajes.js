// function cargarComandas() {
//     const filtroStatus = $('#filtroStatus').val(); // Obtener el filtro seleccionado

//     $.get('../Servidor/PHP/mensajes.php', { numFuncion: '1', status: filtroStatus }, function (response) {
//         if (response.success) {
//             const comandas = response.data;
//             const tbody = $('#tablaComandas tbody');
//             tbody.empty();

//             comandas.forEach(comanda => {
//                 const row = `
//                     <tr>
//                         <td>${comanda.noPedido}</td>
//                         <td>${comanda.nombreCliente}</td>
//                         <td>${comanda.status}</td>
//                         <td>${comanda.fecha}</td>
//                         <td>${comanda.hora}</td>
//                         <td>
//                             <button class="btn btn-primary btn-sm" onclick="mostrarModal('${comanda.id}')">
//                                 <i class="bi bi-eye"></i>
//                             </button>
//                         </td>
//                     </tr>
//                 `;
//                 tbody.append(row);
//             });
//         } else {
//             console.error('Error en la solicitud:', response.message);
//         }
//     }, 'json').fail(function (jqXHR, textStatus, errorThrown) {
//         console.error('Error en la solicitud:', textStatus, errorThrown);
//         console.log('Detalles:', jqXHR.responseText);
//     });
// }

function cargarComandas() {
  const filtroStatus = $("#filtroStatus").val(); // Obtener el filtro seleccionado

  $.get(
    "../Servidor/PHP/mensajes.php",
    { numFuncion: "1", status: filtroStatus },
    function (response) {
      if (response.success) {
        const comandas = response.data;
        const tbody = $("#tablaComandas tbody");
        tbody.empty();

        comandas.forEach((comanda) => {
          const row = `
                    <tr>
                        <td>${comanda.noPedido || "-"}</td>
                        <td class="text-truncate" title="${
                          comanda.nombreCliente
                        }">${comanda.nombreCliente || "-"}</td>
                        <td>${comanda.status || "-"}</td>
                        <td>${comanda.fecha || "-"}</td>
                        <td>${comanda.hora || "-"}</td>
                        <td>
                            <button class="btn btn-secondary btn-sm" onclick="mostrarModal('${
                              comanda.id
                            }')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </td>
                    </tr>
                `;
          tbody.append(row);
        });
      } else {
        console.error("Error en la solicitud:", response.message);
      }
    },
    "json"
  ).fail(function (jqXHR, textStatus, errorThrown) {
    console.error("Error en la solicitud:", textStatus, errorThrown);
    console.log("Detalles:", jqXHR.responseText);
  });
}
function cargarPedidos(){
  const filtroPedido = $("#filtroPedido").val(); // Obtener el filtro seleccionado

  $.get(
    "../Servidor/PHP/mensajes.php",
    { numFuncion: "5", status: filtroPedido },
    function (response) {
      if (response.success) {
        const pedidos = response.data;
        const tbody = $("#tablaPedidos tbody");
        tbody.empty();

        pedidos.forEach((pedido) => {
          const row = `
                    <tr>
                        <td>${pedido.numero || "-"}</td>
                        <<td>${pedido.cliente || "-"}</td>
                        <td>${pedido.diaAlta || "-"}</td>
                        <td>${pedido.vendedor || "-"}</td>
                        <td>${pedido.status || "-"}</td>
                        <td>
                            <button class="btn btn-secondary btn-sm" onclick="mostrarModalPedido('${
                              pedido.id
                            }')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </td>
                    </tr>
                `;
          tbody.append(row);
        });
      } else {
        console.error("Error en la solicitud:", response.message);
      }
    },
    "json"
  ).fail(function (jqXHR, textStatus, errorThrown) {
    console.error("Error en la solicitud:", textStatus, errorThrown);
    console.log("Detalles:", jqXHR.responseText);
  });
}
function verificarNotificaciones() {
  $.get(
    "../Servidor/PHP/mensajes.php",
    { numFuncion: "4" },
    function (response) {
      if (response.success) {
        const { nuevosMensajes, nuevasComandas } = response.data;

        // Mostrar el icono de notificación en el menú de mensajes si hay nuevos mensajes
        if (nuevosMensajes > 0 || nuevasComandas > 0) {
          $("#mensajesNotificacion").removeClass("d-none");
          $("#mensajesNotificacion").text(nuevosMensajes + nuevasComandas); // Total de notificaciones
        } else {
          $("#mensajesNotificacion").addClass("d-none");
        }
      } else {
        console.error("Error al verificar notificaciones:", response.message);
      }
    },
    "json"
  ).fail(function (jqXHR, textStatus, errorThrown) {
    console.error(
      "Error en la solicitud de notificaciones:",
      textStatus,
      errorThrown
    );
  });
}

// Llamar periódicamente a la función de verificación de notificaciones
setInterval(verificarNotificaciones, 30000); // Verificar cada 30 segundos

$(document).ready(function () {
  cargarComandas();
  verificarNotificaciones(); // Verificar notificaciones al cargar la página
});

// Escuchar el cambio en el filtro
$("#filtroStatus").change(function () {
  cargarComandas(); // Recargar las comandas con el filtro aplicado
});

// function mostrarModal(comandaId) {
//     $.get('../Servidor/PHP/mensajes.php', { numFuncion: '2', comandaId }, function (response) {
//         if (response.success) {
//             const comanda = response.data;

//             // Cargar el ID en el campo oculto
//             $('#detalleIdComanda').val(comanda.id);

//             // Cargar los datos en los inputs
//             $('#detalleNoPedido').val(comanda.noPedido);
//             $('#detalleNombreCliente').val(comanda.nombreCliente);
//             $('#detalleStatus').val(comanda.status);
//             $('#detalleFecha').val(comanda.fecha);
//             $('#detalleHora').val(comanda.hora);

//             // Cargar los productos en la tabla
//             const productosList = $('#detalleProductos');
//             productosList.empty();
//             comanda.productos.forEach(producto => {
//                 const fila = `
//                     <tr>
//                         <td>${producto.clave}</td>
//                         <td>${producto.descripcion}</td>
//                         <td>${producto.cantidad}</td>
//                         <td>
//                             <button class="btn btn-danger btn-sm eliminarProducto">
//                                 <i class="bx bx-trash"></i> Eliminar
//                             </button>
//                         </td>
//                     </tr>`;
//                 productosList.append(fila);
//             });

//             // Agregar eventos para los botones de eliminar
//             $('.eliminarProducto').click(function () {
//                 $(this).closest('tr').remove();
//             });

//             // Mostrar el modal
//             $('#modalDetalles').modal('show');
//         } else {
//             alert('Error al obtener los detalles del pedido.');
//         }
//     }, 'json');
// }

function mostrarModal(comandaId) {
  $("#btnTerminar").show();
  $("#btnTerminar").prop("disabled", true);
  $("#divFechaEnvio").hide();
  $.get(
    "../Servidor/PHP/mensajes.php",
    { numFuncion: "2", comandaId },
    function (response) {
      if (response.success) {
        const comanda = response.data;

        // Cargar el ID en el campo oculto
        $("#detalleIdComanda").val(comanda.id);

        // Cargar los datos en los inputs
        $("#detalleNoPedido").val(comanda.noPedido);
        $("#detalleNombreCliente").val(comanda.nombreCliente);
        $("#detalleStatus").val(comanda.status);
        $("#detalleFecha").val(comanda.fecha);
        $("#detalleHora").val(comanda.hora);
        $("#numGuia").val(comanda.numGuia);

        // Cargar los productos en la tabla
        const productosList = $("#detalleProductos");
        productosList.empty();
        comanda.productos.forEach((producto, index) => {
          const fila = `
                 <tr>
                        <td>${producto.clave}</td>
                        <td>${producto.descripcion}</td>
                        <td>${producto.cantidad}</td>
                        <td>
                            <label class="container">
                                <input type="checkbox" class="producto-check" data-index="${index}">
                                <div class="checkmark"></div>
                            </label>
                        </td>
                    </tr>`;
          productosList.append(fila);
        });

        const status = comanda.status;
        if (status == "TERMINADA") {
          $(".producto-check").prop("checked", true);
          $(".producto-check").prop("disabled", false);
          $("#divFechaEnvio").show();
          $("#fechaEnvio").val(comanda.fechaEnvio);
          $("#btnTerminar").hide();
        } else {
          // Deshabilitar el botón "Terminar" inicialmente
          $("#btnTerminar").prop("disabled", true);
          // Listener para checkboxes
          $(".producto-check").change(function () {
            const allChecked =
              $(".producto-check").length ===
              $(".producto-check:checked").length;
            $("#btnTerminar").prop("disabled", !allChecked); // Activar solo si todos están marcados
          });
        }
        // Mostrar el modal
        $("#modalDetalles").modal("show");
      } else {
        alert("Error al obtener los detalles del pedido.");
      }
    },
    "json"
  );
}

// $('#btnTerminar').click(function () {
//     const comandaId = $('#detalleIdComanda').val();
//     $.post('../Servidor/PHP/mensajes.php', { numFuncion: '3', comandaId }, function (response) {
//         if (response.success) {
//             //alert('La comanda se ha marcado como TERMINADA.');
//             Swal.fire({
//                 text: "La comanda se ha marcado como TERMINADA.",
//                 icon: "success"
//               });
//             $('#modalDetalles').modal('hide');
//             cargarComandas(); // Recargar la tabla
//         } else {
//             alert('Error al marcar la comanda como TERMINADA.');
//         }
//     }, 'json');
// });

$("#btnTerminar").click(function () {
  const comandaId = $("#detalleIdComanda").val();
  const numGuia = $("#numGuia").val().trim(); // Obtener y limpiar espacios en la guía

  // Validar que el Número de Guía no esté vacío y tenga exactamente 9 dígitos
  if (numGuia === "" || !/^\d{9}$/.test(numGuia)) {
    Swal.fire({
      text: "El Número de Guía debe contener exactamente 9 dígitos.",
      icon: "warning",
    });
    return; // Detener el proceso si la validación falla
  }

  const horaActual = new Date().getHours(); // Obtener la hora actual en formato 24h
  const enviarHoy = horaActual < 15; // Antes de las 3 PM

  $.post(
    "../Servidor/PHP/mensajes.php",
    {
      numFuncion: "3",
      comandaId: comandaId,
      numGuia: numGuia,
      enviarHoy: enviarHoy,
    },
    function (response) {
      if (response.success) {
        Swal.fire({
          text: enviarHoy
            ? "La comanda se ha marcado como TERMINADA y se enviará hoy."
            : "La comanda se ha marcado como TERMINADA y se enviará mañana.",
          icon: "success",
        });
        $("#modalDetalles").modal("hide");
        cargarComandas(); // Recargar la tabla
      } else {
        Swal.fire({
          text: "Error al marcar la comanda como TERMINADA.",
          icon: "error",
        });
      }
    },
    "json"
  );
});

$(document).ready(function () {
  cargarComandas();
  cargarPedidos();
});
