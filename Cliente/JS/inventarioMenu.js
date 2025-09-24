// ====== Estado global ======
let asignByUser = {
  // userId: { userName, userHandle, lineas: { lineaId: { lineaDesc } } }
};
let lineIndex = {
  // lineaId: Set<userId>
};

async function mostrarInventarios() {
  $.ajax({
    url: "../Servidor/PHP/inventario.php",
    type: "POST",
    dataType: "json",
    data: { numFuncion: "7" },
  })
    .done(async (response) => {
      if (!response.success) {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: response.message || "No se pudieron cargar los inventarios",
        });
        return;
      }

      const inventarios = response.inventarios || [];
      const $tbody = $("#tablaInventarios").empty();

      if (inventarios.length === 0) {
        $tbody.append(
          `<tr><td colspan="4" class="text-center text-muted">No hay inventarios disponibles</td></tr>`
        );
        return;
      }

      // Pintado inicial
      inventarios.forEach((inv) => {
        const noInv = inv.noInventario ?? "-";
        const fecha = inv.fechaInicio ?? "-";
        const estado = inv.status ? "Activo" : "Finalizado";

        const $acciones = $(`<td class="acciones"></td>`);

        // Descargar (se mostrará tras comprobar archivos)
        const $btnDesc = $(
          `<button class="btn btn-sm btn-primary d-none me-2">Descargar</button>`
        ).on("click", () => descargarEvidencia(noInv));
        $acciones.append($btnDesc);

        // Asignar líneas (solo activos)
        if (inv.status === true) {
          const $btnAsign = $(
            `<button class="btn btn-sm btn-outline-secondary">Asignar líneas</button>`
          ).on("click", () => abrirModalAsignacion(noInv));
          $acciones.append($btnAsign);
        } else {
          //$acciones.append(`<span class="text-muted">Inventario cerrado</span>`);
        }

        const $fila = $(`
        <tr data-noinv="${noInv}">
          <td>${noInv}</td>
          <td>${fecha}</td>
          <td>${estado}</td>
        </tr>
      `);
        $fila.append($acciones);
        $tbody.append($fila);
      });

      // Comprobar archivos para mostrar/ocultar “Descargar”
      const checks = inventarios.map((i) => buscarArchivos(i.noInventario));
      const results = await Promise.allSettled(checks);
      results.forEach((r, idx) => {
        const noInv = inventarios[idx].noInventario;
        const $row = $tbody.find(`tr[data-noinv="${noInv}"]`);
        const $btnDesc = $row.find("button.btn.btn-sm.btn-primary");
        const hasFiles = r.status === "fulfilled" && r.value === true;
        if (hasFiles) $btnDesc.removeClass("d-none");
        else $btnDesc.remove();
      });
    })
    .fail(() => {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Error en la solicitud AJAX.",
      });
    });
}
// Comprueba si existe evidencia (PROMISE -> boolean)
function buscarArchivos(noInv) {
  return new Promise((resolve, reject) => {
    $.ajax({
      url: "../Servidor/PHP/descargarInventarios.php",
      type: "POST",
      dataType: "json",
      data: { numFuncion: "2", noInv },
    })
      .done((res) => resolve(!!(res && res.success)))
      .fail((jqXHR, textStatus) => {
        console.error("buscarArchivos AJAX error:", textStatus);
        resolve(false); // tratamos error como “sin archivos”
      });
  });
}
// Descarga el ZIP solo si el server devuelve URL
function descargarEvidencia(noInv) {
  // Ir directo al endpoint que streamea el ZIP
  const url =
    "../Servidor/PHP/descargarInventarios.php?numFuncion=1&noInv=" +
    encodeURIComponent(noInv);
  // nueva pestaña o misma, como prefieras:
  // window.open(url, "_blank");
  window.location = url;
}
///////////////////////////////////////////////////////////
function abrirModalAsignacion(noInv) {
  try {
    // Cerrar modal de inventarios
    // guarda el inventario actual en un hidden del modal
    // (agrégalo al modal si no existe)
     $("#modalInventarios").modal("hide");
    let $hidden = $('#asociarLineas input[type="hidden"][name="noInventario"]');
    if ($hidden.length === 0) {
      $("#asociarLineas .modal-body").prepend(
        '<input type="hidden" name="noInventario" id="noInventarioModal">'
      );
      $hidden = $("#noInventarioModal");
    }
    $hidden.val(noInv);

    // limpiar estructuras / UI
    asignByUser = {};
    lineIndex = {};
    $("#listaEmpresasAsociadas").html(
      '<li class="list-group-item text-muted">Sin asignaciones</li>'
    );
    $("#selectUsuario").val("");
    $("#lineaSelect").val("");

    // cargar selects (reusa tus funciones ya implementadas)
    obtenerLineas(); // llena #lineaSelect
    obtenerAlmacenistas();
    // obtenerAlmacenistas(...) -> si la tienes en JS; si viene de PHP, llama tu AJAX correspondiente
    // por ejemplo: obtenerAlmacenistas(); // llenar #selectUsuario

    // opcional: traer asignaciones existentes para no empezar vacío
    cargarAsignacionesExistentes(noInv).then(() => {
      // abrir modal
      const el = document.getElementById("asociarLineas");
      const modal = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
      modal.show();
      //$("#btnAsignar").off("click").on("click", asignarLinea);
    });
  } catch (e) {
    console.error(e);
    Swal.fire({
      icon: "error",
      title: "Error",
      text: "No se pudo abrir el modal.",
    });
  }
}
// (Opcional) trae lo que haya guardado en Firestore y puebla asignByUser/lineIndex
async function cargarAsignacionesExistentes(noInv) {
  try {
    const res = await $.ajax({
      url: "../Servidor/PHP/inventario.php",
      method: "GET",
      dataType: "json",
      data: { numFuncion: "11", noInventario: noInv }, // <-- crea este case 11 en PHP para devolver {asignaciones:{ lineaId:[userId1,userId2], ... }, usuarios:{userId:{nombre,usuario}}}
    });

    if (!res || res.success !== true || !res.asignaciones) return;

    // Normaliza: res.asignaciones = { "001":["u1","u2"], "002":["u3"] ... }
    // res.usuarios = { u1:{nombre:"..." , usuario:"@..."}, ... }  (sugerido para etiquetas)
    Object.entries(res.asignaciones).forEach(([lin, users]) => {
      if (!lineIndex[lin]) lineIndex[lin] = new Set();
      (users || []).forEach((uid) => {
        const u = (res.usuarios && res.usuarios[uid]) || {};
        if (!asignByUser[uid])
          asignByUser[uid] = {
            userName: u.nombre || uid,
            userHandle: u.usuario || "",
            lineas: {},
          };
        asignByUser[uid].lineas[lin] = {
          lineaDesc: res.lineasDesc?.[lin] || lin,
        };
        lineIndex[lin].add(uid);
      });
    });

    renderLista($("#listaEmpresasAsociadas"));
  } catch (e) {
    console.warn("No se pudieron cargar asignaciones existentes:", e);
  }
}
//////////////////////////////////////////////////////////

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
          Swal.fire({
            icon: "warning",
            title: "Aviso",
            text: res.message || "No se Encontraron Lineas.",
          });
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
function obtenerAlmacenistas() {
  $.ajax({
    url: "../Servidor/PHP/inventario.php",
    method: "GET",
    data: {
      numFuncion: "9",
    },
    success: function (response) {
      try {
        const res =
          typeof response === "string" ? JSON.parse(response) : response;

        if (res.success && Array.isArray(res.data)) {
          const lineaSelect = $("#selectUsuario");
          lineaSelect.empty();
          lineaSelect.append(
            "<option selected disabled>Seleccione una linea</option>"
          );

          res.data.forEach((dato) => {
            lineaSelect.append(
              `<option value="${dato.idUsuario}"" data-id="${dato.idUsuario} data-nombre="${dato.nombre}">
                ${dato.nombre}
              </option>`
            );
          });

          // Habilitar el select si hay vendedores disponibles
          //lineaSelect.prop("disabled", res.data.length === 0);
        } else {
          Swal.fire({
            icon: "warning",
            title: "Aviso",
            text: res.message || "No se Encontraron Lineas.",
          });
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
// Helpers
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
function escapeAttr(str) {
  return escapeHtml(str).replace(/"/g, "&quot;");
}
// Helper para escapar HTML
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
/**AsignarLinea**/
// ====== Asignar (click) ======
function asignarLinea() {
  const $selUser = $("#selectUsuario");
  const $selLinea = $("#lineaSelect"); // o #selectLineaModal si renombraste
  const $lista = $("#listaEmpresasAsociadas");
  const userId = ($selUser.val() || "").toString();
  const userName = $selUser.find("option:selected").text().trim() || userId;
  const userHandle = $selUser.find("option:selected").data("usuario") || "";
  const lineaId = ($selLinea.val() || "").toString();
  const lineaDesc = $selLinea.find("option:selected").text().trim() || lineaId;
  if (!userId)
    return Swal.fire({ icon: "warning", title: "Selecciona un usuario" });
  if (!lineaId)
    return Swal.fire({ icon: "warning", title: "Selecciona una línea" });
  // Asegurar estructuras
  if (!asignByUser[userId])
    asignByUser[userId] = { userName, userHandle, lineas: {} };
  if (!lineIndex[lineaId]) lineIndex[lineaId] = new Set();
  // Si ya está asignado a este usuario, no hacemos nada
  const yaEsta = !!asignByUser[userId].lineas[lineaId];
  // Regla: máx. 2 almacenistas por línea (si no es el mismo usuario)
  if (!yaEsta && lineIndex[lineaId].size >= 2) {
    const actuales = Array.from(lineIndex[lineaId]).join(", ");
    return Swal.fire({
      icon: "info",
      title: "Límite alcanzado",
      text: `La línea ${lineaId} ya tiene 2 almacenistas asignados.`,
    });
  }
  // Asignar
  asignByUser[userId].lineas[lineaId] = { lineaDesc };
  lineIndex[lineaId].add(userId);
  renderLista($lista);
}
// ====== Render: agrupado por usuario, listando sus líneas ======
function renderLista($lista) {
  const userIds = Object.keys(asignByUser);
  if (userIds.length === 0) {
    $lista.html('<li class="list-group-item text-muted">Sin asignaciones</li>');
    return;
  }

  const rows = userIds
    .map((uid) => {
      const u = asignByUser[uid];
      const lineas =
        Object.entries(u.lineas)
          .map(
            ([linId, info]) => `
      <span class="badge bg-light text-dark border me-2 mb-2 d-inline-flex align-items-center">
        ${escapeHtml(info.lineaDesc)} 
        <small class="text-muted ms-1">(${escapeHtml(linId)})</small>
        <button type="button"
                class="btn btn-link btn-sm text-danger ms-2 p-0 btnQuitar"
                data-user="${escapeAttr(uid)}"
                data-linea="${escapeAttr(linId)}"
                title="Quitar esta línea">
          &times;
        </button>
      </span>
    `
          )
          .join("") || '<span class="text-muted">Sin líneas</span>';

      return `
      <li class="list-group-item" data-user="${escapeAttr(uid)}">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="fw-semibold">${escapeHtml(u.userName)}</div>
            ${
              u.userHandle
                ? `<small class="text-muted">@${escapeHtml(
                    u.userHandle
                  )}</small>`
                : ""
            }
          </div>
          <button type="button"
                  class="btn btn-outline-danger btn-sm ms-3 btnQuitarUsuario"
                  data-user="${escapeAttr(uid)}"
                  title="Quitar todas las líneas de este usuario">
            Quitar todo
          </button>
        </div>
        <div class="mt-2">${lineas}</div>
      </li>
    `;
    })
    .join("");

  $lista.html(rows);
}
// ====== Helpers ======
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
function escapeAttr(str) {
  return escapeHtml(str).replace(/"/g, "&quot;");
}
$("#listaEmpresasAsociadas").on("click", ".btnQuitar", function () {
  const uid = $(this).data("user");
  const lin = $(this).data("linea");
  if (asignPend[uid] && asignPend[uid].lineas[lin]) {
    delete asignPend[uid].lineas[lin];
    if (Object.keys(asignPend[uid].lineas).length === 0) delete asignPend[uid];
    renderLista(asignPend, $("#listaEmpresasAsociadas"));
  }
});

$("#btnModalInventarios").click(function () {
  mostrarInventarios();
});

let inventarioActualId = null; // variable global para guardar el ID

$("#btnNuevoInventario").click(function () {
  mostrarLoader();
  $.ajax({
    url: "../Servidor/PHP/inventario.php",
    method: "POST",
    data: { numFuncion: "6" },

    success: function (response) {
      cerrarLoader();
      try {
        const res = JSON.parse(response);
        console.log("Crear inve: ", res);
        if (res.success) {
          inventarioActualId = res.idInventario; // guardar el ID
          Swal.fire({
            icon: "success",
            title: "Éxito",
            text: "Inventario iniciado.",
            timer: 1000,
            showConfirmButton: false,
          }).then(() => {
            $("#modalInventarios").modal("hide");
            obtenerLineas();
            obtenerAlmacenistas();
            $("#asociarLineas").modal("show");
          });
        } else {
          Swal.fire({
            icon: "warning",
            title: "Info",
            text: res.message || "Error al guardar la clave.",
          });
        }
      } catch (error) {
        console.error("Error al procesar la respuesta:", error);
        Swal.fire({
          icon: "warning",
          title: "Error",
          text: "Error al guardar la clave.",
        });
      }
    },
    error: function () {
      cerrarLoader();
      Swal.fire({
        icon: "warning",
        title: "Error",
        text: "Error al realizar la solicitud.",
      });
    },
  });
});

// Cancelar desde el header o footer
$("#cerrarModalAsociasionHeader, #cerrarModalAsociasionFooter").click(
  function () {
    if (inventarioActualId) {
      $.ajax({
        url: "../Servidor/PHP/inventario.php",
        method: "POST",
        data: {
          numFuncion: "21",
          idInventario: inventarioActualId,
        },
        success: function (response) {
          try {
            const res = JSON.parse(response);
            if (res.success) {
              console.log("Inventario eliminado:", inventarioActualId);
            } else {
              console.warn("Error al eliminar inventario:", res.message);
            }
          } catch (e) {
            console.error("Error procesando eliminación:", e);
          }
        },
        error: function () {
          console.error("Error al eliminar inventario en backend.");
        },
      });
    }
    $("#asociarLineas").modal("hide");
  }
);

$("#btnGuardarAsignacion").on("click", function () {
  const $btn = $(this);
  const csrf = $("#csrf_token").val();
  //const noInv = $("#noInventario").val();

  /*if (!noInv) {
    return Swal.fire({ icon: "warning", title: "Falta No. Inventario" });
  }*/
  if (Object.keys(asignByUser).length === 0) {
    return Swal.fire({
      icon: "info",
      title: "Sin asignaciones",
      text: "Agrega al menos una asignación.",
    });
  }
  mostrarLoader();
  // Construir payload: { lineaId: userId }
  const asignaciones = {};
  Object.entries(lineIndex).forEach(([lin, set]) => {
    const arr = Array.from(set);
    if (arr.length > 0) asignaciones[String(lin)] = arr.slice(0, 2);
  });
  console.log("payload asignaciones", asignaciones);
  $btn.prop("disabled", true).text("Guardando…");

  $.ajax({
    url: "../Servidor/PHP/inventario.php",
    method: "POST",
    dataType: "json",
    headers: { "X-CSRF-Token": csrf },
    data: {
      numFuncion: "10", // <-- tu case de guardar asignaciones
      //noInventario: noInv,
      payload: JSON.stringify({ asignaciones }), // { "001": "userId", ... }
    },
  })
    .done(async function (res) {
      cerrarLoader();
      if (!res || res.success !== true) {
        throw new Error(res?.message || "No se pudo guardar");
      }
      await Swal.fire({
        icon: "success",
        title: "Asignaciones guardadas",
        timer: 1400,
        showConfirmButton: false,
      });
      //$modal.modal("hide");
      $("#asociarLineas").modal("hide");
    })
    .fail(function (err) {
      cerrarLoader();
      console.error("Guardar asignaciones error:", err);
      Swal.fire({ icon: "error", title: "Error", text: "Intenta de nuevo." });
    })
    .always(function () {
      cerrarLoader();
      $btn.prop("disabled", false).text("Guardar Asignacion");
    });
});
$("#btnAsignar").click(function () {
  asignarLinea();
});
