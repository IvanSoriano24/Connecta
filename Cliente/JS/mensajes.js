//Funcion para cargar las comandas
// ==================== COMANDAS ====================
// Aquí va toda la logica para el filtrado de comandas

let todasComandas = [];

// Cargar comandas desde PHP
function cargarComandas(tipoUsuario) {
    const filtroStatus = $("#filtroStatus").val();

    $.get(
        "../Servidor/PHP/mensajes.php",
        { numFuncion: "1", status: filtroStatus }, // status se filtra en el backend
        function (response) {
            if (response.success && Array.isArray(response.data)) {
                todasComandas = response.data; // guardar copia limpia
                pintarComandas(todasComandas, tipoUsuario); // mostrar sin filtros locales
            } else {
                $("#tablaComandas tbody").empty().append(`
                    <tr>
                        <td colspan="8" class="text-center text-muted">
                            No hay comandas para mostrar
                        </td>
                    </tr>
                `);
            }
        },
        "json"
    ).fail((jqXHR, textStatus, errorThrown) => {
        console.error("Error:", textStatus, errorThrown);
        console.log("Detalles:", jqXHR.responseText);
    });
}

// Función para aplicar buscador y filtros locales
function aplicarFiltros(tipoUsuario) {
    const texto = ($("#buscarTexto").val() || "").toLowerCase().trim();
    const fecha = ($("#filtroFecha").val() || "").trim();
    const pedido = ($("#filtroNoPedido").val() || "").trim();
    //console.log("Filtros:", { texto, fecha, pedido, todasComandas });

    let filtradas = todasComandas;

    // Filtro por texto
    if (texto) {
        filtradas = filtradas.filter(c => {
            const nombre = (c.nombreCliente || "").toLowerCase().trim();
            const status = (c.status || "").toLowerCase().trim();
            const noPed  = (c.noPedido || "").toLowerCase().trim();
            return (
                nombre.includes(texto) ||
                status.includes(texto) ||
                noPed.includes(texto)
            );
        });
    }

    // Filtro por fecha
    if (fecha) {
        filtradas = filtradas.filter(c => c.fecha && c.fecha.trim() === fecha);
    }

    // Filtro por número de pedido
    if (pedido) {
        filtradas = filtradas.filter(c => {
            const pedidoActual = (c.noPedido || "").trim();
            return pedidoActual === pedido; // comparación directa string con string
        });
    }

    // Si to-do está vacío → mostrar todas
    if (!texto && !fecha && !pedido) {
        filtradas = todasComandas;
    }

    pintarComandas(filtradas, tipoUsuario);
}

// Función que pinta la tabla
function pintarComandas(lista, tipoUsuario) {
    const tbody = $("#tablaComandas tbody").empty();

    if (!lista || lista.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="8" class="text-center text-muted">
                    No se encontraron resultados
                </td>
            </tr>
        `);
        return;
    }

    lista.forEach(comanda => {
        const $row = $(`
            <tr>
              <td>${comanda.noPedido || "-"}</td>
              <td class="text-truncate" title="${comanda.nombreCliente || ""}">
                ${comanda.nombreCliente || "-"}
              </td>
              <td>${comanda.status || "-"}</td>
              <td>${comanda.fecha || "-"}</td>
              <td>${comanda.hora || "-"}</td>
              <td class="text-center">
                  <i class="bi btn-comandas bi-clipboard-data"
                     title="Ver Detalles"
                     data-bs-toggle="tooltip" data-bs-placement="top" data-bs-delay='{"show":0,"hide":0}'
                     onclick="mostrarModal('${comanda.id}')"></i>
                </td>
                
                <td class="text-center">
                  <i class="bi btn-comandas bi-clipboard-check"
                     title="Verificar Remisión"
                     data-bs-toggle="tooltip" data-bs-placement="top" data-bs-delay='{"show":0,"hide":0}'
                     onclick="verificarRemision('${comanda.noPedido}', '${comanda.id}')"></i>
                </td>
                
                <td class="text-center">
                  <i class="bi btn-comandas bi-printer"
                     title="Imprimir Comanda"
                     data-bs-toggle="tooltip" data-bs-placement="top" data-bs-delay='{"show":0,"hide":0}'
                     onclick="imprimirComanda('${comanda.id}')"></i>
                </td>
            </tr>
        `);

        if (tipoUsuario === "ADMINISTRADOR") {
            if (comanda.status === "Pendiente") {
                const $btn = $("<button>")
                    .addClass("btn btn-success btn-sm")
                    .text("Activar")
                    .attr("onclick", `activarComanda("${comanda.id}")`);
                $row.append($("<td>").append($btn));
            } else {
                $row.append($("<td class='text-center'>").text("-"));
            }
        }

        tbody.append($row);
    });
    initTooltipsTabla();

}


// Función para imprimir la comanda
function imprimirComanda(comandaId) {
    $.get(
        "../Servidor/PHP/mensajes.php",
        { numFuncion: "2", comandaId },
        function (response) {
            if (!response.success) {
                return Swal.fire("Error", "No se pudo obtener la comanda", "error");
            }

            const comanda = response.data;

            // === ENCABEZADO DEL DOCUMENTO ===
            const encabezado = {
                columns: [
                    { text: "COMANDA DE PEDIDO", style: "titulo" },
                    {
                        text: `No. Pedido: ${comanda.noPedido || "-"}`,
                        style: "pedido",
                        alignment: "right"
                    }
                ]
            };

            // === INFORMACIÓN GENERAL ===
            const infoGeneral = {
                style: "tablaInfo",
                table: {
                    widths: ["25%", "25%", "25%", "25%"],
                    body: [
                        [
                            { text: "Cliente", style: "th" },
                            { text: comanda.nombreCliente || "-" },
                            { text: "Status", style: "th" },
                            { text: comanda.status || "-" }
                        ],
                        [
                            { text: "Fecha", style: "th" },
                            { text: comanda.fecha || "-" },
                            { text: "Hora", style: "th" },
                            { text: comanda.hora || "-" }
                        ],
                        [
                            { text: "Num. Guía", style: "th" },
                            { text: comanda.numGuia || "-" },
                            { text: "Observaciones", style: "th" },
                            { text: comanda.observaciones || "-" }
                        ]
                    ]
                },
                layout: "lightHorizontalLines"
            };

            // === PRODUCTOS ===
            const productosHeader = [
                { text: "Clave", style: "th" },
                { text: "Descripción", style: "th" },
                { text: "Cantidad", style: "th" },
                { text: "Lote", style: "th" }
            ];

            const productosBody = comanda.productos.map(prod => [
                prod.clave || "-",
                prod.descripcion || "-",
                { text: prod.cantidad || "-", alignment: "right" },
                prod.lote || "-"
            ]);

            const tablaProductos = {
                style: "tablaProductos",
                table: {
                    widths: ["20%", "40%", "20%", "20%"],
                    body: [productosHeader, ...productosBody]
                },
                layout: {
                    fillColor: function (i) {
                        return i === 0 ? "#0d6efd" : null; // azul para encabezado
                    },
                    hLineColor: () => "#ccc",
                    vLineColor: () => "#ccc",
                }
            };

            // === DATOS DE ENVÍO ===
            const envio = comanda.envioData || {};
            const tablaEnvio = {
                style: "tablaEnvio",
                table: {
                    widths: ["30%", "70%"],
                    body: [
                        [{ text: "Datos de envío", style: "th", colSpan: 2, alignment: "center" }, {}],
                        ["Nombre del contacto", envio.nombreContacto || "-"],
                        ["Compañía", envio.companiaContacto || "-"],
                        ["Teléfono", envio.telefonoContacto || "-"],
                        ["Correo", envio.correoContacto || "-"],
                        ["Dirección 1", envio.direccion1Contacto || "-"],
                        ["Dirección 2", envio.direccion2Contacto || "-"],
                        ["Código Postal", envio.codigoContacto || "-"],
                        ["Estado", envio.estadoContacto || "-"],
                        ["Municipio", envio.municipioContacto || "-"]
                    ]
                },
                layout: "lightHorizontalLines"
            };

            // === DEFINICIÓN DEL PDF ===
            const docDefinition = {
                content: [
                    encabezado,
                    { text: "\n" },
                    infoGeneral,
                    { text: "\n" },
                    { text: "Productos", style: "subtitulo" },
                    tablaProductos,
                    { text: "\n" },
                    tablaEnvio
                ],
                styles: {
                    titulo: {
                        fontSize: 20,
                        bold: true,
                        color: "#0d6efd"
                    },
                    pedido: {
                        fontSize: 12,
                        bold: true
                    },
                    subtitulo: {
                        fontSize: 16,
                        bold: true,
                        margin: [0, 10, 0, 5]
                    },
                    th: {
                        bold: true,
                        fillColor: "#f2f2f2",
                        color: "#000"
                    },
                    tablaInfo: {
                        margin: [0, 10, 0, 10]
                    },
                    tablaProductos: {
                        margin: [0, 10, 0, 10]
                    },
                    tablaEnvio: {
                        margin: [0, 10, 0, 10]
                    }
                },
                defaultStyle: {
                    fontSize: 10
                },
                footer: function (currentPage, pageCount) {
                    return {
                        columns: [
                            {
                                text: "Documento generado automáticamente por MDConnecta",
                                alignment: "center",
                                fontSize: 9,
                                color: "#777",
                                margin: [0, 10, 0, 0]
                            }
                        ]
                    };
                },
                info: {
                    title: `Comanda ${comanda.noPedido || "sinNumero"}`,
                    author: 'MDConnecta',
                    subject: 'Comanda de pedido',
                    keywords: 'comanda, mdconnecta, pedido'
                },
            };

            // Descargar el PDF
             const nombreArchivo = `Comanda ${comanda.noPedido || "sinNumero"}.pdf`;
             pdfMake.createPdf(docDefinition).download(nombreArchivo);
        },
        "json"
    );
}

// Función para agregar un mensaje de ayuda a los iconos de las comandas
function initTooltipsTabla() {
    const nodes = document.querySelectorAll('#tablaComandas [data-bs-toggle="tooltip"]');
    nodes.forEach(el => {
        const inst = bootstrap.Tooltip.getInstance(el);
        if (inst) inst.dispose();
        new bootstrap.Tooltip(el, { delay: { show: 0, hide: 0 }, trigger: 'hover' });
    });
}


// Eventos en buscador y filtros locales
$(document).on("input change", "#buscarTexto, #filtroFecha, #filtroNoPedido", function () {
    aplicarFiltros("ADMINISTRADOR");
});

$(document).on("click", "#btnLimpiarFiltros", function () {
    $("#buscarTexto").val("");
    $("#filtroFecha").val("");
    $("#filtroNoPedido").val("");
    $("#filtroStatus").val(""); // reset a "Todos"

    aplicarFiltros("ADMINISTRADOR"); // refrescar tabla
});
// =================================================================================================================



// ==================== PEDIDOS ====================

// Función para cargar los pedidos a autorizar
function cargarPedidos() {
    const filtroPedido = $("#filtroPedido").val();

    $.get(
        "../Servidor/PHP/mensajes.php",
        { numFuncion: "5", status: filtroPedido },
        function (response) {
            const tbody = $("#tablaPedidos tbody").empty();

            if (
                response.success &&
                Array.isArray(response.data) &&
                response.data.length > 0
            ) {
                response.data.forEach((pedido) => {
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
                            <td style="color: ${color};">${pedido.status || "N/A"}</td>
                            <td style="text-align: right;">
                                ${pedido.totalPedido
                        ? `$${parseFloat(pedido.totalPedido).toFixed(2)}`
                        : "N/A"}
                            </td>
                            <td>
                                <button class="btn btn-secondary btn-sm" onclick="mostrarModalPedido('${pedido.id}')">
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
                        <td colspan="7" class="text-center text-muted">
                            No hay pedidos para mostrar
                        </td>
                    </tr>
                `);
            }
        },
        "json"
    ).fail((jqXHR, textStatus, errorThrown) => {
        console.error("Error en la solicitud:", textStatus, errorThrown);
        console.log("Detalles:", jqXHR.responseText);

        $("#tablaPedidos tbody").empty().append(`
            <tr>
                <td colspan="7" class="text-center text-danger">
                    Error al obtener los pedidos
                </td>
            </tr>
        `);
    });
}

// Escuchar cambios en el filtro de pedidos
$("#filtroPedido").change(function () {
    cargarPedidos();
});


function obtenerEstados() {
    return $.ajax({
        url: "../Servidor/PHP/ventas.php",
        method: "POST",
        data: {numFuncion: "22"},
        dataType: "json",
    })
        .done(function (resEstado) {
            const $sel = $("#estadoContacto")
                .prop("disabled", false)
                .empty()
                .append("<option selected disabled>Selecciona un Estado</option>");
            if (resEstado.success && Array.isArray(resEstado.data)) {
                resEstado.data.forEach((e) => {
                    $sel.append(`<option value="${e.Clave}">${e.Descripcion}</option>`);
                });
            } else {
                Swal.fire(
                    "Aviso",
                    resEstado.message || "No se encontraron estados.",
                    "warning"
                );
            }
        })
        .fail(function () {
            Swal.fire("Error", "No pude cargar la lista de estados.", "error");
        });
}

function obtenerMunicipios(edo, municipio) {
    // Habilitamos el select
    //$("#estadoContacto").prop("disabled", false);
    $.ajax({
        url: "../Servidor/PHP/ventas.php",
        method: "POST",
        data: {numFuncion: "23", estado: edo},
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

function obtenerDatosEnvioEditar(envioData) {
    console.log("Datos de Envio:", envioData);

    /*document.getElementById("idDatos").value = pedido[0].idDocumento || "";
          document.getElementById("folioDatos").value = pedido[0].id || "";*/
    document.getElementById("nombreContacto").value =
        envioData.nombreContacto || "";
    //document.getElementById("titutoDatos").value = pedido[0].tituloEnvio || "";
    document.getElementById("compañiaContacto").value =
        envioData.companiaContacto || "";
    document.getElementById("telefonoContacto").value =
        envioData.telefonoContacto || "";
    document.getElementById("correoContacto").value =
        envioData.correoContacto || "";
    document.getElementById("direccion1Contacto").value =
        envioData.direccion1Contacto || "";
    document.getElementById("direccion2Contacto").value =
        envioData.direccion2Contacto || "";
    document.getElementById("codigoContacto").value =
        envioData.codigoContacto || "";

    document.getElementById("estadoContacto").value =
        envioData.estadoContacto || "";
    document.getElementById("municipioContacto").value =
        envioData.municipioContacto || "";

    console.log("Datos de envio cargados correctamente.");
}

function obtenerEstadosComanda(estadoSeleccionado, municipioSeleccionado) {
    $.ajax({
        //url: "../Servidor/PHP/ventas.php",
        url: "../Servidor/PHP/mensajes.php",
        method: "POST",
        //data: { numFuncion: "25", estadoSeleccionado: estadoSeleccionado },
        data: {numFuncion: "10", estadoSeleccionado: estadoSeleccionado},
        dataType: "json",
        success: function (resEstado) {
            const $sel = $("#estadoContacto")
                .prop("disabled", true)
                .empty()
                .append("<option selected disabled>Selecciona un Estado</option>");

            if (resEstado.success) {
                // Normaliza a array aunque venga un solo objeto
                const estados = Array.isArray(resEstado.data)
                    ? resEstado.data
                    : [resEstado.data];
                console.log("Estado: ", estados);
                estados.forEach((e) => {
                    $sel.append(`<option value="${e.Clave}">${e.Descripcion}</option>`);
                });
                if (estadoSeleccionado) {
                    $sel.val(estadoSeleccionado);
                    // Si además hay municipio, lo pasamos para poblar ese select
                    if (municipioSeleccionado) {
                        //obtenerMunicipios(estadoSeleccionado, municipioSeleccionado);
                    }
                }
            } else {
                Swal.fire({
                    icon: "warning",
                    title: "Aviso",
                    text: resEstado.message || "No se encontraron estados.",
                });
            }
        },
        error: function () {
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "No pude cargar la lista de estados.",
            });
        },
    });
}

function mostrarModal(comandaId) {
    // Resetear controles
    $("#btnTerminar").show().prop("disabled", true);
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
                $("#activada").val(comanda.activada);
                $("#detalleNoPedido").val(comanda.noPedido);
                $("#detalleNombreCliente").val(comanda.nombreCliente);
                $("#detalleStatus").val(comanda.status);
                $("#detalleFecha").val(comanda.fecha);
                $("#detalleHora").val(comanda.hora);
                $("#numGuia").val(comanda.numGuia).prop("disabled", false);
                $("#observaciones").val(comanda.observaciones);

                // Datos de envío
                obtenerDatosEnvioEditar(comanda.envioData);

                // Cargar los productos en la tabla
                const productosList = $("#detalleProductos").empty();
                comanda.productos.forEach((producto, index) => {
                    const fila = `
                        <tr>
                            <td style="display: table-cell !important;">${producto.clave}</td>
                            <td>${producto.descripcion}</td>
                            <td style="text-align: right;">${producto.cantidad}</td>
                            <td style="text-align: right;">${producto.lote}</td>
                            <td>
                                <label class="container">
                                    <input type="checkbox" 
                                           class="producto-check" 
                                           data-index="${index}" 
                                           ${producto.checked ? "checked" : ""}>
                                    <div class="checkmark"></div>
                                </label>
                            </td>
                        </tr>`;
                    productosList.append(fila);
                });

                // Manejo de status
                const status = comanda.status;
                if (status === "TERMINADA") {
                    $(".producto-check").prop("checked", true).prop("disabled", true);
                    $("#divFechaEnvio").show();
                    $("#fechaEnvio").val(comanda.fechaEnvio);
                    $("#btnTerminar").hide();
                    $("#numGuia").prop("disabled", true);
                } else if (status === "Pendiente") {
                    $(".producto-check").prop("checked", false).prop("disabled", true);
                    $("#divFechaEnvio").show();
                    $("#fechaEnvio").prop("disabled", true);
                    $("#btnTerminar").hide();
                    $("#numGuia").prop("disabled", true);
                } else if (status === "CANCELADO") {
                    $(".producto-check").prop("checked", false).prop("disabled", true);
                    $("#divFechaEnvio").show();
                    $("#fechaEnvio").val(comanda.fechaEnvio);
                    $("#btnTerminar").hide();
                    $("#numGuia").prop("disabled", true);
                } else {
                    // Botón terminar deshabilitado inicialmente
                    $("#btnTerminar").prop("disabled", true);

                    // Listener para habilitar el botón si todos están marcados
                    $(".producto-check").off("change.enableFinish").on("change.enableFinish", function () {
                        const allChecked =
                            $(".producto-check").length === $(".producto-check:checked").length;
                        $("#btnTerminar").prop("disabled", !allChecked);
                    });
                }

                // Mostrar modal
                $("#modalDetalles").modal("show");
            } else {
                alert("Error al obtener los detalles del pedido.");
            }
        },
        "json"
    );
}

// ====================== AUTOGUARDADO de checks ======================
$(document).on("change", ".producto-check", function () {
    const index = $(this).data("index");
    const checked = $(this).is(":checked");
    const comandaId = $("#detalleIdComanda").val();

    //console.log("Autoguardado:", { index, checked, comandaId });

    $.post("../Servidor/PHP/mensajes.php", {
        numFuncion: "14",
        comandaId: comandaId,
        index: index,
        checked: checked
    }, function (response) {
        //console.log("Respuesta del servidor:", response);
        if (!response.success) {
            Swal.fire("Error", "No se pudo guardar el avance", "error");
        }
    }, "json").fail((jqXHR, textStatus, errorThrown) => {
        console.error("Error AJAX:", textStatus, errorThrown);
        console.log("Respuesta cruda:", jqXHR.responseText);
    });
});

// =================================================================================================================

function mostrarModalPedido(pedidoId) {
    //Abilitar y/o desabilitar campos
    $("#btnAutorizar").show();
    $("#btnAutorizar").prop("disabled", true);
    $.get(
        "../Servidor/PHP/mensajes.php",
        {numFuncion: "6", pedidoId},
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

function verificarRemision(noPedido, comanda) {
    $.get(
        "../Servidor/PHP/mensajes.php",
        {
            numFuncion: "11",
            noPedido: noPedido,
        },
        function (response) {
            if (response.success) {
                const {statusCode, statusText, remisionDoc} = response.data;
                console.log("Código status:", statusCode);
                console.log("Texto de status:", statusText);

                if (statusCode === "E" || statusCode === "O") {
                    // Remisión activa
                    Swal.fire({
                        icon: "success",
                        title: "Remisión Activa",
                        html: `La remisión <strong>${remisionDoc}</strong> está activa.<br/><em>(${statusText})</em>`,
                    });
                } else if (statusCode === "C") {
                    cancelarComanda(comanda);
                    // Remisión cancelada
                    Swal.fire({
                        icon: "error",
                        title: "Remisión Cancelada",
                        html: `La remisión <strong>${remisionDoc}</strong> ha sido cancelada.`,
                    });
                    cargarComandas(tipoUsuario);
                } else {
                    // Sin remisión
                    Swal.fire({
                        icon: "warning",
                        title: "Sin Remisión",
                        text: "No se encontró ninguna remisión para este pedido.",
                    });
                }
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Error al verificar remisión: " + response.message,
                });
            }
        },
        "json"
    ).fail(function (jqXHR, textStatus) {
        Swal.fire({
            icon: "error",
            title: "Error de Red",
            text: "No se pudo conectar: " + textStatus,
        });
    });
}

function cancelarComanda(comanda) {
    $.get(
        "../Servidor/PHP/mensajes.php",
        {
            numFuncion: "12",
            comandaId: comanda,
        },
        function (response) {
            if (response.success) {
                /*Swal.fire({
                  text: "La comanda se Cancelo",
                  icon: "success",
                });*/
                /*$("#modalDetalles").modal("hide");
                cargarComandas(tipoUsuario); // Recargar la tabla*/
            } else {
                Swal.fire({
                    text: "Error al marcar la comanda como TERMINADA.",
                    icon: "error",
                });
            }
        },
        "json"
    );
}

function verificarComandas() {
    $.get(
        "../Servidor/PHP/mensajes.php",
        {numFuncion: "13"},
        function (resp) {
            if (!resp.success) {
                return Swal.fire("Error", " " + resp.message, "error");
            }
            const {canceladas, noCanceladas} = resp;

            let texto = "";
            if (canceladas.length > 0) {
                texto +=
                    `✅ Comandas canceladas:<br>` +
                    canceladas.map((c) => c.noPedido).join(", ") +
                    "<br><br>";
            }
            if (canceladas.length < 0) {
                texto += `No se cancelaron comandas`;
            }
            if (!texto) {
                texto = "No había comandas que procesar.";
            }

            Swal.fire({
                title: "Resultado de Verificación",
                html: texto,
                icon: "info",
            });
        },
        "json"
    ).fail(function (err) {
        Swal.fire(
            "Error de red",
            "No se pudo conectar: " + err.statusText,
            "error"
        );
    });
}

//Funcion para autorizar el pedido
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
    //Obtener los datos del pedido
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
                    //Mostrar mensaje de exito
                    Swal.fire({
                        text: "El pedido fue autorizado",
                        icon: "success",
                    }).then(() => {
                        //Cerrar modal y recargar la tabla
                        $("#modalPedido").modal("hide");
                        cargarPedidos();
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
    ).fail(function (err) {
        console.log(response)
        Swal.fire(
            "Error de red",
            err.statusText,
            "error"
        );
    });
});
//Funcion para rechazar el pedido
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
    //Obtener los datos del pedido
    const pedidoId = $("#detalleIdPedido").val();
    const folio = $("#folio").val();
    const cliente = $("#nombreCliente").val();
    const vendedor = $("#vendedor").val();
    $.get(
        "../Servidor/PHP/mensajes.php",
        {numFuncion: "8", pedidoId, folio, vendedor, cliente},
        function (response) {
            if (response.success) {
                //Mostrar mensaje de exito
                Swal.fire({
                    text: "El pedido fue rechazado",
                    icon: "success",
                }).then(() => {
                    $("#modalPedido").modal("hide"); //Cerra Modal
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
//Funcion para mostrar los datos de envio
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
