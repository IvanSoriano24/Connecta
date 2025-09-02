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
function cargarLineasYAlmacenistas(){
    const $tbody = $('#tablaAsociaciones');
    $tbody.html(`<tr><td colspan="3" class="text-center text-muted">Cargando…</td></tr>`);

    // Requests en paralelo
    const reqLineas = $.ajax({
      url: "../Servidor/PHP/inventario.php",
      method: "GET",
      data: { numFuncion: "3" },
      dataType: "json"
    });

    const reqUsers = $.ajax({
      url: "../Servidor/PHP/inventario.php",
      method: "GET",
      data: { numFuncion: "9" }, // ← almacenistas
      dataType: "json"
    });

    $.when(reqLineas, reqUsers)
      .done(function(r1, r2){
        // jQuery when devuelve [data, textStatus, jqXHR] por request
        const resLin = r1 && r1[0] ? r1[0] : null;
        const resUsr = r2 && r2[0] ? r2[0] : null;

        const lineas = (resLin && resLin.success && Array.isArray(resLin.data)) ? resLin.data : [];
        const users  = (resUsr && resUsr.success && Array.isArray(resUsr.data)) ? resUsr.data : [];

        if (lineas.length === 0) {
          $tbody.html(`<tr><td colspan="3" class="text-center text-muted">No hay líneas disponibles.</td></tr>`);
          return;
        }

        const hasUsers = users.length > 0;
        const optionsHtml = [
          `<option value="">-- Sin asignar --</option>`,
          ...users.map(u => `<option value="${escapeAttr(u.idUsuario)}" data-usuario="${escapeAttr(u.usuario||'')}">${escapeHtml(u.nombre || u.usuario || u.idUsuario)}</option>`)
        ].join('');

        const rows = lineas.map(li => {
          const cve  = (li.CVE_LIN ?? '').toString();
          const desc = (li.DESC_LIN ?? '').toString();

          return `
            <tr data-linea="${escapeAttr(cve)}">
              <td class="align-middle">
                <div class="fw-semibold">${escapeHtml(desc)}</div>
                <small class="text-muted">Código: ${escapeHtml(cve)}</small>
              </td>
              <td class="align-middle" style="min-width:260px;">
                ${hasUsers ? `
                  <select class="form-control sel-almacenista" data-linea="${escapeAttr(cve)}">
                    ${optionsHtml}
                  </select>
                ` : `
                  <span class="badge badge-warning">No hay almacenistas</span>
                `}
              </td>
            </tr>
          `;
        }).join('');

        $tbody.html(rows);

        // TODO (siguiente paso): si ya tienes asignaciones guardadas, aquí puedes preseleccionar
        // la opción correspondiente haciendo otro GET (e.g., numFuncion=13) y luego setear .val(idUsuario)
      })
      .fail(function(){
        $tbody.html(`<tr><td colspan="3" class="text-center text-danger">Error al cargar datos.</td></tr>`);
      });
  }
  // Helpers
  function escapeHtml(str){
    return String(str ?? '').replace(/[&<>"']/g, m => ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
    }[m]));
  }
  function escapeAttr(str){
    return escapeHtml(str).replace(/"/g, '&quot;');
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
            //cargarLineasEnModal();
            cargarLineasYAlmacenistas();
            $("#asociarLineas").modal("show");
          });
        } else {
          Swal.fire({
            icon: "warning",
            title: "Error",
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
$("#btnGuardarAsignacion").click(function () {
  $.ajax({
    url: "../Servidor/PHP/inventario.php", // Ruta al PHP
    method: "POST",
    data: {
      numFuncion: "",
    },
    success: function (response) {
      try {
        const res = JSON.parse(response);
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