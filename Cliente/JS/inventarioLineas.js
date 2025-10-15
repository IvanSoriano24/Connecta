//lineaSelect
function obtenerLineas() {
  $.ajax({
    url: "../Servidor/PHP/inventario.php",
    method: "GET",
    data: {
      numFuncion: "3",
    },
    success: function (response) {
      console.log("obtenerLineas: response: ", response);
      try {
        const res =
          typeof response === "string" ? JSON.parse(response) : response;

        if (res.success && Array.isArray(res.data)) {
          const lineaSelect = $("#lineaSelect");
          lineaSelect.empty();
          lineaSelect.append(
            "<option selected disabled>Seleccione una linea</option>"
          );
          console.log("Data: ", res.data);
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
    const claveLinea = window.claveLinea; // ya definida en tu código
    const idInventario = window.idInventario;
    const subconteo = parseInt(window.subConteo) || 10;

    console.log(
      "claveLinea: ",
      claveLinea,
      " idInventario: ",
      idInventario,
      " subconteo: ",
      subconteo
    );

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

    // Verificar si está finalizada en Firestore
    verificarLineaFinalizada(idInventario, claveLinea, subconteo);

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
  mostrarLoader();
  const csrfToken = $("#csrf_token").val();
  const $noInv = $("#noInventario");
  const $linea = $("#lineaSelect");
  const $btnNext = $("#btnNext");

  // Estado inicial (opcional)
  //$btnNext.prop("disabled", true);

  return $.ajax({
    url: "../Servidor/PHP/inventario.php",
    method: "GET",
    data: { numFuncion: "1" },
    dataType: "json",
    headers: { "X-CSRF-Token": csrfToken },
  })
    .done(async function (res) {
      // Esperado: { success: true, foundActive: bool, existsAny: bool, docId: string|null, folioSiguiente: int|null }
      console.log("inventario?", res);
      window.idInventario = res.docId;
      console.log("ID guardado en window.idInventario:", window.idInventario);

      if (!res || res.success !== true) {
        cerrarLoader();
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Respuesta inválida del servidor.",
        });
        return;
      }

      // Presentación/estado
      const { foundActive, existsAny, noInventario, docId } = res;

      cerrarLoader();
      if (foundActive) {
        // Hay inventario ACTIVO
        $noInv
          .val(noInventario ?? "")
          .prop("readonly", true)
          .addClass("is-valid");
        $linea.prop("disabled", false);
        //$btnNext.prop("disabled", false);

        /*
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
        }); */
      } else if (existsAny) {
        // No hay activo, pero sí existen inventarios (inactivos)
        $noInv
          .val(noInventario ?? "")
          .prop("readonly", false)
          .removeClass("is-valid");
        $linea.prop("disabled", true);
        $btnNext.prop("disabled", true);

        await Swal.fire({
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
        window.location.href = "inventarioFisico.php";
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

///////////////////////////////////
/*function comparararConteos(tipoUsuario, subconteo, conteoLinea) {
  if (tipoUsuario === "SUPER-ALMACENISTA") {
    const noInv = $("#noInventario").val();
    const claveLinea = $("#lineaSelect").val();
    if (!noInv || !claveLinea) {
      return Swal.fire({
        icon: "warning",
        title: "Faltan datos para comparar",
      });
    }
    $.ajax({
      url: "../Servidor/PHP/inventarioFirestore.php",
      method: "GET",
      dataType: "json",
      data: {
        accion: "obtenerLineaConteos",
        noInventario: noInv,
        claveLinea: claveLinea,
      },
    })
      .done(function (res) {
        if (!res || res.success !== true) {
          const msg = res?.message || "No fue posible obtener los conteos.";
          return Swal.fire({ icon: "info", title: "Sin datos", text: msg });
        }
        const c1 = Array.isArray(res.conteo1)
          ? res.conteo1
          : normalizeDocToProducts(res.conteo1);
        const c2 = Array.isArray(res.conteo2)
          ? res.conteo2
          : normalizeDocToProducts(res.conteo2);

        const cmp = compareProducts(c1, c2);

        // ⭐ Nombres de usuarios (con fallback al id si no hay nombre)
        const u1 = res.user1?.name || res.user1?.id || "Conteo 1";
        const u2 = res.user2?.name || res.user2?.id || "Conteo 2";

        const html = renderCompareTable(cmp, claveLinea, {
          user1: u1,
          user2: u2,
        }); // ⭐ pasa nombres
        Swal.fire({
          width: Math.min(window.innerWidth - 40, 900),
          title: `Comparación de conteos — Línea ${claveLinea}`,
          html,
          confirmButtonText: "Cerrar",
        }).then(() => {
          if (cmp.rows.length == cmp.iguales) {
            compararSae(cmp, claveLinea);
          } else {
            Swal.fire({
              title: "Comparación SAE",
              html: "<strong>Conteos diferentes</strong><br>No es posible compararlo con SAE.",
              icon: "warning",
              confirmButtonText: "Aceptar",
              showCloseButton: true,
              allowOutsideClick: false,
              backdrop: true,
              timerProgressBar: false,
            });
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
}*/

function comparararConteos(tipoUsuario) {
  if (tipoUsuario !== "SUPER-ALMACENISTA") return;

  const noInv = $("#noInventario").val();
  const claveLinea = $("#lineaSelect").val();
  const conteo = $("#conteoInput").val();
  if (!noInv || !claveLinea) {
    return Swal.fire({ icon: "warning", title: "Faltan datos para comparar" });
  }

  mostrarLoader();
  $.ajax({
    url: "../Servidor/PHP/inventarioFirestore.php",
    method: "GET",
    dataType: "json",
    data: {
      accion: "obtenerLineaConteos",
      noInventario: noInv,
      claveLinea,
      conteo: conteo,
    },
  })
    .done(function (res) {
      cerrarLoader();
      if (!res || res.success !== true) {
        const msg = res?.message || "No fue posible obtener los conteos.";
        return Swal.fire({ icon: "info", title: "Sin datos", text: msg });
      }

      const p1 = res.conteo1 ? normalizeDocToProducts(res.conteo1) : null;
      const p2 = res.conteo2 ? normalizeDocToProducts(res.conteo2) : null;

      // A) No hay ningún conteo
      if (!p1 && !p2) {
        return Swal.fire({
          icon: "info",
          title: "Sin datos",
          text: "No hay conteos para esta línea.",
        });
      }

      // B) Solo un conteo → usa tu compararSae(cmp, claveLinea)
      if ((p1 && !p2) || (!p1 && p2)) {
        const unico = p1 || p2;

        // construimos un cmp “mínimo” con las filas del conteo único
        const cmpMin = {
          rows: unico.map((it) => ({
            cve_art: String(it.cve_art),
            // estos campos serán reemplazados/contrastados por la lógica del backend/tablaComparativaSae con SAE
            total1: Number(it.total) || 0,
            total2: 0,
            diff: 0,
            status: "ok",
          })),
          iguales: 0,
          difs: 0,
          solo1: 0,
          solo2: 0,
        };

        // ✅ Reutiliza tu función existente
        return compararSae(cmpMin, claveLinea);
      }

      // C) Hay dos conteos → comparas entre sí como ya hacías
      const c1 = Array.isArray(res.conteo1) ? res.conteo1 : p1;
      const c2 = Array.isArray(res.conteo2) ? res.conteo2 : p2;
      const cmp = compareProducts(c1, c2);

      // 🔹 Ordenar por código o nombre del producto (por ejemplo cve_art)
      cmp.rows.sort((a, b) => a.cve_art.localeCompare(b.cve_art));


      const u1 = res.user1?.name || res.user1?.id || "Conteo 1";
      const u2 = res.user2?.name || res.user2?.id || "Conteo 2";
      const html = renderCompareTable(cmp, claveLinea, {
        user1: u1,
        user2: u2,
      });

      Swal.fire({
        width: Math.min(window.innerWidth - 40, 900),
        title: `Comparación de conteos — Línea ${claveLinea}`,
        html,
        confirmButtonText: "Cerrar",
      }).then(() => {
        if (cmp.rows.length == cmp.iguales) {
          compararSae(cmp, claveLinea); // tu flujo actual
        } else {
          window.BanderaGeneracionConteoNuevo = true;
          Swal.fire({
            title: "Comparación SAE",
            html: "<strong>Conteos diferentes</strong><br>No es posible compararlo con SAE.",
            icon: "warning",
            confirmButtonText: "Aceptar",
            showCloseButton: true,
            allowOutsideClick: false,
            backdrop: true,
            timerProgressBar: false,
          }).then(async () => {
            const idInventario = window.idInventario;
            const conteo = document.getElementById("conteoInput").value;

            // 🔹 Verificar si el conteo actual sigue siendo el mismo ANTES de mostrar loader
            const resInv = await fetch(`../Servidor/PHP/inventarioFirestore.php?accion=obtenerConteoActual&idInventario=${idInventario}`);
            const docInv = await resInv.json();
            const conteoActual = Number(docInv?.conteo || 0);

            // 🚫 Si el conteo ya no es el actual → salir sin mostrar loader ni generar nada
            if (conteoActual !== Number(conteo)) {
              console.log(
                  `⏭ Conteo ${conteo} no es el actual (${conteoActual}), se omite generación.`
              );
              return;
            }

            // ✅ Solo si sigue siendo el actual → continuar flujo normal
            mostrarLoader();

            if (window.BanderaGeneracionConteoNuevo) {
              // Llamar al backend para verificar y generar conteos
              $.post(
                  "../Servidor/PHP/inventario.php",
                  {
                    numFuncion: "20",
                    idInventario: idInventario,
                    conteo: conteo,
                  },
                  async function (response) {
                    cerrarLoader();
                    console.log("Respuesta verificación inventario:", response);
                    if (response.success) {
                      window.finalizadoConteo = false;
                      await mostrarAlerta("Éxito", response.message, "success");
                    } else {
                      await mostrarAlerta(
                          "Aún hay líneas sin terminar",
                          response.message,
                          "info"
                      );
                    }
                  },
                  "json"
              ).fail(async (jqXHR, textStatus, errorThrown) => {
                cerrarLoader();
                await mostrarAlerta("Ocurrió un problema inesperado", "", "");
                console.error("Error AJAX:", textStatus, errorThrown);
                console.log("Respuesta cruda:", jqXHR.responseText);
                window.location.href = "inventarioFisico.php";
              });
            } else {
              console.log("Bandera: ", window.BanderaGeneracionConteoNuevo);
              cerrarLoader();
              window.finalizadoConteo = true;
              await mostrarAlerta(
                  "Éxito",
                  "Todo correcto, no se generó un nuevo conteo",
                  "success"
              );
            }
          });

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

      const html = tablaComparativaSae(cmp, claveLinea, res.data);
      Swal.fire({
        width: Math.min(window.innerWidth - 40, 900),
        title: `Comparación con SAE — Línea ${claveLinea}`,
        html,
        confirmButtonText: "Cerrar",
      }).then(async () => {
        const idInventario = window.idInventario;
        const conteo = document.getElementById("subconteoInput").value;

        if (window.BanderaGeneracionConteoNuevo) {
          // Llamar al backend para verificar y generar conteos
          $.post(
            "../Servidor/PHP/inventario.php",
            { numFuncion: "20", idInventario: idInventario, conteo: conteo },
            async function (response) {
              console.log("Respuesta verificación inventario:", response);
              if (response.success) {
                window.finalizadoConteo = false;
                await mostrarAlerta("Éxito", response.message, "success");
              } else {
                await mostrarAlerta(
                  "Aún hay líneas sin terminar",
                  response.message,
                  "info"
                );
              }
            },
            "json"
          ).fail(async (jqXHR, textStatus, errorThrown) => {
            await mostrarAlerta("Ocurrió un problema inesperado", "", "");
            console.error("Error AJAX:", textStatus, errorThrown);
            console.log("Respuesta cruda:", jqXHR.responseText);
          });
        } else {
          window.finalizadoConteo = true;
          await mostrarAlerta(
            "Éxito",
            "Todo correcto, no se generó un nuevo conteo",
            "success"
          );
        }
      });
    })
    .fail(function (err) {
      console.error("compararSae error:", err);
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "No fue posible comparar los conteos.",
      });
    });
}
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
function renderCompareTable(cmp, linea, opts = {}) {
  const u1 = opts.user1 || "Conteo 1";
  const u2 = opts.user2 || "Conteo 2";

  const stats = `
    <div class="mb-2">
      <span class="badge bg-success me-2">Iguales: ${cmp.iguales}</span>
      <span class="badge bg-warning text-dark me-2">Diferentes: ${
        cmp.difs
      }</span>
      <span class="badge bg-secondary me-2">Solo en ${escapeHtml(u1)}: ${
    cmp.solo1
  }</span>
      <span class="badge bg-secondary">Solo en ${escapeHtml(u2)}: ${
    cmp.solo2
  }</span>
    </div>
  `;

  const head = `
    <thead>
      <tr>
        <th style="white-space:nowrap">Código</th>
        <th>${escapeHtml(u1)}</th>
        <th>${escapeHtml(u2)}</th>
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
    return "table-warning";
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
          // true si son diferentes, false si son iguales
          window.BanderaGeneracionConteoNuevo = diff !== Number(r.total1);

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

async function subirPendientesDespuesDeLinea(claveLinea) {
  const files = window.MDPDFs?.getSelected?.() || [];
  if (!files.length) return null;

  const res = await subirPDFsLineas(files); // <- ya arma FormData con noInventario
  // Feedback al usuario:
  Swal.fire({
    icon: "success",
    title: "Archivos cargados",
    text: "Se adjuntaron correctamente a la línea.",
  });
  return res;
}

function subirPDFsLineas(selectedFiles, meta = {}) {
  const fd = new FormData();
  fd.append("numFuncion", 12);

  const noInventario =
    document.getElementById("noInventario")?.value ||
    document.querySelector('[name="noInventario"]')?.value ||
    window.noInventario ||
    "";

  if (!noInventario) throw new Error("Falta el noInventario en el front.");
  fd.append("noInventario", noInventario);

  // meta opcional
  if (meta.tipo) fd.append("tipo", String(meta.tipo)); // 'linea' | 'producto'
  if (meta.linea) fd.append("linea", String(meta.linea));
  if (meta.cve_art) fd.append("cve_art", String(meta.cve_art));

  const files = Array.from(selectedFiles || []);
  if (!files.length) throw new Error("No hay archivos seleccionados.");

  for (const f of files) fd.append("pdfs[]", f, f.name);

  return fetch("../Servidor/PHP/inventario.php", {
    method: "POST",
    body: fd,
  }).then(async (r) => {
    const text = await r.text();
    let data = null;
    try {
      data = text ? JSON.parse(text) : null;
    } catch {}
    if (!r.ok) {
      const msg =
        (data && (data.message || data.error || data.msg)) ||
        `HTTP ${r.status} ${r.statusText}`;
      throw new Error(
        msg + (text && !data ? ` · body: ${text.slice(0, 200)}` : "")
      );
    }
    if (data && (data.success === true || data.ok === true)) return data;
    throw new Error(
      (data && (data.message || data.error || data.msg)) ||
        "Respuesta JSON inesperada del servidor"
    );
  });
}

async function verificarLineaFinalizada(idInventario, claveLinea) {
  const conteo = document.getElementById("conteoInput").value;
  const subconteo = document.getElementById("subconteoInput").value;

  try {
    const res = await $.get("../Servidor/PHP/inventarioFirestore.php", {
      accion: "verificarLinea",
      idInventario: idInventario,
      claveLinea: claveLinea,
      conteo: conteo,
      subconteo: subconteo,
    });

    if (res.success && res.finalizada) {
      // Ya está finalizada → ocultar botón
      $("#finalizarInventarioLinea").hide();
    } else {
      $("#finalizarInventarioLinea").show();
    }
  } catch (e) {
    console.error("Error al verificar línea:", e);
    $("#finalizarInventarioLinea").show(); // fallback
  }
}

//////////////////////////////////
function cargarLineasDesdeFirestore() {
  $.get("../Servidor/PHP/inventarioFirestore.php", { accion: "obtenerLineas" })
    .done(function (resRaw) {
      const res = typeof resRaw === "string" ? JSON.parse(resRaw) : resRaw;
      console.log("Respuesta Firestore:", res);

      // Normalizar: priorizar res.lineas, si no existe usar res.data
      const lineasRaw = Array.isArray(res.lineas)
        ? res.lineas
        : Array.isArray(res.data)
        ? res.data
        : null;

      if (
        !res ||
        !res.success ||
        !Array.isArray(lineasRaw) ||
        lineasRaw.length === 0
      ) {
        Swal.fire({
          icon: "warning",
          title: "Aviso",
          text: "No se encontraron líneas en Firestore.",
        });
        return;
      }

      // Obtener datos del servidor SQL
      $.get("../Servidor/PHP/inventario.php", { numFuncion: "3" })
        .done(function (respRaw) {
          const r = typeof respRaw === "string" ? JSON.parse(respRaw) : respRaw;
          if (!r || !r.success || !Array.isArray(r.data)) {
            Swal.fire({
              icon: "warning",
              title: "Aviso",
              text: "No se encontraron líneas en el servidor.",
            });
            return;
          }

          const lineaSelect = $("#lineaSelect");
          //lineaSelect.off("change");
          lineaSelect.empty();
          lineaSelect.append(
            "<option selected disabled>Seleccione una línea</option>"
          );

          // Si lineasRaw es array de strings, crear objetos simples
          const fireLineas = lineasRaw.map((item) => {
            if (item && typeof item === "object") {
              return {
                CVE_LIN: String(item.CVE_LIN ?? item.value ?? ""),
                conteo: Number.isFinite(item.conteo) ? item.conteo : 0,
                subconteo: Number.isFinite(item.subconteo) ? item.subconteo : 0,
              };
            } else {
              const s = String(item);
              return { CVE_LIN: s, conteo: 0, subconteo: 0 };
            }
          });

          // Mapa rápido de r.data por CVE_LIN (string)
          // Normalizar y crear mapaR
          const mapaR = {};
          r.data.forEach((d) => {
            const rawKey = d.CVE_LIN ?? d.value ?? "";
            const key = String(rawKey).trim().toUpperCase();
            mapaR[key] = d;
          });

          const opcionesUnicas = new Set(); // mover fuera del bucle
          const grupos = {};

          fireLineas.forEach((fl) => {
            const claveNorm = String(fl.CVE_LIN).trim().toUpperCase();
            console.log("Data: ", fl, " claveNorm:", claveNorm);

            // Si quieres obligar a existir en r.data usa:
            // const datoServer = mapaR[claveNorm];
            // if (!datoServer) { console.warn("No existe en r.data:", claveNorm); return; }

            // Incluimos aunque no exista en r.data (fallback)
            const key = `${claveNorm}-${fl.conteo}-${fl.subconteo}`;
            if (opcionesUnicas.has(key)) return;
            opcionesUnicas.add(key);

            if (!grupos[fl.conteo]) grupos[fl.conteo] = {};
            if (!grupos[fl.conteo][fl.subconteo])
              grupos[fl.conteo][fl.subconteo] = [];

            grupos[fl.conteo][fl.subconteo].push({
              CVE_LIN: claveNorm,
              DESC_LIN:
                fl.DESC_LIN ??
                (mapaR[claveNorm] && mapaR[claveNorm].DESC_LIN) ??
                claveNorm,
              conteo: fl.conteo,
              subconteo: fl.subconteo,
            });
          });

          console.log("Grupos resultantes:", grupos);

          Object.keys(grupos)
            .map(Number)
            .sort((a, b) => a - b)
            .forEach((conteo) => {
              const optgroup = $(
                `<optgroup label="Conteo ${conteo}"></optgroup>`
              );
              Object.keys(grupos[conteo])
                .map(Number)
                .sort((a, b) => a - b)
                .forEach((sub) => {
                  grupos[conteo][sub].forEach((linea) => {
                    const v = escapeHtml(linea.CVE_LIN);
                    const desc = escapeHtml(linea.DESC_LIN);
                    // Aseguramos los atributos data-*
                    const $opt = $(
                      `<option value="${v}" data-conteo="${linea.conteo}" data-subconteo="${linea.subconteo}">Sub ${linea.subconteo} → ${desc}</option>`
                    );
                    optgroup.append($opt);
                  });
                });
              lineaSelect.append(optgroup);
            });

          // Bind del change (una sola vez) con lectura robusta de atributos
          lineaSelect.on("change", function () {
            const opt = $(this).find(":selected");
            console.log("opt seleccionado DOM:", opt.get(0)); // para inspección

            // Intentar con .data() y caer a .attr() si es necesario
            let conteoVal = opt.data("conteo");
            if (conteoVal === undefined) conteoVal = opt.attr("data-conteo");
            let subconteoVal = opt.data("subconteo");
            if (subconteoVal === undefined)
              subconteoVal = opt.attr("data-subconteo");

            // Normalizar a string/número según necesites
            if (conteoVal !== undefined && conteoVal !== null)
              conteoVal = String(conteoVal);
            if (subconteoVal !== undefined && subconteoVal !== null)
              subconteoVal = String(subconteoVal);

            console.log("conteoVal:", conteoVal, "subconteoVal:", subconteoVal);

            $("#conteoInput").val(conteoVal || "");
            $("#subconteoInput").val(subconteoVal || "");

            window.subConteo = subconteoVal || "";
            window.claveLinea = opt.val() || "";
          });
        })
        .fail(function () {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: "Error al obtener las líneas del servidor.",
          });
        });
    })
    .fail(function () {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Error al obtener las líneas desde Firestore.",
      });
    });
}
// Escapa texto para insertar en HTML
function escapeHtml(text) {
  return String(text)
    .replace(/&/g, "&amp;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;");
}

//////////////////////////////////
$(document).ready(function () {
  initInventarioUI();
  let noInventario = null;

  // Inventario activo
  $.get("../Servidor/PHP/inventarioFirestore.php", {
    accion: "obtenerInventarioActivo",
  }).done(function (res) {
    if (res.success) {
      console.log("Inventario Activo: ", res);
      $("#noInventario").val(res.noInventario);
      noInventario = res.noInventario;

      function toISODate(fecha) {
        if (!fecha) return "";
        // Si trae hora (YYYY-MM-DD HH:mm:ss), separar
        const partes = fecha.split(" ");
        const fechaSolo = partes[0]; // "2025-09-03"
        return fechaSolo; // ya está en formato ISO
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
  cargarLineasDesdeFirestore();
  // Lineas asignadas
  /*$.get("../Servidor/PHP/inventarioFirestore.php", {
    accion: "obtenerLineas",
  }).done(function (res) {
    console.log("obtenerLines res que hay: ", res)
    if (res.success && res.lineas.length > 0) {
      const clavesAsignadas = res.lineas.map((l) => l.CVE_LIN);
      console.log(
        "res obtener lineas: ",
        res,
        " clavesAsignadas: ",
        clavesAsignadas
      );

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

            // 🔹 Ordenar primero por subconteo, luego por CVE_LIN
            res.lineas.sort((a, b) => {
              if (a.subconteo !== b.subconteo) {
                return a.subconteo - b.subconteo;
              }
              return a.CVE_LIN.localeCompare(b.CVE_LIN);
            });

            // 🔹 Evitar duplicados
            const opcionesUnicas = new Set();

            // 🔹 Agrupar por conteo y subconteo
            const grupos = {};
            r.data.forEach((dato) => {
              const lineasAsignadas = res.lineas.filter(
                (l) => l.CVE_LIN === dato.CVE_LIN
              );

              lineasAsignadas.forEach((lineaAsignada) => {
                const key = `${dato.CVE_LIN}-${lineaAsignada.conteo}-${lineaAsignada.subconteo}`;
                if (!opcionesUnicas.has(key)) {
                  opcionesUnicas.add(key);

                  if (!grupos[lineaAsignada.conteo]) {
                    grupos[lineaAsignada.conteo] = {};
                  }
                  if (!grupos[lineaAsignada.conteo][lineaAsignada.subconteo]) {
                    grupos[lineaAsignada.conteo][lineaAsignada.subconteo] = [];
                  }

                  grupos[lineaAsignada.conteo][lineaAsignada.subconteo].push({
                    CVE_LIN: dato.CVE_LIN,
                    DESC_LIN: dato.DESC_LIN,
                    conteo: lineaAsignada.conteo,
                    subconteo: lineaAsignada.subconteo,
                  });
                }
              });
            });

            // 🔹 Pintar grupos en el select
            Object.keys(grupos)
              .sort((a, b) => a - b) // ordenar por conteo
              .forEach((conteo) => {
                const optgroupConteo = $(
                  `<optgroup label="Conteo ${conteo}"></optgroup>`
                );

                Object.keys(grupos[conteo])
                  .sort((a, b) => a - b) // ordenar por subconteo
                  .forEach((sub) => {
                    grupos[conteo][sub].forEach((linea) => {
                      optgroupConteo.append(
                        `<option value="${linea.CVE_LIN}"
                            data-conteo="${linea.conteo}"
                            data-subconteo="${linea.subconteo}">
                            Sub ${linea.subconteo} → ${linea.DESC_LIN}
                        </option>`
                      );
                    });
                  });

                lineaSelect.append(optgroupConteo);
              });

            // Cuando seleccionas una línea, se pintan conteo y subconteo
            lineaSelect.on("change", function () {
              const opt = $(this).find(":selected");

              $("#conteoInput").val(opt.data("conteo") || "");
              $("#subconteoInput").val(opt.data("subconteo") || "");

              // Variables globales
              window.subConteo = opt.data("subconteo");
              window.claveLinea = opt.val();
            });
          }
        }
      );
    }
  });*/

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

  // =============================== AUTOGUARDADO DE LINEA cada 5 minutos =====================================

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
      /*const corr = Number($row.find('.qty-input-corrugado').val()) || 0;
            const cajas = Number($row.find('.qty-input-cajas').val()) || 0;
            const sueltos = Number($row.find('.qty-input-sueltos').val()) || 0;
            const totales = Number($row.find('.qty-input-total').val()) || 0;
            const piezas = (corr * cajas) + sueltos;*/
      const corr = parseInt($row.find(".qty-input-corrugado").val()) || 0;
      const cajas = parseInt($row.find(".qty-input-cajas").val()) || 0;
      const sueltos = parseInt($row.find(".qty-input-sueltos").val()) || 0;
      const totales = parseInt($row.find(".qty-input-total").val()) || 0;
      const piezas = corr * cajas + sueltos;

      lotes.push({
        lote,
        corrugados: corr,
        corrugadosPorCaja: cajas,
        sueltos: sueltos,
        piezas: piezas,
        totales: totales,
      });
    });

    articulos[codigoArticulo] = lotes;
  });

  return { noInventario: noInv, claveLinea, articulos };
}

// Envía datos al backend (autoguardado/finalizar)
function guardarLinea(finalizar) {
  mostrarLoader("Guardando línea...");
  const payload = recolectarLinea();
  payload.subconteo = document.getElementById("subconteoInput").value;
  payload.conteo = document.getElementById("conteoInput").value;
  payload.status = !finalizar;

  payload.idInventario = window.idInventario;


  console.log("PAYLOAD: ", payload);

  $.ajax({
    url: "../Servidor/PHP/inventarioFirestore.php?accion=guardarLinea",
    method: "POST",
    contentType: "application/json",
    data: JSON.stringify(payload),

    success: async function (res) {
      cerrarLoader();
      if (res.success) {
        console.log("respuesta guardar linea: ", res);
        await Swal.fire({
          icon: "success",
          title: finalizar ? "Línea finalizada" : "Autoguardado",
          text: res.message,
        }).then(() => {
          const modalEl = document.getElementById("resumenInventario");
          const modal =
            bootstrap.Modal.getInstance(modalEl) ||
            new bootstrap.Modal(modalEl);
          modal.hide();

          try {
            const seleccionados = window.MDPDFs.getSelected();
            if (seleccionados && seleccionados.length > 0) {
              subirPDFsLineas(seleccionados, {
                tipo: "linea",
                linea: res.claveLinea,
              });
              window.MDPDFs.reset();
            }
          } catch (err) {
            console.warn("No se pudieron subir PDFs:", err.message);
          }

          window.location.reload();
        });
      } else {
        await Swal.fire(
          "Error",
          res.message || "No se pudo guardar la línea",
          "error"
        );
      }
    },
    error: async function () {
      cerrarLoader();
      await Swal.fire(
        "Error",
        "Error de comunicación con el servidor",
        "error"
      );
    },
  });
}

// Escuchar el clic en el boton
$("#btnNext").click(function () {
  abrirModal();
});

// =================== PDF =============================
// Convierte <img> en base64
function getBase64Image(imgElement) {
  if (!imgElement) return "";
  const canvas = document.createElement("canvas");
  canvas.width = imgElement.naturalWidth;
  canvas.height = imgElement.naturalHeight;
  const ctx = canvas.getContext("2d");
  ctx.drawImage(imgElement, 0, 0);
  return canvas.toDataURL("image/png");
}

// Arma datos para PDF
function prepararDatosPDF() {
  const payload = recolectarLinea();
  const datos = [];

  Object.entries(payload.articulos).forEach(([clave, lotes]) => {
    const card = $(`#articulos .article-card[data-articulo='${clave}']`);
    const nombre = card.find(".name").text() || "SIN NOMBRE";
    const exist = parseInt(card.data("exist")) || 0;

    datos.push({
      clave: clave,
      articulo: nombre,
      sae: exist,
      lotes: lotes, // mandar lotes completos
    });
  });

  return {
    noInventario: payload.noInventario,
    claveLinea: payload.claveLinea,
    fechaInicio: $("#fechaInicio").val() || "",
    fechaFin: $("#fechaFin").val() || "",
    usuario: $("#usuarioActual").text() || "—",
    datos: datos,
  };
}

// Generar PDF
function generarPDFInventario(datos) {
  const logoElement = document.getElementById("logoInventario");
  const logoBase64 = getBase64Image(logoElement);

  $.ajax({
    url: "../Servidor/PHP/reporteInventario.php",
    type: "POST",
    data: {
      datos: JSON.stringify(datos),
      logo: logoBase64,
    },
    xhrFields: { responseType: "blob" },
    success: function (data) {
      const blob = new Blob([data], { type: "application/pdf" });
      const link = document.createElement("a");
      link.href = URL.createObjectURL(blob);
      link.download =
        "Inventario_" + datos.noInventario + "_" + datos.claveLinea + ".pdf";
      link.click();
    },
    error: function () {
      Swal.fire("Error", "No se pudo generar el PDF", "error");
    },
  });
}

$("#generarPDF").click(function () {
  const datos = prepararDatosPDF();
  generarPDFInventario(datos);
});

// ============== EXCEL =====================
// Arma datos para Excel
function prepararDatosExcel() {
  const payload = recolectarLinea();
  const datos = [];
  let totalGeneral = 0;

  Object.entries(payload.articulos).forEach(([clave, lotes], indexClave) => {
    // Si no es la primera clave → insertar fila en blanco como separación
    if (indexClave > 0) {
      datos.push({ Clave: "", Lote: "", Cantidad: "" });
    }

    lotes.forEach((lote, indexLote) => {
      const cantidad = lote.piezas || 0;
      datos.push({
        Clave: indexLote === 0 ? clave : "", // solo la primera fila de la clave lleva clave
        Lote: lote.lote || "—",
        Cantidad: cantidad,
      });
      totalGeneral += cantidad;
    });
  });

  // Fila vacía + total
  datos.push({ Clave: "", Lote: "", Cantidad: "" });
  datos.push({ Clave: "", Lote: "TOTAL", Cantidad: totalGeneral });

  return datos;
}

async function generarExcelInventario(datos) {
  // Insertar fila vacía antes del TOTAL
  const total = datos.pop(); // quitamos el total original
  datos.push({ Clave: "", Lote: "", Cantidad: "" }); // fila vacía
  datos.push({
    Clave: "", // nada en clave
    Lote: "TOTAL", // total en columna Lote
    Cantidad: total.Cantidad, // número en Cantidad
  });

  // Crear libro y hoja
  const workbook = new ExcelJS.Workbook();
  const worksheet = workbook.addWorksheet("Inventario");

  // Definir columnas con anchos
  worksheet.columns = [
    { header: "Clave", key: "Clave", width: 30 },
    { header: "Lote", key: "Lote", width: 18 },
    { header: "Cantidad", key: "Cantidad", width: 12 },
  ];

  // Agregar filas
  datos.forEach((row) => {
    const newRow = worksheet.addRow(row);

    // Forzar alineación de la celda Lote a la derecha
    newRow.getCell("Lote").alignment = { horizontal: "right" };
  });

  // === Estilos ===
  // Encabezados
  worksheet.getRow(1).eachCell((cell) => {
    cell.font = { bold: true, color: { argb: "FFFFFFFF" } };
    cell.fill = {
      type: "pattern",
      pattern: "solid",
      fgColor: { argb: "3f317d" }, // morado
    };
    cell.alignment = { horizontal: "center", vertical: "middle" };
  });

  // Fila TOTAL (última)
  const totalRowIndex = worksheet.rowCount;
  const cellTotalText = worksheet.getCell("B" + totalRowIndex); // columna Lote
  const cellTotalValue = worksheet.getCell("C" + totalRowIndex); // columna Cantidad

  // Celda "TOTAL"
  cellTotalText.font = { bold: true, color: { argb: "FFFFFFFF" } };
  cellTotalText.fill = {
    type: "pattern",
    pattern: "solid",
    fgColor: { argb: "3f317d" }, // lila/morado
  };
  cellTotalText.alignment = { horizontal: "center" };

  // Celda valor del total
  cellTotalValue.font = { bold: true, color: { argb: "FFFFFFFF" } };
  cellTotalValue.fill = {
    type: "pattern",
    pattern: "solid",
    fgColor: { argb: "FF2ECC71" }, // verde
  };
  cellTotalValue.alignment = { horizontal: "right" };

  // Exportar archivo
  const buffer = await workbook.xlsx.writeBuffer();
  const blob = new Blob([buffer], {
    type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
  });

  // Obtener texto del option seleccionado
  let lineaDesc = $("#lineaSelect option:selected").text() || "linea";

  // Limpiar espacios en blanco al inicio y fin
  lineaDesc = lineaDesc.trim();

  // Quitar el "Sub X → " si existe
  lineaDesc = lineaDesc.replace(/^Sub\s+\d+\s+→\s+/, "");

  // Reemplazar espacios múltiples por uno solo
  lineaDesc = lineaDesc.replace(/\s+/g, " ");

  // Opcional: quitar acentos y reemplazar espacios por "_"
  const lineaFile = lineaDesc
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "") // quita acentos
    .replace(/\s+/g, "_"); // espacios → _

  // Fecha actual YYYY-MM-DD
  const hoy = new Date();
  const fecha = hoy.toISOString().split("T")[0]; // corta en "T", queda solo yyyy-mm-dd

  // Exportar con nombre: Inventario_LINEA_2025-09-30.xlsx
  saveAs(blob, `Inventario - ${lineaFile} - ${fecha}.xlsx`);
}

$("#exportarExcel").click(function () {
  const datos = prepararDatosExcel();
  generarExcelInventario(datos);
});
