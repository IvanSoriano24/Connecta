// Referencias a los elementos
const btnDatosGenerales = document.getElementById("btnDatosGenerales");
const btnDatosVentas = document.getElementById("btnDatosVentas");
const formDatosGenerales = document.getElementById("formDatosGenerales");
const formDatosVentas = document.getElementById("formDatosVentas");
let paginaActual = 1;
//const registrosPorPagina = 5; // Ajusta según convenga
let registrosPorPagina = 10;

// Mostrar Datos Generales
btnDatosGenerales.addEventListener("click", () => {
  formDatosGenerales.style.display = "block"; // Mostrar Datos Generales
  formDatosVentas.style.display = "none"; // Ocultar Datos Ventas
  btnDatosGenerales.classList.add("btn-primary");
  btnDatosGenerales.classList.remove("btn-secondary");
  btnDatosVentas.classList.add("btn-secondary");
  btnDatosVentas.classList.remove("btn-primary");
});

// Mostrar Datos Ventas
btnDatosVentas.addEventListener("click", () => {
  formDatosGenerales.style.display = "none"; // Ocultar Datos Generales
  formDatosVentas.style.display = "block"; // Mostrar Datos Ventas
  btnDatosVentas.classList.add("btn-primary");
  btnDatosVentas.classList.remove("btn-secondary");
  btnDatosGenerales.classList.add("btn-secondary");
  btnDatosGenerales.classList.remove("btn-primary");
});

let debounceTimeout;
function debouncedSearch() {
  clearTimeout(debounceTimeout);

  // Espera 3 segundos antes de ejecutar doSearch
  debounceTimeout = setTimeout(() => {
    doSearch();
  }, 500);
}

function doSearch() {
  const searchText = document.getElementById("searchTerm").value.toLowerCase();
  
  if (searchText.length >= 2) {
    $.post(
      "../Servidor/PHP/clientes.php",
      {
        numFuncion: "4",
        noEmpresa: noEmpresa,
        token: token,
        pagina: paginaActual,
        porPagina: registrosPorPagina,
        searchText: searchText
      },
      function (response) {
        try {
          // Verifica si response es una cadena (string) que necesita ser parseada
          if (typeof response === "string") {
            response = JSON.parse(response);
          }
          // Verifica si response es un objeto antes de intentar procesarlo
          if (typeof response === "object" && response !== null) {
            if (response.success && response.data) {
              let clientes = response.data;
              // Eliminar duplicados basados en la 'CLAVE' (suponiendo que 'CLAVE' es única)
              clientes = clientes.filter(
                (value, index, self) =>
                  index === self.findIndex((t) => t.CLAVE === value.CLAVE)
              );
  
              // Ordenar los clientes por la clave (cliente.CLAVE)
              clientes.sort((a, b) => {
                const claveA = a.CLAVE ? parseInt(a.CLAVE) : 0;
                const claveB = b.CLAVE ? parseInt(b.CLAVE) : 0;
                return claveA - claveB; // Orden ascendente
              });
  
              const clientesTable = document.getElementById("datosClientes");
              clientesTable.innerHTML = ""; // Limpiar la tabla antes de agregar nuevos datos
  
              // Recorrer los clientes ordenados y agregar las filas a la tabla
              clientes.forEach((cliente) => {
                const saldo = parseFloat(cliente.SALDO || 0);
                const saldoFormateado = `$${saldo.toLocaleString("es-MX", {
                  minimumFractionDigits: 2,
                  maximumFractionDigits: 2,
                })}`;
                const estadoTimbrado = cliente.EstadoDatosTimbrado
                  ? "<i class='bx bx-check-square' style='color: green; display: block; margin: 0 auto;'></i>"
                  : ""; // Centrado de la palomita con display: block y margin: 0 auto
                const row = document.createElement("tr");
                row.innerHTML = `
                              <td>${cliente.CLAVE || "Sin clave"}</td>
                              <td>${cliente.NOMBRE || "Sin nombre"}</td>
                              <td>${cliente.CALLE || "Sin calle"}</td>
                              <td style="text-align: right;">${saldoFormateado}</td>
                              <td style="text-align: center;">${estadoTimbrado}</td> <!-- Centrar la palomita -->
                              <td>${
                                cliente.NOMBRECOMERCIAL || "Sin nombre comercial"
                              }</td>
                              <td>
                                  <button class="btnVisualizarCliente" name="btnVisualizarCliente" data-id="${
                                    cliente.CLAVE
                                  }" style="
                                      display: inline-flex;
                                      align-items: center;
                                      padding: 0.5rem 1rem;
                                      font-size: 1rem;
                                      font-family: Lato;
                                      color: #fff;
                                      background-color: #007bff;
                                      border: none;
                                      border-radius: 0.25rem;
                                      cursor: pointer;
                                      transition: background-color 0.3s ease;
                                  ">
                                      <i class="fas fa-eye" style="margin-right: 0.5rem;"></i> Visualizar
                                  </button>
                              </td>
                          `;
                clientesTable.appendChild(row);
              });
              buildPagination(response.total);
              agregarEventosBotones();
            } else {
              const clientesTable = document.getElementById("datosClientes");
              clientesTable.innerHTML = `<tr><td style="text-align: center;">No hay datos disponibles</td></tr>`;
            }
          } else {
            console.error("La respuesta no es un objeto válido:", response);
          }
        } catch (error) {
          console.error("Error al procesar la respuesta JSON:", error);
          console.error("Detalles de la respuesta:", response); // Mostrar respuesta completa
        }
      },
      "json"
    ).fail(function (jqXHR, textStatus, errorThrown) {
      console.error("Error en la solicitud:", textStatus, errorThrown, jqXHR);
    });
  } else {
    obtenerClientes(true);
  }
}

const noEmpresa = sessionStorage.getItem("noEmpresaSeleccionada");
const token = document.getElementById("csrf_token").value;

// Función para agregar eventos a los botones dinámicos
function agregarEventosBotones() {
  const botonesVisualizar = document.querySelectorAll(".btnVisualizarCliente");

  botonesVisualizar.forEach((boton) => {
    boton.addEventListener("click", function () {
      const clienteId = this.getAttribute("data-id");
      // Realizamos la petición AJAX para obtener los datos del cliente
      fetch(
        `../Servidor/PHP/clientes.php?clave=${clienteId}&numFuncion=2&token=${token}`
      )
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            const cliente = data.cliente;
            const saldoFormateado = `$${parseFloat(cliente.SALDO || 0)
              .toFixed(2)
              .toLocaleString("es-MX")}`;
            const limiteFormateado = `$${parseFloat(cliente.LIMCRED || 0)
              .toFixed(2)
              .toLocaleString("es-MX")}`;
            // Asignamos los valores de los campos del cliente
            // Asignar los valores de los campos del cliente, con valores por defecto si están indefinidos
            document.getElementById("clave").value =
              cliente.CLAVE || "Sin clave";
            document.getElementById("nombre").value =
              cliente.NOMBRE || "Sin nombre";
            document.getElementById("estatus").value =
              cliente.STATUS || "Sin clasificación";
            document.getElementById("saldo").value = saldoFormateado || "0";
            document.getElementById("rfc").value = cliente.RFC || "Sin RFC";
            document.getElementById("calle").value =
              cliente.CALLE || "Sin calle";
            document.getElementById("numE").value =
              cliente.NUMEXT || "Sin número exterior";
            document.getElementById("regimenFiscal").value =
              cliente.REGIMENFISCAL || "Sin régimen fiscal";
            document.getElementById("numI").value =
              cliente.NUMINT || "Sin número interior";
            document.getElementById("curp").value = cliente.CURP || "Sin CURP";
            document.getElementById("entreCalle").value =
              cliente.ENTRECALLE || "Sin entre calle";
            document.getElementById("yCalle").value =
              cliente.YCALLE || "Sin calle";
            document.getElementById("nacionalidad").value =
              cliente.NACIONALIDAD || "Sin nacionalidad";
            document.getElementById("estado").value =
              cliente.ESTADO || "Sin estado";
            document.getElementById("poblacion").value =
              cliente.POBLACION || "Sin población";
            document.getElementById("pais").value = cliente.PAIS || "Sin país";
            document.getElementById("codigoPostal").value =
              cliente.CODIGOPOSTAL || "Sin código postal";
            document.getElementById("municipio").value =
              cliente.MUNICIPIO || "Sin municipio";
            document.getElementById("colonia").value =
              cliente.COLONIA || "Sin colonia";
            document.getElementById("referencia").value =
              cliente.REFERENCIA || "Sin referencia";
            document.getElementById("clasificacion").value =
              cliente.CLASIFICACION || "Sin clasificación";
            document.getElementById("telefono").value =
              cliente.TELEFONO || "Sin teléfono";
            document.getElementById("zona").value = cliente.ZONA || "Sin zona";
            document.getElementById("fax").value = cliente.FAX || "Sin fax";
            document.getElementById("paginaWeb").value =
              cliente.PAGINAWEB || "Sin página web";

            //document.getElementById('manejoCredito').value = cliente.CON_CREDITO;
            document.getElementById("manejoCredito").checked =
              cliente.CON_CREDITO === "S";
            document.getElementById("diaRevision").value =
              cliente.DIAREV || "Sin dias de revicion";
            document.getElementById("diasCredito").value =
              cliente.DIASCRED || "Sin dias de credito";
            document.getElementById("diaPago").value = cliente.DIAPAGO || "";
            document.getElementById("limiteCredito").value =
              limiteFormateado || "Sin página web";
            document.getElementById("saldoVentas").value =
              saldoFormateado || "Sin saldo";
            document.getElementById("metodoPago").value =
              cliente.METODODEPAGO || "Sin metodo de pago";

            document.getElementById("listaPrecios").value =
              cliente.LISTA_PREC || "1";
            document.getElementById("descuento").value =
              cliente.DESCUENTO || "Sin descuento";
            let vend = cliente.NombreVendedor;
            let nombreVendedor = vend.split("/");
            document.getElementById("vendedor").value =
              cliente.CVE_VEND || "Sin Vendedor";
            document.getElementById("vendedorNombre").value =
              nombreVendedor[0] || "Sin Vendedor";

            // Mostrar el modal si se está utilizando
            $("#usuarioModal").modal("show"); // Asegúrate de que estás usando jQuery y Bootstrap para este modal
          } else {
            Swal.fire({
              title: "Aviso",
              text: "Error al obtener la información del cliente.",
              icon: "warning",
              confirmButtonText: "Aceptar",
            });
            //alert('Error al obtener la información del cliente');
          }
        })
        .catch((error) => {
          console.error("Error al obtener los datos del cliente:", error);
          Swal.fire({
            title: "Aviso",
            text: "Hubo un error al intentar cargar la información del cliente.",
            icon: "error",
            confirmButtonText: "Aceptar",
          });
          //alert('Hubo un error al intentar cargar la información del cliente.');
        });
    });
  });
}


function cerrarModal() {
  const modal = bootstrap.Modal.getInstance(
    document.getElementById("usuarioModal")
  );
  modal.hide();
}
function obtenerClientes(limpiarTabla = true) {
  $.post(
    "../Servidor/PHP/clientes.php",
    {
      numFuncion: "1",
      noEmpresa: noEmpresa,
      token: token,
      pagina: paginaActual,
      porPagina: registrosPorPagina,
    },
    function (response) {
      try {
        // Verifica si response es una cadena (string) que necesita ser parseada
        if (typeof response === "string") {
          response = JSON.parse(response);
        }
        // Verifica si response es un objeto antes de intentar procesarlo
        if (typeof response === "object" && response !== null) {
          if (response.success && response.data) {
            let clientes = response.data;
            // Eliminar duplicados basados en la 'CLAVE' (suponiendo que 'CLAVE' es única)
            clientes = clientes.filter(
              (value, index, self) =>
                index === self.findIndex((t) => t.CLAVE === value.CLAVE)
            );

            // Ordenar los clientes por la clave (cliente.CLAVE)
            clientes.sort((a, b) => {
              const claveA = a.CLAVE ? parseInt(a.CLAVE) : 0;
              const claveB = b.CLAVE ? parseInt(b.CLAVE) : 0;
              return claveA - claveB; // Orden ascendente
            });

            const clientesTable = document.getElementById("datosClientes");
            clientesTable.innerHTML = ""; // Limpiar la tabla antes de agregar nuevos datos

            // Recorrer los clientes ordenados y agregar las filas a la tabla
            clientes.forEach((cliente) => {
              const saldo = parseFloat(cliente.SALDO || 0);
              const saldoFormateado = `$${saldo.toLocaleString("es-MX", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              })}`;
              const estadoTimbrado = cliente.EstadoDatosTimbrado
                ? "<i class='bx bx-check-square' style='color: green; display: block; margin: 0 auto;'></i>"
                : ""; // Centrado de la palomita con display: block y margin: 0 auto
              const row = document.createElement("tr");
              row.innerHTML = `
                            <td>${cliente.CLAVE || "Sin clave"}</td>
                            <td>${cliente.NOMBRE || "Sin nombre"}</td>
                            <td>${cliente.CALLE || "Sin calle"}</td>
                            <td style="text-align: right;">${saldoFormateado}</td>
                            <td style="text-align: center;">${estadoTimbrado}</td> <!-- Centrar la palomita -->
                            <td>${
                              cliente.NOMBRECOMERCIAL || "Sin nombre comercial"
                            }</td>
                            <td>
                                <button class="btnVisualizarCliente" name="btnVisualizarCliente" data-id="${
                                  cliente.CLAVE
                                }" style="
                                    display: inline-flex;
                                    align-items: center;
                                    padding: 0.5rem 1rem;
                                    font-size: 1rem;
                                    font-family: Lato;
                                    color: #fff;
                                    background-color: #007bff;
                                    border: none;
                                    border-radius: 0.25rem;
                                    cursor: pointer;
                                    transition: background-color 0.3s ease;
                                ">
                                    <i class="fas fa-eye" style="margin-right: 0.5rem;"></i> Visualizar
                                </button>
                            </td>
                        `;
              clientesTable.appendChild(row);
            });
            buildPagination(response.total);
            agregarEventosBotones();
          } else {
            Swal.fire({
              title: "Aviso",
              text: "Error desde el servidor: " + response.message,
              icon: "warning",
              confirmButtonText: "Entendido",
            });
            //console.error('Error en la respuesta del servidor:', response);
          }
        } else {
          console.error("La respuesta no es un objeto válido:", response);
        }
      } catch (error) {
        console.error("Error al procesar la respuesta JSON:", error);
        console.error("Detalles de la respuesta:", response); // Mostrar respuesta completa
      }
    },
    "json"
  ).fail(function (jqXHR, textStatus, errorThrown) {
    console.error("Error en la solicitud:", textStatus, errorThrown, jqXHR);
  });
}
/*function buildPagination(total) {
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

  /*const makeBtn = (txt, page, disabled, active) =>
    $("<button>")
      .text(txt)
      .prop("disabled", disabled)
      .toggleClass("active", active)
      .on("click", () => {
        paginaActual = page;
        obtenerClientes(true);
      });*/
    /*let makeBtn = makeBtn(text, page, disabled, active);
  // Flechas First / Prev
  $cont.append(makeBtn("«", 1, paginaActual === 1, false));
  $cont.append(makeBtn("‹", paginaActual - 1, paginaActual === 1, false));

  // Botones de página
  for (let i = start; i <= end; i++) {
    $cont.append(makeBtn(i, i, false, i === paginaActual));
  }

  // Flechas Next / Last
  $cont.append(
    makeBtn("›", paginaActual + 1, paginaActual === totalPages, false)
  );
  $cont.append(makeBtn("»", totalPages, paginaActual === totalPages, false));
}
function makeBtn(text, page, disabled, active) {
  const $btn = $("<button>")
    .text(text)
    .prop("disabled", disabled)
    .toggleClass("active", active);

  if (!disabled) {
    $btn.on("click", () => {
      paginaActual = page;
      datosPedidos(true);
    });
  }

  return $btn;
}*/
// 1) Primero definimos makeBtn como función declarada
function makeBtn(text, page, disabled, active) {
  const $btn = $("<button>")
    .text(text)
    .prop("disabled", disabled)
    .toggleClass("active", active);

  if (!disabled) {
    $btn.on("click", () => {
      paginaActual = page;
      obtenerClientes(true);   // o datosPedidos(true) si así la llamas
    });
  }

  return $btn;
}
// 2) Ahora buildPagination puede usar makeBtn sin problema
function buildPagination(total) {
    console.log("total : ", total);
    console.log("registrosPorPagina : ", registrosPorPagina);
  const totalPages = Math.ceil(total / registrosPorPagina);
  const maxButtons = 5;
  const $cont = $("#pagination").empty();
  console.log("totalPages: ", totalPages);

  if (totalPages <= 1) return;

  let start = Math.max(1, paginaActual - Math.floor(maxButtons / 2));
  let end   = start + maxButtons - 1;
  if (end > totalPages) {
    end   = totalPages;
    start = Math.max(1, end - maxButtons + 1);
  }

  // Flechas «Primera» y «Anterior»
  $cont.append(makeBtn("«", 1, paginaActual === 1, false));
  $cont.append(makeBtn("‹", paginaActual - 1, paginaActual === 1, false));

  // Botones numéricos
  for (let i = start; i <= end; i++) {
    $cont.append(makeBtn(i, i, false, i === paginaActual));
  }

  // Flechas «Siguiente» y «Última»
  $cont.append(makeBtn("›", paginaActual + 1, paginaActual === totalPages, false));
  $cont.append(makeBtn("»", totalPages, paginaActual === totalPages, false));

  console.log("paginaActual: ", paginaActual);
}

$(document).ready(function () {
  obtenerClientes();
  $(".cerrar-modal").click(function () {
    cerrarModal();
  });
});

function cerrarModal() {
  $("#usuarioModal").modal("hide");
}
$("#selectCantidad").on("change", function () {
  const seleccion = parseInt($(this).val(), 10);
  registrosPorPagina = isNaN(seleccion) ? registrosPorPagina : seleccion;
  paginaActual = 1; // volvemos a la primera página
  obtenerClientes(true); // limpia la tabla y carga sólo registrosPorPagina filas
});
