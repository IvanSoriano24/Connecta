const noEmpresa = sessionStorage.getItem("noEmpresaSeleccionada");
let paginaActual = 1;
let registrosPorPagina = 10;
let estadoMuestra = localStorage.getItem("estadoMuestra") || "Activas";

// Función para inicializar tooltips de Bootstrap en la tabla de muestras
function initTooltipsMuestras() {
  const nodes = document.querySelectorAll('#muestras [data-bs-toggle="tooltip"]');
  nodes.forEach(el => {
    const inst = bootstrap.Tooltip.getInstance(el);
    if (inst) inst.dispose();
    new bootstrap.Tooltip(el, { delay: { show: 0, hide: 0 }, trigger: 'hover' });
  });
}

function agregarEventosBotones() {
  // Botones de editar
  const botonesEditar = document.querySelectorAll(".btnEditarMuestra");
  botonesEditar.forEach((boton) => {
    boton.addEventListener("click", async function () {
      const muestraID = this.dataset.id;
      window.location.href = "gestionMuestra.php?accion=editar&muestraID=" + muestraID;
    });
  });

  // Botones de ver
  const botonesVer = document.querySelectorAll(".btnVerMuestra");
  botonesVer.forEach((boton) => {
    boton.addEventListener("click", async function () {
      const muestraID = this.dataset.id;
      window.location.href = "gestionMuestra.php?accion=ver&muestraID=" + muestraID;
    });
  });

  // Botones de cancelar
  const botonesCancelar = document.querySelectorAll(".btnCancelarMuestra");
  botonesCancelar.forEach((boton) => {
    boton.addEventListener("click", async function () {
      const muestraID = this.dataset.id;
      Swal.fire({
        title: "¿Estás seguro?",
        text: "¿Deseas cancelar esta muestra?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Sí, cancelar",
        cancelButtonText: "No",
      }).then((result) => {
        if (result.isConfirmed) {
          cancelarMuestra(muestraID);
        }
      });
    });
  });

  // Botones de descargar
  const botonesDescargar = document.querySelectorAll(".btnDescargarMuestra");
  botonesDescargar.forEach((boton) => {
    boton.addEventListener("click", function () {
      const muestraID = this.dataset.id;
      descargarPdf(muestraID);
    });
  });
}

function cancelarMuestra(muestraID) {
  $.post(
    "../Servidor/PHP/muestras.php",
    { numFuncion: "10", muestraID: muestraID },
    function (response) {
      try {
        if (typeof response === "string") {
          response = JSON.parse(response);
        }
        if (response.success) {
          Swal.fire({
            title: "Cancelada",
            text: "La muestra ha sido cancelada correctamente",
            icon: "success",
            confirmButtonText: "Entendido",
          }).then(() => {
            datosMuestras(true);
          });
        } else {
          Swal.fire({
            title: "Aviso",
            text: response.message || "No se pudo cancelar la muestra",
            icon: "error",
            confirmButtonText: "Entendido",
          });
        }
      } catch (error) {
        console.error("Error al procesar la respuesta JSON:", error);
      }
    }
  ).fail(function (jqXHR, textStatus, errorThrown) {
    Swal.fire({
      title: "Aviso",
      text: "Hubo un problema al intentar cancelar la muestra",
      icon: "error",
      confirmButtonText: "Entendido",
    });
    console.log("Detalles del error:", jqXHR.responseText);
  });
}

function descargarPdf(muestraID) {
  $.ajax({
    url: "../Servidor/PHP/muestras.php",
    method: "GET",
    data: {
      numFuncion: 26,
      muestraID: muestraID,
    },
    xhrFields: {
      responseType: "blob",
    },
    success: function (blob, status, xhr) {
      let disposition = xhr.getResponseHeader("Content-Disposition");
      let filename = "Muestra_" + muestraID + ".pdf";
      if (disposition && disposition.indexOf("filename=") !== -1) {
        let match = disposition.match(/filename="?([^"]+)"?/);
        if (match && match[1]) {
          filename = "Muestra_" + muestraID + ".pdf";
        }
      }
      let urlBlob = window.URL.createObjectURL(blob);
      let a = document.createElement("a");
      a.style.display = "none";
      a.href = urlBlob;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(urlBlob);
      document.body.removeChild(a);
    },
    error: function (jqXHR, textStatus, errorThrown) {
      Swal.fire({
        title: "Error",
        text: "No se pudo descargar la muestra.",
        icon: "error",
        confirmButtonText: "Entendido",
      });
      console.error("Error en la descarga:", textStatus, errorThrown);
    },
  });
}

// Función para mostrar mensaje cuando no hay datos
function mostrarSinDatos() {
  const muestrasTable = document.getElementById("datosMuestras");
  if (!muestrasTable) {
    console.error("No se encontró el elemento con id 'datosMuestras'");
    return;
  }
  muestrasTable.innerHTML = "";
  const row = document.createElement("tr");
  const numColumns = 8; // Actualizar según número de columnas
  row.innerHTML = `<td colspan="${numColumns}" style="text-align: center;">No hay datos disponibles</td>`;
  muestrasTable.appendChild(row);
}

function makeBtn(text, page, disabled, active) {
  const $btn = $("<button>")
    .text(text)
    .prop("disabled", disabled)
    .toggleClass("active", active);

  if (!disabled) {
    $btn.on("click", () => {
      paginaActual = page;
      datosMuestras(true);
    });
  }

  return $btn;
}

function buildPagination(total) {
  const totalPages = Math.ceil(total / registrosPorPagina);
  const maxButtons = 5;
  const $cont = $("#pagination").empty();

  if (totalPages <= 1) return;

  let start = Math.max(1, paginaActual - Math.floor(maxButtons / 2));
  let end = start + maxButtons - 1;
  if (end > totalPages) {
    end = totalPages;
    start = Math.max(1, end - maxButtons + 1);
  }

  if (paginaActual > 1) {
    $cont.append(makeBtn("«", 1, false, false));
    $cont.append(makeBtn("‹", paginaActual - 1, false, false));
  }

  for (let p = start; p <= end; p++) {
    $cont.append(makeBtn(String(p), p, false, p === paginaActual));
  }

  if (paginaActual < totalPages) {
    $cont.append(makeBtn("›", paginaActual + 1, false, false));
    $cont.append(makeBtn("»", totalPages, false, false));
  }
}

// CARGAR Los Datos
function datosMuestras(limpiarTabla = false) {
  const muestrasTable = document.getElementById("datosMuestras");
  if (!muestrasTable) {
    console.error("No se encontró el elemento con id 'datosMuestras'");
    return;
  }

  if (limpiarTabla) {
    muestrasTable.innerHTML = "";
  }

  const filtroFecha = localStorage.getItem("filtroSeleccionado") || "Hoy";
  const filtroVendedor = document.getElementById("filtroVendedor")
    ? $("#filtroVendedor").val()
    : null;
  const searchTerm = $("#searchTerm").val() || "";

  $.post(
    "../Servidor/PHP/muestras.php",
    {
      numFuncion: "1",
      filtroFecha: filtroFecha,
      estadoMuestra: estadoMuestra,
      filtroVendedor: filtroVendedor,
      searchTerm: searchTerm,
      offset: (paginaActual - 1) * registrosPorPagina,
      limit: registrosPorPagina,
    },
    function (response) {
      try {
        if (typeof response === "string") {
          response = JSON.parse(response);
        }

        if (response && response.success && Array.isArray(response.data)) {
          const muestras = response.data;
          const numColumns = 8;

          if (muestras.length > 0) {
            const fragment = document.createDocumentFragment();

            muestras.forEach((muestra) => {
              const row = document.createElement("tr");

              // Determinar qué botones mostrar según el estado
              const mostrarBotones =
                estadoMuestra === "Activas" &&
                (tipoUsuario === "ADMINISTRADOR" || tipoUsuario === "VENDEDOR");

              row.innerHTML = `
                <td>${muestra.Clave || "Sin folio"}</td>
                <td>${muestra.Cliente || "Sin cliente"}</td>
                <td>${muestra.Nombre || "Sin nombre"}</td>
                <td>${muestra.FechaElaboracion || "Sin fecha"}</td>
                <td class="nombreVendedor">${
                  muestra.NombreVendedor || "Sin vendedor"
                }</td>
                ${
                  mostrarBotones
                    ? `
                  <!-- EDITAR -->
                  <td class="text-center">
                    <i class="bi btn-iconNuevos bi-pencil btnEditarMuestra"
                       title="Editar Muestra"
                       data-bs-toggle="tooltip" data-bs-placement="top" data-bs-delay='{"show":0,"hide":0}'
                       data-id="${muestra.Clave}"></i>
                  </td>

                  <!-- CANCELAR -->
                  <td class="text-center">
                    <i class="bi btn-iconNuevos bi-x-circle btnCancelarMuestra"
                       title="Cancelar Muestra"
                       data-bs-toggle="tooltip" data-bs-placement="top" data-bs-delay='{"show":0,"hide":0}'
                       data-id="${muestra.Clave}"></i>
                  </td>
                `
                    : `
                  <td></td>
                  <td></td>
                `
                }

                <!-- VISUALIZAR -->
                <td class="text-center">
                  <i class="bi btn-iconNuevos bi-eye btnVerMuestra"
                     title="Ver Detalles"
                     data-bs-toggle="tooltip" data-bs-placement="top" data-bs-delay='{"show":0,"hide":0}'
                     data-id="${muestra.Clave}"></i>
                </td>

                <!-- DESCARGAR -->
                <td class="text-center">
                  <i class="bi btn-iconNuevos bi-download btnDescargarMuestra"
                     title="Descargar PDF"
                     data-bs-toggle="tooltip" data-bs-placement="top" data-bs-delay='{"show":0,"hide":0}'
                     data-id="${muestra.Clave}"></i>
                </td>
              `;

              fragment.appendChild(row);
            });

            muestrasTable.appendChild(fragment);
            buildPagination(response.total);

            // Inicializar tooltips de Bootstrap
            initTooltipsMuestras();

            // Asignar eventos a los botones
            if (typeof agregarEventosBotones === "function") {
              agregarEventosBotones();
            }
          } else {
            muestrasTable.innerHTML = `<tr><td colspan="${numColumns}" style="text-align: center;">No hay datos disponibles</td></tr>`;
          }
        } else {
          console.error("La respuesta no es un objeto válido:", response);
        }
      } catch (error) {
        console.error("Error al procesar la respuesta JSON:", error);
        console.error("Detalles de la respuesta:", response);
      }
    },
    "json"
  ).fail(function (jqXHR, textStatus, errorThrown) {
    console.error("Error en la solicitud:", textStatus, errorThrown);
    console.log("Detalles de la respuesta JSON:", jqXHR.responseText);
  });
}

// Función debounce para búsqueda
let searchTimeout;
function debouncedSearch() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    paginaActual = 1;
    datosMuestras(true);
  }, 500);
}

// Función para llenar filtro de vendedor (solo ADMIN)
function llenarFiltroVendedor() {
  $.post(
    "../Servidor/PHP/muestras.php",
    { numFuncion: "33" },
    function (response) {
      if (response.success && Array.isArray(response.data)) {
        const $select = $("#filtroVendedor").empty();
        $select.append('<option value="">Todos los vendedores</option>');
        response.data.forEach((vendedor) => {
          $select.append(
            `<option value="${vendedor.CVE_VEND}">${vendedor.NOMBRE}</option>`
          );
        });
      }
    },
    "json"
  );
}

// Eventos para filtros
$(document).on("change", "#filtroVendedor", function () {
  paginaActual = 1;
  datosMuestras(true);
});

// Eventos para botones de filtro de estado
$(document).on("click", ".filtro-rol", function () {
  $(".filtro-rol").removeClass("btn-primary").addClass("btn-secondary");
  $(this).removeClass("btn-secondary").addClass("btn-primary");

  estadoMuestra = $(this).data("rol");
  localStorage.setItem("estadoMuestra", estadoMuestra);

  paginaActual = 1;
  datosMuestras(true);
});

// Recuperar estado del filtro al cargar
$(document).ready(function () {
  const estadoGuardado = localStorage.getItem("estadoMuestra") || "Activas";
  $(`.filtro-rol[data-rol="${estadoGuardado}"]`).trigger("click");
});
