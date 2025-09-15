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
    data: { numFuncion: "2" },

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
        text: "No se pudo obtener el número de inventario.",
      });
    },
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
        const lote =
          $row.find(".lote input").val() || $row.find(".lote").text() || "—";
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
      const diffClass =
        diferencia < 0
          ? "text-danger"
          : diferencia > 0
          ? "text-success"
          : "text-muted";

      htmlResumen += `
        <tr class="table-subtotal fw-semibold text-end">
          <td></td>
          <td colspan="5" class="text-end">Subtotal producto:</td>
          <td>${conteo}</td>
          <td>${exist}</td>
          <td class="${diffClass}">${
        diferencia > 0 ? "+" : ""
      }${diferencia}</td>
        </tr>
        <!-- fila vacía para separación -->
        <tr><td colspan="9" style="background:transparent; border:0; height:15px;"></td></tr>
      `;
    });

    const diferenciaLinea = totalLinea - totalInventario;
    const diffLineaClass =
      diferenciaLinea < 0
        ? "text-danger"
        : diferenciaLinea > 0
        ? "text-success"
        : "text-muted";

    htmlResumen += `
        </tbody>
        <tfoot>
          <tr class="table-success fw-bold text-end">
            <td></td>
            <td colspan="5" class="text-end">TOTALES DE LA LÍNEA:</td>
            <td>${totalLinea}</td>
            <td>${totalInventario}</td>
            <td class="${diffLineaClass}">${
      diferenciaLinea > 0 ? "+" : ""
    }${diferenciaLinea}</td>
          </tr>
        </tfoot>
      </table>
    `;

    $("#resumenContenido").html(htmlResumen);

    const modal = new bootstrap.Modal(
      document.getElementById("resumenInventario")
    );
    modal.show();
  } catch (err) {
    console.error("Error en abrirModal:", err);
    Swal.fire("Error", "No se pudo generar el resumen.", "error");
  }
}

//Funcion para saber si hay un inventario activo
function buscarInventario() {
  const csrfToken = $("#csrf_token").val();
  const $noInv = $("#noInventario");
  const $linea = $("#lineaSelect");
  const $btnNext = $("#btnNext");

  // Estado inicial (opcional)
  $btnNext.prop("disabled", true);

  return $.ajax({
    url: "../Servidor/PHP/inventario.php",
    method: "GET",
    data: { numFuncion: "1" },
    dataType: "json",
    headers: { "X-CSRF-Token": csrfToken },
  })
    .done(function (res) {
      // Esperado: { success: true, foundActive: bool, existsAny: bool, docId: string|null, folioSiguiente: int|null }
      if (!res || res.success !== true) {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Respuesta inválida del servidor.",
        });
        return;
      }

      // Presentación/estado
      const { foundActive, existsAny, noInventario, docId } = res;

      if (foundActive) {
        // Hay inventario ACTIVO
        $noInv
          .val(noInventario ?? "")
          .prop("readonly", true)
          .addClass("is-valid");
        $linea.prop("disabled", false);
        $btnNext.prop("disabled", false);

        Swal.fire({
          icon: "info",
          title: "Inventario activo",
          html: `
          <div style="text-align:left">
            <div><b>Numero de Inventario:</b> ${noInventario ?? "—"}</div>
            <!--<div><b>Documento:</b> ${docId ?? "—"}</div>-->
            <div class="mt-2">Puedes continuar con el conteo por líneas.</div>
          </div>
        `,
          confirmButtonText: "Continuar",
        });
      } else if (existsAny) {
        // No hay activo, pero sí existen inventarios (inactivos)
        $noInv
          .val(noInventario ?? "")
          .prop("readonly", false)
          .removeClass("is-valid");
        $linea.prop("disabled", true);
        $btnNext.prop("disabled", true);

        Swal.fire({
          icon: "warning",
          title: "No hay inventario activo",
          html: `
          <div style="text-align:left">
            <div>Existen inventarios previos para esta empresa, pero ninguno activo.</div>
            <div class="mt-2">Define o activa un inventario para continuar.</div>
          </div>
        `,
          confirmButtonText: "Entendido",
        });
      } else {
        // No existe ningún inventario para esa empresa
        $noInv.val("").prop("readonly", false).removeClass("is-valid");
        $linea.prop("disabled", true);
        $btnNext.prop("disabled", true);

        Swal.fire({
          icon: "question",
          title: "Sin inventarios",
          text: "No existe ningún inventario registrado para esta empresa. Crea uno para continuar.",
          confirmButtonText: "Ok",
        });
      }
    })
    .fail(function (jqXHR, textStatus, errorThrown) {
      console.error("AJAX error:", textStatus, errorThrown);
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "No se pudo obtener el estado del inventario.",
      });
    });
}

async function initInventarioUI() {
  await buscarInventario(); // ya la hicimos antes
  //await loadLinesStatus();        // nueva
}

/*
function comparararConteos(claveLinea) {
  $.ajax({
    url: "../Servidor/PHP/inventarioFirestore.php?accion=compararLineas",
    method: "POST",
    contentType: "application/json",
    data: claveLinea,
    success: function (res) {
      if (res.success) {
        Swal.fire({
          icon: "success",
          title: finalizar ? "Línea finalizada" : "Autoguardado",
          text: res.message,
        }).then(() => {
          //
        });
      } else {
        Swal.fire(
          "Error",
          res.message || "No se pudo guardar la línea",
          "error"
        );
      }
    },
    error: function () {
      Swal.fire("Error", "Error de comunicación con el servidor", "error");
    },
  });
}
*/
///////////////////////////////////
function comparararConteos(claveLinea) {
  const noInv = $("#noInventario").val();
  if (!noInv || !claveLinea) {
    return Swal.fire({ icon: "warning", title: "Faltan datos para comparar" });
  }

  $.ajax({
    url: "../Servidor/PHP/inventarioFirestore.php",
    method: "GET",
    dataType: "json",
    data: {
      accion: "obtenerLineaConteos", // ← endpoint PHP sugerido abajo
      noInventario: noInv,
      claveLinea: claveLinea,
    },
  })
    .done(function (res) {
      if (!res || res.success !== true) {
        const msg = res?.message || "No fue posible obtener los conteos.";
        return Swal.fire({ icon: "info", title: "Sin datos", text: msg });
      }
      // res.conteo1 y res.conteo2 ya pueden venir normalizados; si no, normalizamos aquí
      const c1 = Array.isArray(res.conteo1)
        ? res.conteo1
        : normalizeDocToProducts(res.conteo1);
      const c2 = Array.isArray(res.conteo2)
        ? res.conteo2
        : normalizeDocToProducts(res.conteo2);

      const cmp = compareProducts(c1, c2); // {rows, iguales, difs, solo1, solo2}
      //console.log(cmp);
      // Render en un modal bonito
      const html = renderCompareTable(cmp, claveLinea);
      Swal.fire({
        width: Math.min(window.innerWidth - 40, 900),
        title: `Comparación de conteos — Línea ${claveLinea}`,
        html,
        confirmButtonText: "Cerrar",
      }).then(() => {
        if (cmp.rows.length == cmp.iguales) {
          compararSae(cmp, claveLinea);
        }
      });
    })
    .fail(function (err) {
      console.error("comparararConteos error:", err);
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "No fue posible comparar los conteos.",
      });
    });
}
function compararSae(cmp, claveLinea) {
  $.ajax({
    url: "../Servidor/PHP/inventarioFirestore.php",
    method: "GET",
    dataType: "json",
    data: {
      accion: "obtenerInventario", // ← endpoint PHP sugerido abajo
      articulos: cmp.rows,
    },
  })
    .done(function (res) {
      if (!res || res.success !== true) {
        const msg = res?.message || "No fue posible obtener los conteos.";
        return Swal.fire({ icon: "info", title: "Sin datos", text: msg });
      }
      //console.log(res);
      const html = tablaComparativaSae(cmp, claveLinea, res.data);
      Swal.fire({
        width: Math.min(window.innerWidth - 40, 900),
        title: `Comparación con SAE — Línea ${claveLinea}`,
        html,
        confirmButtonText: "Cerrar",
      });
    })
    .fail(function (err) {
      console.error("comparararConteos error:", err);
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "No fue posible comparar los conteos.",
      });
    });
}
// ---------- Helpers ----------
// Si el backend te devuelve el doc completo (formato Firestore REST), lo convertimos a:
// [{ cve_art, total, lotes:[{corrugados,corrugadosPorCaja,lote,total}]}]
function normalizeDocToProducts(doc) {
  if (!doc || !doc.fields) return [];
  const fields = doc.fields;

  const reservados = new Set([
    "idAsignado",
    "status",
    "updatedAt",
    "conteo",
    "subconteo",
    "lastProduct",
    "conteoTotal",
    "diferencia",
    "existSistema",
    "descr",
  ]);

  const out = [];
  Object.keys(fields).forEach((k) => {
    if (reservados.has(k)) return;
    const v = fields[k];
    if (!v || !v.arrayValue || !Array.isArray(v.arrayValue.values)) return;

    let suma = 0;
    const lotes = v.arrayValue.values.map((entry) => {
      const f =
        entry.mapValue && entry.mapValue.fields ? entry.mapValue.fields : {};
      const corr = parseInt(f.corrugados?.integerValue ?? 0, 10) || 0;
      const cxc = parseInt(f.corrugadosPorCaja?.integerValue ?? 0, 10) || 0;
      const lote = String(f.lote?.stringValue ?? "");
      const tot = parseInt(f.total?.integerValue ?? corr * cxc, 10) || 0;
      suma += tot;
      return { corrugados: corr, corrugadosPorCaja: cxc, lote, total: tot };
    });

    out.push({ cve_art: k, total: suma, lotes });
  });

  return out;
}
// Compara dos listas [{cve_art, total}] y devuelve estructura para pintar
function compareProducts(c1, c2) {
  // indexar por código
  const m1 = new Map(c1.map((x) => [String(x.cve_art), x]));
  const m2 = new Map(c2.map((x) => [String(x.cve_art), x]));
  const all = new Set([...m1.keys(), ...m2.keys()]);

  const rows = [];
  let iguales = 0,
    difs = 0,
    solo1 = 0,
    solo2 = 0;

  all.forEach((code) => {
    const a = m1.get(code);
    const b = m2.get(code);

    if (a && b) {
      const diff = (b.total || 0) - (a.total || 0);
      const equal = (a.total || 0) === (b.total || 0);
      if (equal) iguales++;
      else difs++;
      rows.push({
        cve_art: code,
        total1: a.total || 0,
        total2: b.total || 0,
        diff,
        status: equal ? "ok" : diff > 0 ? "mayor" : "menor",
      });
    } else if (a && !b) {
      solo1++;
      rows.push({
        cve_art: code,
        total1: a.total || 0,
        total2: 0,
        diff: -(a.total || 0),
        status: "solo1",
      });
    } else if (!a && b) {
      solo2++;
      rows.push({
        cve_art: code,
        total1: 0,
        total2: b.total || 0,
        diff: b.total || 0,
        status: "solo2",
      });
    }
  });

  // ordenar: diferencias primero, luego iguales
  rows.sort((r1, r2) => {
    const s = (r) => (r.status === "ok" ? 1 : 0);
    if (s(r1) !== s(r2)) return s(r1) - s(r2);
    // por magnitud de diff desc
    return Math.abs(r2.diff) - Math.abs(r1.diff);
  });

  return { rows, iguales, difs, solo1, solo2 };
}
// Render HTML de la tabla de comparación
function renderCompareTable(cmp, linea) {
  const stats = `
    <div class="mb-2">
      <span class="badge bg-success me-2">Iguales: ${cmp.iguales}</span>
      <span class="badge bg-warning text-dark me-2">Diferentes: ${cmp.difs}</span>
      <span class="badge bg-secondary me-2">Solo en C1: ${cmp.solo1}</span>
      <span class="badge bg-secondary">Solo en C2: ${cmp.solo2}</span>
    </div>
  `;

  const head = `
    <thead>
      <tr>
        <th style="white-space:nowrap">Código</th>
        <th>Conteo 1 </th>
        <th>Conteo 2 </th>
        <th>Diferencia</th>
      </tr>
    </thead>
  `;

  const body = `
    <tbody>
      ${cmp.rows
        .map(
          (r) => `
        <tr class="${rowClass(r)}">
          <td><code>${escapeHtml(r.cve_art)}</code></td>
          <td>${r.total1}</td>
          <td>${r.total2}</td>
          <td>${signed(r.diff)}</td>
        </tr>
      `
        )
        .join("")}
    </tbody>
  `;

  return `
    ${stats}
    <div class="table-responsive">
      <table class="table table-sm table-bordered align-middle">
        ${head}
        ${body}
      </table>
    </div>
    <small class="text-muted">
      * Los valores corresponden a la suma de <strong>total</strong> por producto (suma de lotes).
    </small>
  `;

  function rowClass(r) {
    if (r.status === "ok") return "table-success";
    if (r.status === "solo1" || r.status === "solo2") return "table-secondary";
    return "table-warning"; // diferentes
  }
  function signed(n) {
    return (n > 0 ? "+" : "") + n;
  }
}
function tablaComparativaSae(cmp, linea, saeList) {
  // saeList viene como array [{CVE_ART, DESCR, EXIST}, ...]
  // Indexamos por código para lookup O(1)
  const saeIndex = new Map(
    (Array.isArray(saeList) ? saeList : []).map((d) => [
      String(d.CVE_ART),
      Number(d.EXIST) || 0,
    ])
  );

  const stats = `
    <div class="mb-2">
      <span class="badge bg-success me-2">Iguales: ${cmp.iguales}</span>
      <span class="badge bg-warning text-dark me-2">Diferentes: ${cmp.difs}</span>
      <span class="badge bg-secondary me-2">Solo en C1: ${cmp.solo1}</span>
      <span class="badge bg-secondary">Solo en C2: ${cmp.solo2}</span>
    </div>
  `;

  const head = `
    <thead>
      <tr>
        <th style="white-space:nowrap">Código</th>
        <th>Conteo 1</th>
        <th>SAE</th>
        <th>Diferencia</th>
      </tr>
    </thead>
  `;

  const body = `
    <tbody>
      ${cmp.rows
        .map((r) => {
          const saeExist = saeIndex.get(String(r.cve_art)) ?? 0;
          const diff = (Number(r.total1) || 0) - saeExist;
          const status = diff === 0 ? "ok" : diff > 0 ? "mayor" : "menor";
          return `
          <tr class="${rowClass(status)}">
            <td><code>${escapeHtml(r.cve_art)}</code></td>
            <td>${Number(r.total1) || 0}</td>
            <td>${saeExist}</td>
            <td>${signed(diff)}</td>
          </tr>
        `;
        })
        .join("")}
    </tbody>
  `;

  return `
    ${stats}
    <div class="table-responsive">
      <table class="table table-sm table-bordered align-middle">
        ${head}
        ${body}
      </table>
    </div>
    <small class="text-muted">
      * Los valores corresponden a la suma de <strong>total</strong> por producto (suma de lotes).
    </small>
  `;

  function rowClass(status) {
    if (status === "ok") return "table-success";
    return "table-warning"; // diferentes
  }
  function signed(n) {
    return (n > 0 ? "+" : "") + n;
  }
  function escapeHtml(str) {
    return String(str ?? "").replace(
      /[&<>"']/g,
      (m) =>
        ({
          "&": "&amp;",
          "<": "&lt;",
          ">": "&gt;",
          '"': "&quot;",
          "'": "&#39;",
        }[m])
    );
  }
}
// utilidades pequeñas
function escapeHtml(str) {
  return String(str ?? "").replace(
    /[&<>"']/g,
    (m) =>
      ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;",
      }[m])
  );
}
//////////////////////////////////

$(document).ready(function () {
  initInventarioUI();
  //buscarInventario();
  //obtenerLineas(); // ← ya no, ahora usamos Firestore
  //bloquearLineasTerminadas();
  //noInventario();

  // Inventario activo
  $.get("../Servidor/PHP/inventarioFirestore.php", {
    accion: "obtenerInventarioActivo",
  }).done(function (res) {
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
  $.get("../Servidor/PHP/inventarioFirestore.php", {
    accion: "obtenerLineas",
  }).done(function (res) {
    if (res.success && res.lineas.length > 0) {
      const clavesAsignadas = res.lineas.map((l) => l.CVE_LIN);

      $.get("../Servidor/PHP/inventario.php", { numFuncion: "3" }).done(
        function (response) {
          const r =
            typeof response === "string" ? JSON.parse(response) : response;
          if (r.success) {
            const lineaSelect = $("#lineaSelect");
            lineaSelect.empty();
            lineaSelect.append(
              "<option selected disabled>Seleccione una línea</option>"
            );

            r.data.forEach((dato) => {
              const lineaAsignada = res.lineas.find(
                (l) => l.CVE_LIN === dato.CVE_LIN
              );
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
        }
      );
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
      reverseButtons: true,
    }).then((result) => {
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
      const lote =
        $row.find(".lote input").val() || $row.find(".lote").text() || "—";
      const corr = parseInt($row.find(".qty-input-corrugado").val()) || 0;
      const cajas = parseInt($row.find(".qty-input-cajas").val()) || 0;
      const piezas = corr * cajas;

      lotes.push({
        lote,
        corrugados: corr,
        corrugadosPorCaja: cajas,
        total: piezas,
      });
    });

    articulos[codigoArticulo] = lotes;
  });

  return { noInventario: noInv, claveLinea, articulos };
}

// Envía datos al backend (autoguardado/finalizar)
function guardarLinea(finalizar = false) {
  const payload = recolectarLinea();
  payload.status = finalizar ? false : true;
  payload.conteo = document.getElementById("conteoInput").value;

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
          text: res.message,
        }).then(() => {
          const modal = new bootstrap.Modal(
            document.getElementById("resumenInventario")
          );
          modal.hide();
          //comparararConteos(res.claveLinea);
        });
      } else {
        Swal.fire(
          "Error",
          res.message || "No se pudo guardar la línea",
          "error"
        );
      }
    },
    error: function () {
      Swal.fire("Error", "Error de comunicación con el servidor", "error");
    },
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
/*$("#finalizarInventarioLinea").click(function () {
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
});*/
