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
/*function cargarLineasEnModal() {
  const $tbody = $("#tablaAsociaciones");
  $tbody.html(
    `<tr><td colspan="3" class="text-center text-muted">Cargando líneas…</td></tr>`
  );

  $.ajax({
    url: "../Servidor/PHP/inventario.php",
    method: "GET",
    data: { numFuncion: "3" },
  })
    .done(function (response) {
      let res = response;
      try {
        if (typeof response === "string") res = JSON.parse(response);
      } catch (e) {}

      if (
        !res ||
        res.success !== true ||
        !Array.isArray(res.data) ||
        res.data.length === 0
      ) {
        $tbody.html(
          `<tr><td colspan="3" class="text-center text-muted">No hay líneas disponibles.</td></tr>`
        );
        return;
      }

      const rowsHtml = res.data
        .map((dato) => {
          const cve = (dato.CVE_LIN ?? "").toString();
          const desc = (dato.DESC_LIN ?? "").toString();

          return `
          <tr data-linea="${escapeHtml(cve)}">
            <td class="align-middle">
              <div class="fw-semibold">${escapeHtml(desc)}</div>
              <small class="text-muted">Código: ${escapeHtml(cve)}</small>
            </td>
            <td class="align-middle">
              <!-- Aquí pondremos el select de almacenistas más adelante -->
              <span class="badge badge-secondary">Sin asignar</span>
            </td>
          </tr>
        `;
        })
        .join("");

      $tbody.html(rowsHtml);
    })
    .fail(function () {
      $tbody.html(
        `<tr><td colspan="3" class="text-center text-danger">Error al cargar las líneas.</td></tr>`
      );
    });
}*/
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

  // Recolectar asignaciones desde el modal
  const asignaciones = {}; // { lineaId: idUsuario }
  $("#tablaAsociaciones .sel-almacenista").each(function () {
    const lineaId = $(this).data("linea");
    const userId = $(this).val();
    if (lineaId && userId) {
      asignaciones[String(lineaId)] = String(userId);
    }
    // Si está vacío, NO lo incluimos; en el servidor sobreescribiremos el map
    // con solo las claves presentes (esto "desasigna" líneas no enviadas).
  });

  // (Opcional) Validación: al menos 1 asignación
  if (Object.keys(asignaciones).length === 0) {
    return Swal.fire({
      icon: "info",
      title: "Sin asignaciones",
      text: "Selecciona al menos un almacenista.",
    });
  }

  // Estado guardando
  $btn
    .prop("disabled", true)
    .html('<i class="bx bx-loader-alt bx-spin"></i> Guardando…');

  $.ajax({
    url: "../Servidor/PHP/inventario.php",
    method: "POST",
    dataType: "json",
    headers: { "X-CSRF-Token": csrf },
    data: {
      numFuncion: "10",
      payload: JSON.stringify({ asignaciones }),
    },
  })
    .done(function (res) {
      if (!res || res.success !== true) {
        throw new Error(res?.message || "Error al guardar asignaciones");
      }
      Swal.fire({
        icon: "success",
        title: "Asignaciones guardadas",
        timer: 1400,
        showConfirmButton: false,
      });
      // Cierra el modal si quieres:
      $("#asociarLineas").modal("hide");
    })
    .fail(function (err) {
      console.error("Guardar asignaciones error:", err);
      Swal.fire({
        icon: "error",
        title: "No se pudo guardar",
        text: "Intenta de nuevo.",
      });
    })
    .always(function () {
      $btn.prop("disabled", false).html("Guardar");
    });
});
