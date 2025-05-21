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
function cargarPedidos() {
  const filtroPedido = $("#filtroPedido").val(); // Obtener el filtro seleccionado

  $.get(
    "../Servidor/PHP/mensajes.php",
    { numFuncion: "5", status: filtroPedido },
    function (response) {
      const tbody = $("#tablaPedidos tbody");
      tbody.empty(); // Limpiar la tabla antes de agregar nuevos datos

      if (
        response.success &&
        Array.isArray(response.data) &&
        response.data.length > 0
      ) {
        const pedidos = response.data;

        pedidos.forEach((pedido) => {
          const color =
            pedido.status === "Autorizado"
              ? "green"
              : pedido.status === "Rechazado"
              ? "red"
              : pedido.status === "Sin Autorizar"
              ? "blue"
              : "black";

          const row = `
                    <tr>
                        <td>${pedido.folio || "N/A"}</td>
                        <td>${pedido.cliente || "N/A"}</td>
                        <td>${pedido.diaAlta || "N/A"}</td>
                        <td>${pedido.vendedor || "N/A"}</td>
                        <td style="color: ${color};">${
            pedido.status || "N/A"
          }</td>
                        <td style="text-align: right;">${
                          pedido.totalPedido
                            ? `$${parseFloat(pedido.totalPedido).toFixed(2)}`
                            : "N/A"
                        }</td>
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
        console.warn("No hay pedidos para mostrar.");
        tbody.append(`
          <tr>
            <td colspan="7" class="text-center text-muted">No hay datos para mostrar</td>
          </tr>
        `);
      }
    },
    "json"
  ).fail(function (jqXHR, textStatus, errorThrown) {
    console.error("Error en la solicitud:", textStatus, errorThrown);
    console.log("Detalles:", jqXHR.responseText);

    $("#tablaPedidos tbody").empty().append(`
      <tr>
        <td colspan="7" class="text-center text-danger">Error al obtener los pedidos</td>
      </tr>
    `);
  });
}

// Escuchar el cambio en el filtro
$("#filtroStatus").change(function () {
  cargarComandas(); // Recargar las comandas con el filtro aplicado
});
// Escuchar el cambio en el filtro
$("#filtroPedido").change(function () {
  cargarPedidos(); // Recargar las comandas con el filtro aplicado
});

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
                        <td style="text-align: right;">${producto.cantidad}</td>
                        <td style="text-align: right;">${producto.lote}</td>
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
          $("#numGuia").prop("disabled", true);
        }
        if (status == "Pendiente") {
          $(".producto-check").prop("checked", true);
          $(".producto-check").prop("disabled", false);
          $("#divFechaEnvio").show();
          $("#fechaEnvio").val(comanda.fechaEnvio);
          $("#btnTerminar").hide();
          $("#numGuia").prop("disabled", true);
        }
        if (status == "Cancelada") {
          $(".producto-check").prop("checked", true);
          $(".producto-check").prop("disabled", false);
          $("#divFechaEnvio").show();
          $("#fechaEnvio").val(comanda.fechaEnvio);
          $("#btnTerminar").hide();
          $("#numGuia").prop("disabled", true);
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
function mostrarModalPedido(pedidoId) {
  $("#btnAutorizar").show();
  $("#btnAutorizar").prop("disabled", true);
  $.get(
    "../Servidor/PHP/mensajes.php",
    { numFuncion: "6", pedidoId },
    function (response) {
      if (response.success) {
        const pedido = response.data;
        console.log(pedido);
        // Cargar el ID en el campo oculto
        $("#detalleIdPedido").val(pedido.id);
        $("#noEmpresa").val(pedido.noEmpresa);
        $("#claveSae").val(pedido.claveSae);
        $("#vendedor").val(pedido.vendedor);

        // Cargar los datos en los inputs
        $("#folio").val(pedido.folio);
        $("#nombreCliente").val(pedido.cliente);
        $("#status").val(pedido.status);
        $("#diaAlta").val(pedido.diaAlta);

        // Cargar los productos en la tabla
        const productosList = $("#detallePartidas");
        productosList.empty();
        pedido.productos.forEach((producto, index) => {
          const fila = `
                 <tr>
                        <td>${producto.producto}</td>
                        <td>${producto.descripcion}</td>
                        <td>${producto.cantidad}</td>
                        <td style="text-align: right;">$${producto.subtotal}</td>
                    </tr>`;
          productosList.append(fila);
        });

        const status = pedido.status;
        if (status == "Autorizado" || status == "Rechazado") {
          $("#btnAutorizar").hide();
        } else {
          // Deshabilitar el botón "Terminar" inicialmente
          $("#btnAutorizar").show();
          $("#btnAutorizar").prop("disabled", false);
        }
        // Mostrar el modal
        $("#modalPedido").modal("show");
      } else {
        alert("Error al obtener los detalles del pedido.");
      }
    },
    "json"
  );
}

$("#btnTerminar").click(function () {
  const comandaId = $("#detalleIdComanda").val();
  const numGuia = $("#numGuia").val().trim(); // Obtener y limpiar espacios en la guía
  const token = $("#csrf_token_C").val().trim();

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
      token: token,
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
$("#btnAutorizar").click(function () {
  Swal.fire({
    title: "Procesando pedido...",
    text: "Por favor, espera mientras se autoriza el pedido.",
    allowOutsideClick: false,
    allowEscapeKey: false,
    didOpen: () => {
      Swal.showLoading();
    },
  });
  const pedidoId = $("#detalleIdPedido").val();
  const folio = $("#folio").val();
  const noEmpresa = $("#noEmpresa").val();
  const claveSae = $("#claveSae").val();
  const vendedor = $("#vendedor").val();
  const token = $("#csrf_tokenP").val();
  $.post(
    "../Servidor/PHP/mensajes.php",
    {
      numFuncion: "7",
      pedidoId: pedidoId,
      folio: folio,
      noEmpresa: noEmpresa,
      claveSae: claveSae,
      vendedor: vendedor,
      token: token,
    },
    function (response) {
      if (response.success) {
        if (response.notificacion) {
          Swal.fire({
            text: "El pedido fue autorizado",
            icon: "success",
          }).then(() => {
            $("#modalPedido").modal("hide");
            cargarPedidos(); // Recargar la tabla
            //window.location.reload();
          });
        } else if (response.telefono) {
          Swal.fire({
            text: response.message,
            icon: "success",
          }).then(() => {
            $("#modalPedido").modal("hide");
            cargarPedidos(); // Recargar la tabla
            //window.location.reload();
          });
        } else if (response.correo) {
          Swal.fire({
            text: response.message,
            icon: "success",
          }).then(() => {
            $("#modalPedido").modal("hide");
            cargarPedidos(); // Recargar la tabla
            //window.location.reload();
          });
        } else {
          Swal.fire({
            text: response.message,
            icon: "success",
          });
        }
      } else {
        Swal.fire({
          text: "Error al autorizar el pedido.",
          icon: "error",
        });
      }
    },
    "json"
  );
});

$("#btnRechazar").click(function () {
  Swal.fire({
    title: "Procesando pedido...",
    text: "Por favor, espera mientras se rechaza el pedido.",
    allowOutsideClick: false,
    allowEscapeKey: false,
    didOpen: () => {
      Swal.showLoading();
    },
  });
  const pedidoId = $("#detalleIdPedido").val();
  const folio = $("#folio").val();
  const cliente = $("#nombreCliente").val();
  const vendedor = $("#vendedor").val();
  $.get(
    "../Servidor/PHP/mensajes.php",
    { numFuncion: "8", pedidoId, folio, vendedor, cliente },
    function (response) {
      if (response.success) {
        Swal.fire({
          text: "El pedido fue rechazado",
          icon: "success",
        }).then(() => {
          $("#modalPedido").modal("hide");
          cargarPedidos(); // Recargar la tabla
          //window.location.reload();
        });
      } else {
        Swal.fire({
          text: "Error al rechazar el pedido.",
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
