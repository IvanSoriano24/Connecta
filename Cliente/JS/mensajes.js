function cargarComandas(tipoUsuario) {
  const filtroStatus = $("#filtroStatus").val();

  $.get(
    "../Servidor/PHP/mensajes.php",
    { numFuncion: "1", status: filtroStatus },
    function (response) {
      if (!response.success) {
        console.error("Error en la solicitud:", response.message);
        return;
      }

      const tbody = $("#tablaComandas tbody").empty();
      response.data.forEach((comanda) => {
        // 1) Crear la fila como objeto jQuery
        const $row = $(`
        <tr>
          <td>${comanda.noPedido || "-"}</td>
          <td class="text-truncate" title="${comanda.nombreCliente || ""}">
            ${comanda.nombreCliente || "-"}
          </td>
          <td>${comanda.status || "-"}</td>
          <td>${comanda.fecha || "-"}</td>
          <td>${comanda.hora || "-"}</td>
          <td>
            <button class="btn btn-secondary btn-sm" 
                    onclick="mostrarModal('${comanda.id}')">
              <i class="bi bi-eye"></i>
            </button>
          </td>
        </tr>
      `);

        // 2) Añadir la celda “Activar” si status es “Pendiente”
        if (tipoUsuario === "ADMINISTRADOR") {
          if (comanda.status === "Pendiente") {
            const $btn = $("<button>")
              .addClass("btn btn-success btn-sm")
              .text("Activar")
              .attr("onclick", `activarComanda("${comanda.id}")`);
            $row.append($("<td>").append($btn));
          } else {
            $row.append($("<td>").text("-"));
          }
        }

        // 3) Finalmente la fila al tbody
        tbody.append($row);
      });
    },
    "json"
  ).fail((jqXHR, textStatus, errorThrown) => {
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
$("#filtroPedido").change(function () {
  cargarPedidos(); // Recargar las comandas con el filtro aplicado
});
/*function obtenerEstados() {
  // Habilitamos el select
  //$("#estadoContacto").prop("disabled", false);

  $.ajax({
    url: "../Servidor/PHP/ventas.php",
    method: "POST",
    data: { numFuncion: "22" },
    dataType: "json",
    success: function (resEstado) {
      if (resEstado.success && Array.isArray(resEstado.data)) {
        const $estadoNuevoContacto = $("#estadoContacto");
        $estadoNuevoContacto.empty();
        $estadoNuevoContacto.append(
          "<option selected disabled>Selecciona un Estado</option>"
        );
        // Filtrar según el largo del RFC
        resEstado.data.forEach((estado) => {
          $estadoNuevoContacto.append(
            `<option value="${estado.Clave}" 
                data-Pais="${estado.Pais}"
                data-Descripcion="${estado.Descripcion}">
                ${estado.Descripcion}
              </option>`
          );
        });
      } else {
        Swal.fire({
          icon: "warning",
          title: "Aviso",
          text: resEstado.message || "No se encontraron estados.",
        });
        //$("#estadoNuevoContacto").prop("disabled", true);
      }
    },
    error: function () {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Error al obtener la lista de estados.",
      });
    },
  });
}*/
function obtenerEstados() {
  return $.ajax({
    url: "../Servidor/PHP/ventas.php",
    method: "POST",
    data: { numFuncion: "22" },
    dataType: "json"
  }).done(function(resEstado) {
    const $sel = $("#estadoContacto")
      .prop("disabled", false)
      .empty()
      .append('<option selected disabled>Selecciona un Estado</option>');
    if (resEstado.success && Array.isArray(resEstado.data)) {
      resEstado.data.forEach(e => {
        $sel.append(
          `<option value="${e.Clave}">${e.Descripcion}</option>`
        );
      });
    } else {
      Swal.fire("Aviso", resEstado.message || "No se encontraron estados.", "warning");
    }
  }).fail(function() {
    Swal.fire("Error", "No pude cargar la lista de estados.", "error");
  });
}

function obtenerMunicipios(edo, municipio) {
  // Habilitamos el select
  //$("#estadoContacto").prop("disabled", false);
  $.ajax({
    url: "../Servidor/PHP/ventas.php",
    method: "POST",
    data: { numFuncion: "23", estado: edo },
    dataType: "json",
    success: function (resMunicipio) {
      if (resMunicipio.success && Array.isArray(resMunicipio.data)) {
        const $municipioNuevoContacto = $("#municipioContacto");
        $municipioNuevoContacto.empty();
        $municipioNuevoContacto.append(
          "<option selected disabled>Selecciona un Estado</option>"
        );
        // Filtrar según el largo del RFC
        resMunicipio.data.forEach((municipio) => {
          $municipioNuevoContacto.append(
            `<option value="${municipio.Clave}" 
                data-estado="${municipio.Estado}"
                data-Descripcion="${municipio.Descripcion || ""}">
                ${municipio.Descripcion}
              </option>`
          );
        });
        $("#municipioContacto").val(municipio);
      } else {
        Swal.fire({
          icon: "warning",
          title: "Aviso",
          text: resMunicipio.message || "No se encontraron municipios.",
        });
        //$("#municipioNuevoContacto").prop("disabled", true);
      }
    },
    error: function () {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Error al obtener la lista de estados.",
      });
    },
  });
}
function obtenerDatosEnvioEditar(pedidoID) {
  //
  $("#datosEnvio").prop("disabled", false);
  $.post(
    "../Servidor/PHP/clientes.php",
    {
      numFuncion: 10, // Función para obtener el pedido por ID
      pedidoID: pedidoID,
    },
    function (response) {
      console.log("Respuesta cruda:", response); // 👈 Imprime lo que llega
      if (response.success) {
        const pedido = response.data;
        console.log("Datos de Envio:", pedido);

        /*document.getElementById("idDatos").value = pedido[0].idDocumento || "";
        document.getElementById("folioDatos").value = pedido[0].id || "";*/
        document.getElementById("nombreContacto").value =
          pedido[0].nombreContacto || "";
        //document.getElementById("titutoDatos").value = pedido[0].tituloEnvio || "";
        document.getElementById("compañiaContacto").value =
          pedido[0].compania || "";
        document.getElementById("telefonoContacto").value =
          pedido[0].telefonoContacto || "";
        document.getElementById("correoContacto").value =
          pedido[0].correoContacto || "";
        document.getElementById("direccion1Contacto").value =
          pedido[0].linea1 || "";
        document.getElementById("direccion2Contacto").value =
          pedido[0].linea2 || "";
        document.getElementById("codigoContacto").value =
          pedido[0].codigoPostal || "";

        const edo = pedido[0].estado;
        const municipio = pedido[0].municipio;
        console.log("Estado Crudo: ", edo);

        //$("#estadoContacto").val(edo);
        $("#estadoContacto").val(edo);

        obtenerMunicipios(edo, municipio);
        console.log("Datos de envio cargados correctamente.");
      } else {
        Swal.fire({
          title: "Aviso",
          text: "No se pudo cargar el pedido.",
          icon: "warning",
          confirmButtonText: "Aceptar",
        });
        //alert("No se pudo cargar el pedido: " + response.message);
      }
    },
    "json"
  ).fail(function (jqXHR, textStatus, errorThrown) {
    //console.log(errorThrown);
    Swal.fire({
      title: "Aviso",
      text: "Error al cargar el pedido.",
      icon: "error",
      confirmButtonText: "Aceptar",
    });
    //alert("Error al cargar el pedido: " + textStatus + " " + errorThrown);
    console.log("Error al cargar el pedido: " + textStatus + " " + errorThrown);
  });
}
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

        obtenerEstados();
        obtenerDatosEnvioEditar(comanda.noPedido);

        // Cargar los productos en la tabla
        const productosList = $("#detalleProductos");
        productosList.empty();
        comanda.productos.forEach((producto, index) => {
          const fila = `
                 <tr>
                        <td style="display: table-cell !important;">${producto.clave}</td>
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
          $(".producto-check").prop("disabled", true);
          $("#divFechaEnvio").show();
          $("#fechaEnvio").val(comanda.fechaEnvio);
          $("#btnTerminar").hide();
          $("#numGuia").prop("disabled", true);
        }
        if (status == "Pendiente") {
          $(".producto-check").prop("checked", false);
          $(".producto-check").prop("disabled", true);
          $("#divFechaEnvio").show();
          $("#fechaEnvio").prop("disabled", true);
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
                        <td style="display: table-cell !important;">${producto.producto}</td>
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

  $("#datEnvio").on("click", function () {
    const $btn = $(this);
    const $datos = $("#datosEnvio");

    // alternamos la clase d-none
    $datos.toggleClass("d-none");

    // según esté oculto o no, cambiamos el texto
    if ($datos.hasClass("d-none")) {
      $btn.val("Mostrar datos de envío");
    } else {
      $btn.val("Ocultar datos de envío");
    }
  });
