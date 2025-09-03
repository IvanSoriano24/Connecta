function mostrarInventarios() {
  $.ajax({
    url: "../Servidor/PHP/inventario.php",
    type: "POST",
    dataType: "json",
    data: { numFuncion: "7" },
    success: function (response) {
      if (response.succes) {
        const inventarios = response.inventarios || [];
        const $tbody = $("#tablaInventarios");
        $tbody.empty(); // limpiar antes de re-dibujar

        if (inventarios.length === 0) {
          $tbody.append(
            $("<tr>").append(
              $("<td>")
                .attr("colspan", 4)
                .addClass("text-center text-muted")
                .text("No hay inventarios disponibles")
            )
          );
          return;
        }

        inventarios.forEach((inv) => {
          const noInv = inv.noInventario ?? "-";
          const fecha = inv.fechaInicio ?? "-";
          const estado = inv.estado ?? "Pendiente";

          // Botones de acciones
          const $acciones = $("<td>").append(
            $("<button>")
              .addClass("btn btn-sm btn-primary me-2")
              .text("Ver")
              .on("click", function () {
                // aquí llamas a tu función para ver el detalle
                console.log("Ver inventario", noInv);
              }),
            $("<button>")
              .addClass("btn btn-sm btn-danger")
              .text("Eliminar")
              .on("click", function () {
                // aquí llamas a tu función de eliminar
                console.log("Eliminar inventario", noInv);
              })
          );

          const $fila = $("<tr>");
          $fila.append($("<td>").text(noInv));
          $fila.append($("<td>").text(fecha));
          $fila.append($("<td>").text(estado));
          //$fila.append($acciones);

          $tbody.append($fila);
        });
      } else {
        console.warn("Inventarios:", response.message);
        alert("Error al cargar inventarios: " + response.message);
      }
    },
    error: function (jqXHR, textStatus, errorThrown) {
      console.error("AJAX error:", textStatus, errorThrown);
      alert("Error en la solicitud AJAX.");
    },
  });
}
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
// ====== Estado global ======
const asignByUser = {
  // userId: { userName, userHandle, lineas: { lineaId: { lineaDesc } } }
};
const lineIndex = {
  // lineaId: Set<userId>
};

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
$("#btnNuevoInventario").click(function () {
  $.ajax({
    url: "../Servidor/PHP/inventario.php", // Ruta al PHP
    method: "POST",
    data: {
      numFuncion: "6",
    },
    success: function (response) {
      try {
        const res = JSON.parse(response);
        if (res.success) {
          Swal.fire({
            icon: "success",
            title: "Éxito",
            text: "Inventario iniciado.",
            timer: 1000,
            showConfirmButton: false,
          }).then(() => {
            //abrirModalAsignacion();
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
      Swal.fire({
        icon: "warning",
        title: "Error",
        text: "Error al realizar la solicitud.",
      });
    },
  });
});
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
    .done(function (res) {
      if (!res || res.success !== true) {
        throw new Error(res?.message || "No se pudo guardar");
      }
      Swal.fire({
        icon: "success",
        title: "Asignaciones guardadas",
        timer: 1400,
        showConfirmButton: false,
      });
      //$modal.modal("hide");
      $("#asociarLineas").modal("hide");
    })
    .fail(function (err) {
      console.error("Guardar asignaciones error:", err);
      Swal.fire({ icon: "error", title: "Error", text: "Intenta de nuevo." });
    })
    .always(function () {
      $btn.prop("disabled", false).text("Guardar Asignacion");
    });
});
$("#btnAsignar").click(function () {
  asignarLinea();
});
