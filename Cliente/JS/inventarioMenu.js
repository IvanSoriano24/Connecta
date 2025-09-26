// ====== Estado global ======
let asignByUser = {
  // userId: { userName, userHandle, lineas: { lineaId: { lineaDesc } } }
};
let lineIndex = {
  // lineaId: Set<userId>
};

// Función para formatear fecha
function formatearFecha(fechaStr) {
  if (!fechaStr) return "-";

  const meses = ["ene", "feb", "mar", "abr", "may", "jun",
    "jul", "ago", "sept", "oct", "nov", "dic"];

  const [fechaParte, horaParte] = fechaStr.split(" "); // "2025-09-26", "08:30:48"
  const [anio, mes, dia] = fechaParte.split("-");

  return `${parseInt(dia)} ${meses[parseInt(mes) - 1]} ${anio} ${horaParte}`;
}


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
        const fecha = formatearFecha(inv.fechaInicio) ?? "-";
        const estado = inv.status
            ? `<span class="badge-estado badge-activo">Activo</span>`
            : `<span class="badge-estado badge-finalizado">Finalizado</span>`;


        const $acciones = $(`<td class="acciones"></td>`);

        // Descargar (se mostrará tras comprobar archivos)
        const $btnDesc = $(
          `<button class="btn btn-sm btn-blue me-2">Descargar Evidencias</button>`
        ).on("click", () => descargarEvidencia(noInv));
        $acciones.append($btnDesc);

        // Asignar líneas (solo activos)
        if (inv.status === true) {
          const $btnAsign = $(
            `<button class="btn btn-sm btn-purple">Asignar líneas</button>`
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
  $("#modalInventarios").modal("hide");
  mostrarLoader("Cargando Líneas...", "Por favor espera");
  try {
    // Cerrar modal de inventarios
    // guarda el inventario actual en un hidden del modal
    // (agrégalo al modal si no existe)


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
      cerrarLoader();
      modal.show();
      //$("#btnAsignar").off("click").on("click", asignarLinea);
    });
  } catch (e) {
    cerrarLoader();
    console.error(e);
    Swal.fire({
      icon: "error",
      title: "Error",
      text: "No se pudo abrir el modal.",
    });
  }
}
// (Opcional) trae lo que haya guardado en Firestore y puebla asignByUser/lineIndex
// Llama esto al abrir el modal, pasando el noInventario activo
async function cargarAsignacionesExistentes(noInv) {
  try {
    const res = await $.ajax({
      url: "../Servidor/PHP/inventario.php",
      method: "GET",
      dataType: "json",
      data: { numFuncion: "11", noInventario: noInv },
    });

    if (!res || res.success !== true) return;

    // Limpia estructuras en memoria
    asignByUser = {};
    lineIndex = {};

    const asign = res.asignaciones || {}; // { lineaId: [uid1, uid2], ... }
    const users = res.usuarios || {};      // opcional, para mostrar nombre/usuario

    Object.entries(asign).forEach(([lineaId, uids]) => {
      if (!lineIndex[lineaId]) lineIndex[lineaId] = new Set();
      (uids || []).slice(0, 2).forEach(uid => {
        const infoU = users[uid] || {};
        if (!asignByUser[uid]) {
          asignByUser[uid] = {
            userName: infoU.nombre || uid,
            userHandle: infoU.usuario || "",
            lineas: {}
          };
        }
        // 👇 clave: marcamos persisted
        asignByUser[uid].lineas[lineaId] = { lineaDesc: lineaId, persisted: true };
        lineIndex[lineaId].add(uid);
      });
    });

    renderLista($("#listaEmpresasAsociadas"));
  } catch (e) {
    console.error("cargarAsignacionesExistentes error:", e);
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
            "<option selected disabled>Seleccione un usuario</option>"
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

  const rows = userIds.map((uid) => {
    const u = asignByUser[uid];
    const lineas = Object.entries(u.lineas).map(([linId, info]) => {
      const isOld = !!info.persisted; // ← venía del servidor
      // estilos: viejo => éxito sutil; nuevo => claro
      const cls = isOld
        ? "bg-success-subtle border border-success text-success"
        : "bg-light text-dark border";

      const check = isOld ? '<i class="bx bx-check-circle me-1"></i>' : '';

      return `
        <span class="badge ${cls} me-2 mb-2 d-inline-flex align-items-center">
          ${check}${escapeHtml(info.lineaDesc)}
          <small class="text-muted ms-1">(${escapeHtml(linId)})</small>
          <button type="button"
                  class="btn btn-link btn-sm ${isOld ? 'text-warning' : 'text-danger'} ms-2 p-0 btnQuitar"
                  data-user="${escapeAttr(uid)}"
                  data-linea="${escapeAttr(linId)}"
                  title="${isOld ? 'Quitar (ya asignada antes)' : 'Quitar'}">
            &times;
          </button>
        </span>
      `;
    }).join("") || '<span class="text-muted">Sin líneas</span>';

    return `
      <li class="list-group-item" data-user="${escapeAttr(uid)}">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="fw-semibold">${escapeHtml(u.userName)}</div>
            ${u.userHandle ? `<small class="text-muted">@${escapeHtml(u.userHandle)}</small>` : ""}
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
  }).join("");

  // leyenda opcional
  const legend = `
    <li class="list-group-item d-flex align-items-center gap-3">
      <span class="badge bg-success-subtle border border-success text-success">
        <i class="bx bx-check-circle me-1"></i>Asignación existente
      </span>
      <span class="badge bg-light text-dark border">Nueva asignación</span>
    </li>
  `;

  $lista.html(legend + rows);
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

  const obj = asignByUser?.[uid]?.lineas?.[lin];
  if (!obj) return;

  const reallyRemove = async () => {
    delete asignByUser[uid].lineas[lin];
    lineIndex[lin]?.delete(uid);
    if (Object.keys(asignByUser[uid].lineas).length === 0) delete asignByUser[uid];
    renderLista($("#listaEmpresasAsociadas"));
  };

  if (obj.persisted) {
    Swal.fire({
      icon: "warning",
      title: "Quitar asignación existente",
      text: "Esta línea ya estaba asignada previamente. ¿Seguro que deseas quitarla?",
      showCancelButton: true,
      confirmButtonText: "Sí, quitar",
      cancelButtonText: "Cancelar",
    }).then((r) => { if (r.isConfirmed) reallyRemove(); });
  } else {
    reallyRemove();
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


// ======================== SWITCHES DE CONFIGURACION ================================
$(document).ready(function() {
  // Obtener valores iniciales al cargar la página
  obtenerConfig();

  // Eventos de cambio
  $("#switchGeneracionConteos").on("change", function() {
    actualizarConfig("generacionConteos", this.checked);
  });

  $("#switchGuardadoAutomatico").on("change", function() {
    actualizarConfig("guardadoAutomatico", this.checked);
  });
});

// === Obtener configuración inicial (case 22) ===
function obtenerConfig() {
  $.ajax({
    url: "../Servidor/PHP/inventario.php",
    method: "GET",
    data: { numFuncion: 22 },
    dataType: "json" // 👈 fuerza JSON
  })
      .done(function(res) {
        if (res.success && res.data) {
          $("#switchGeneracionConteos").prop("checked", res.data.generacionConteos === true);
          $("#switchGuardadoAutomatico").prop("checked", res.data.guardadoAutomatico === true);
        } else {
          console.error("Error al obtener configuración:", res.message || "Respuesta sin datos");
        }
      })
      .fail(function(err) {
        console.error("Fallo en obtenerConfig:", err);
      });
}



// === Actualizar configuración (case 23) ===
function actualizarConfig(campo, valor) {
  $.post("../Servidor/PHP/inventario.php", {
    numFuncion: 23,
    campo: campo,
    valor: valor
  }, null, "json").done(function(res) {
    if (res.success) {
      console.log("Actualizado");
    } else {
      console.log("Error");
    }
  }).fail(function(err) {
    console.log("Fallo en actualizarConfig:", err);
  });
}


