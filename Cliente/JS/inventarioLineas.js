//lineaSelect
function obtenerLineas() {
    $.ajax({
        url: "../Servidor/PHP/inventario.php",
        method: "GET",
        data: {
            numFuncion: "3",
        },
        success: function (response) {
            try {
                const res =
                    typeof response === "string" ? JSON.parse(response) : response;

                if (res.success && Array.isArray(res.data)) {
                    const lineaSelect = $("#lineaSelect");
                    lineaSelect.empty();
                    lineaSelect.append(
                        "<option selected disabled>Seleccione una linea</option>"
                    );

                    res.data.forEach((dato) => {
                        lineaSelect.append(
                            `<option value="${dato.CVE_LIN}" data-id="${dato.CVE_LIN}" data-descripcion="${dato.DESC_LIN}">
                ${dato.DESC_LIN}
              </option>`
                        );
                    });

                    // Habilitar el select si hay vendedores disponibles
                    //lineaSelect.prop("disabled", res.data.length === 0);
                } else {
                    /*Swal.fire({
                      icon: "warning",
                      title: "Aviso",
                      text: res.message || "No se Encontraron Datos de Envio.",
                    });*/
                    //$("#lineaSelect").prop("disabled", true);
                }
            } catch (error) {
                console.error("Error al Procesar la Respuesta:", error);
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Error al Cargar las Lineas.",
                });
            }
        },
        error: function () {
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "Error al Obtener las Lineas.",
            });
        },
    });
}

/*function cargarProductos(){
    const filtroLinea = $("#lineaSelect").val(); // Obtener el filtro seleccionado
}*/


function noInventario() {
    $.ajax({
        url: "../Servidor/PHP/inventario.php",
        method: "GET",
        data: {numFuncion: "2"},

        // 1) Indica que esperas JSON
        dataType: "json",

        // 2) Coloca aquí el callback con la clave `success`
        success: function (data) {
            console.log("Respuesta recibida:", data);
            if (data.success) {
                document.getElementById("noInventario").value = data.noInventario;
            } else {
                console.error("Error del servidor:", data.message);
            }
        },

        // Error de comunicación
        error: function (jqXHR, textStatus, errorThrown) {
            console.error("AJAX error:", textStatus, errorThrown);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "No se pudo obtener el número de inventario."
            });
        }
    });
}

function abrirModal() {
    try {
        const lineaTexto = $("#lineaSelect option:selected").text() || "—";
        $("#lineaSeleccionada").text(`Línea seleccionada: ${lineaTexto}`);

        let htmlResumen = `
      <table class="table table-striped align-middle">
        <thead class="table-light">
          <tr>
            <th>Clave</th>
            <th>Artículo</th>
            <th>Lote</th>
            <th class="text-end">Corrugados</th>
            <th class="text-end">Cajas</th>
            <th class="text-end">Total piezas</th>
            <th class="text-end">Suma total lotes</th>
            <th class="text-end">Inventario SAE</th>
            <th class="text-end">Diferencia</th>
          </tr>
        </thead>
        <tbody>
    `;

        let totalLinea = 0;
        let totalInventario = 0;

        $("#articulos .article-card").each(function () {
            const $card = $(this);
            let codigo = $card.data("articulo") || "—";
            let nombre = $card.find(".name").text() || "—";
            const exist = parseInt($card.data("exist")) || 0;
            const conteo = parseInt($card.find(".article-total").text()) || 0;
            const diferencia = conteo - exist;

            totalLinea += conteo;
            totalInventario += exist;

            const lotes = $card.find(".row-line");
            let primeraFila = true;

            // filas de lotes
            lotes.each(function () {
                const $row = $(this);
                const lote = $row.find(".lote input").val() || $row.find(".lote").text() || "—";
                const corr = parseInt($row.find(".qty-input-corrugado").val()) || 0;
                const cajas = parseInt($row.find(".qty-input-cajas").val()) || 0;
                const piezas = corr * cajas;

                htmlResumen += `
          <tr>
            <td>${primeraFila ? codigo : ""}</td>
            <td>${primeraFila ? nombre : ""}</td>
            <td>${lote}</td>
            <td class="text-end">${corr}</td>
            <td class="text-end">${cajas}</td>
            <td class="text-end">${piezas}</td>
            <td></td>
            <td></td>
            <td></td>
          </tr>
        `;
                primeraFila = false;
            });

            // subtotal del producto con color diferente
            const diffClass = diferencia < 0 ? "text-danger" : (diferencia > 0 ? "text-success" : "text-muted");

            htmlResumen += `
        <tr class="table-subtotal fw-semibold text-end">
          <td></td>
          <td colspan="5" class="text-end">Subtotal producto:</td>
          <td>${conteo}</td>
          <td>${exist}</td>
          <td class="${diffClass}">${diferencia > 0 ? '+' : ''}${diferencia}</td>
        </tr>
        <!-- fila vacía para separación -->
        <tr><td colspan="9" style="background:transparent; border:0; height:15px;"></td></tr>
      `;
        });

        const diferenciaLinea = totalLinea - totalInventario;
        const diffLineaClass = diferenciaLinea < 0 ? "text-danger" : (diferenciaLinea > 0 ? "text-success" : "text-muted");

        htmlResumen += `
        </tbody>
        <tfoot>
          <tr class="table-success fw-bold text-end">
            <td></td>
            <td colspan="5" class="text-end">TOTALES DE LA LÍNEA:</td>
            <td>${totalLinea}</td>
            <td>${totalInventario}</td>
            <td class="${diffLineaClass}">${diferenciaLinea > 0 ? '+' : ''}${diferenciaLinea}</td>
          </tr>
        </tfoot>
      </table>
    `;

        $("#resumenContenido").html(htmlResumen);

        const modal = new bootstrap.Modal(document.getElementById('resumenInventario'));
        modal.show();

    } catch (err) {
        console.error("Error en abrirModal:", err);
        Swal.fire("Error", "No se pudo generar el resumen.", "error");
    }
}

//Funcion para saber si hay un inventario activo
function buscarInventario() {
    const csrfToken = $('#csrf_token').val();
    const $noInv = $('#noInventario');
    const $linea = $('#lineaSelect');
    const $btnNext = $('#btnNext');

    // Estado inicial (opcional)
    $btnNext.prop('disabled', true);

    return $.ajax({
        url: "../Servidor/PHP/inventario.php",
        method: "GET",
        data: {numFuncion: "1"},
        dataType: "json",
        headers: {'X-CSRF-Token': csrfToken}
    })
        .done(function (res) {
            // Esperado: { success: true, foundActive: bool, existsAny: bool, docId: string|null, folioSiguiente: int|null }
            if (!res || res.success !== true) {
                Swal.fire({icon: "error", title: "Error", text: "Respuesta inválida del servidor."});
                return;
            }

            // Presentación/estado
            const {foundActive, existsAny, noInventario, docId} = res;

            if (foundActive) {
                // Hay inventario ACTIVO
                $noInv.val(noInventario ?? '').prop('readonly', true).addClass('is-valid');
                $linea.prop('disabled', false);
                $btnNext.prop('disabled', false);

                Swal.fire({
                    icon: "info",
                    title: "Inventario activo",
                    html: `
          <div style="text-align:left">
            <div><b>Numero de Inventario:</b> ${noInventario ?? '—'}</div>
            <!--<div><b>Documento:</b> ${docId ?? '—'}</div>-->
            <div class="mt-2">Puedes continuar con el conteo por líneas.</div>
          </div>
        `,
                    confirmButtonText: "Continuar"
                });
            } else if (existsAny) {
                // No hay activo, pero sí existen inventarios (inactivos)
                $noInv.val(noInventario ?? '').prop('readonly', false).removeClass('is-valid');
                $linea.prop('disabled', true);
                $btnNext.prop('disabled', true);

                Swal.fire({
                    icon: "warning",
                    title: "No hay inventario activo",
                    html: `
          <div style="text-align:left">
            <div>Existen inventarios previos para esta empresa, pero ninguno activo.</div>
            <div class="mt-2">Define o activa un inventario para continuar.</div>
          </div>
        `,
                    confirmButtonText: "Entendido"
                });
            } else {
                // No existe ningún inventario para esa empresa
                $noInv.val('').prop('readonly', false).removeClass('is-valid');
                $linea.prop('disabled', true);
                $btnNext.prop('disabled', true);

                Swal.fire({
                    icon: "question",
                    title: "Sin inventarios",
                    text: "No existe ningún inventario registrado para esta empresa. Crea uno para continuar.",
                    confirmButtonText: "Ok"
                });
            }
        })
        .fail(function (jqXHR, textStatus, errorThrown) {
            console.error("AJAX error:", textStatus, errorThrown);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "No se pudo obtener el estado del inventario."
            });
        });
}

async function initInventarioUI() {
    await buscarInventario();       // ya la hicimos antes
    //await loadLinesStatus();        // nueva
}


$(document).ready(function () {
    initInventarioUI();
    //buscarInventario();
    //obtenerLineas(); // ← ya no, ahora usamos Firestore
    //bloquearLineasTerminadas();
    //noInventario();

    // Inventario activo
    $.get("../Servidor/PHP/inventarioFirestore.php", {accion: "obtenerInventarioActivo"})
        .done(function (res) {
            if (res.success) {
                $("#noInventario").val(res.noInventario);

                function toISODate(fechaDDMMYYYY) {
                    if (!fechaDDMMYYYY) return "";
                    const partes = fechaDDMMYYYY.split("/");
                    if (partes.length !== 3) return "";
                    const [dd, mm, yyyy] = partes;
                    return `${yyyy}-${mm}-${dd}`; // → 2025-08-26
                }

                // Ejemplo en la llamada AJAX
                if (res.fechaInicio) {
                    $("#fechaInicio").val(toISODate(res.fechaInicio));
                }
                if (res.fechaFin) {
                    $("#fechaFin").val(toISODate(res.fechaFin));
                } else {
                    $("#fechaFinContainer").hide(); // ocultar si no existe
                }
            }
        });

// Lineas asignadas
    $.get("../Servidor/PHP/inventarioFirestore.php", {accion: "obtenerLineas"})
        .done(function (res) {
            if (res.success && res.lineas.length > 0) {
                const clavesAsignadas = res.lineas.map(l => l.CVE_LIN);

                $.get("../Servidor/PHP/inventario.php", {numFuncion: "3"})
                    .done(function (response) {
                        const r = typeof response === "string" ? JSON.parse(response) : response;
                        if (r.success) {
                            const lineaSelect = $("#lineaSelect");
                            lineaSelect.empty();
                            lineaSelect.append("<option selected disabled>Seleccione una línea</option>");

                            r.data.forEach(dato => {
                                const lineaAsignada = res.lineas.find(l => l.CVE_LIN === dato.CVE_LIN);
                                if (lineaAsignada) {
                                    lineaSelect.append(
                                        `<option value="${dato.CVE_LIN}"
                                               data-conteo="${lineaAsignada.conteo}"
                                               data-subconteo="${lineaAsignada.subconteo}">
                                         ${dato.DESC_LIN} (Conteo ${lineaAsignada.conteo})
                                       </option>`
                                    );

                                }
                            });

                            // Cuando seleccionas una línea, se pintan conteo y subconteo
                            lineaSelect.on("change", function () {
                                const opt = $(this).find(":selected");
                                $("#conteoInput").val(opt.data("conteo") || "");
                                $("#subconteoInput").val(opt.data("subconteo") || "");
                            });
                        }
                    });
            }
        });


    // === BOTÓN FINALIZAR INVENTARIO DE LÍNEA ===
    $("#finalizarInventarioLinea").click(function () {
        Swal.fire({
            title: "¿Estás seguro?",
            text: "Si guardas esta línea ya no podrás editarla después.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Sí, guardar",
            cancelButtonText: "Cancelar",
            reverseButtons: true
        }).then(result => {
            if (result.isConfirmed) {
                guardarLinea(true); // Guardar y bloquear edición
            }
        });
    });

    // === AUTOGUARDADO cada 5 minutos ===

    /*
    setInterval(() => {
        console.log("Autoguardado...");
        guardarLinea(false);
    }, 5 * 60 * 1000); */
});


// ======================= FUNCIONES =======================

// Recolecta datos de todos los artículos y lotes
function recolectarLinea() {
    const claveLinea = $("#lineaSelect").val();
    const noInv = $("#noInventario").val();
    const articulos = {};

    $("#articulos .article-card").each(function () {
        const $card = $(this);
        const codigoArticulo = $card.data("articulo") || "SIN-CODIGO";

        const lotes = [];
        $card.find(".row-line").each(function () {
            const $row = $(this);
            const lote = $row.find(".lote input").val() || $row.find(".lote").text() || "—";
            const corr = parseInt($row.find(".qty-input-corrugado").val()) || 0;
            const cajas = parseInt($row.find(".qty-input-cajas").val()) || 0;
            const piezas = corr * cajas;

            lotes.push({
                lote,
                corrugados: corr,
                corrugadosPorCaja: cajas,
                total: piezas
            });
        });

        articulos[codigoArticulo] = lotes;
    });

    return {noInventario: noInv, claveLinea, articulos};
}

// Envía datos al backend (autoguardado/finalizar)
function guardarLinea(finalizar = false) {
    const payload = recolectarLinea();
    payload.status = finalizar ? false : true;

    $.ajax({
        url: "../Servidor/PHP/inventarioFirestore.php?accion=guardarLinea",
        method: "POST",
        contentType: "application/json",
        data: JSON.stringify(payload),
        success: function (res) {
            if (res.success) {
                Swal.fire({
                    icon: "success",
                    title: finalizar ? "Línea finalizada" : "Autoguardado",
                    text: res.message
                });
            } else {
                Swal.fire("Error", res.message || "No se pudo guardar la línea", "error");
            }
        },
        error: function () {
            Swal.fire("Error", "Error de comunicación con el servidor", "error");
        }
    });
}


// Escuchar el cambio en el filtro
/*$("#lineaSelect").change(function () {
  cargarProductos(); // Recargar las comandas con el filtro aplicado
});*/
// Escuchar el clic en el boton
$("#btnNext").click(function () {
    abrirModal();
});
$("#finalizarInventarioLinea").click(function () {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Si guardas esta línea ya no podrás editarla después.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, guardar",
        cancelButtonText: "Cancelar",
        reverseButtons: true
    }).then(result => {
        if (result.isConfirmed) {
            guardarLinea(true); // Guardar y bloquear edición
        }
    });
});


