// Referencias a los elementos
const btnDatosGenerales = document.getElementById("btnDatosGenerales");
const btnDatosVentas = document.getElementById("btnDatosVentas");
const formDatosGenerales = document.getElementById("formDatosGenerales");
const formDatosVentas = document.getElementById("formDatosVentas");
let paginaActual = 1;
//const registrosPorPagina = 5; // Ajusta según convenga
let registrosPorPagina = 10; //10

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

//Funcion para buscar a un cliente
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
                
                // Crear las celdas directamente
                const tdClave = document.createElement("td");
                tdClave.textContent = cliente.CLAVE || "Sin clave";
                
                const tdNombre = document.createElement("td");
                tdNombre.textContent = cliente.NOMBRE || "Sin nombre";
                
                const tdSaldo = document.createElement("td");
                tdSaldo.style.textAlign = "right";
                tdSaldo.innerHTML = saldoFormateado;
                
                const tdEstado = document.createElement("td");
                tdEstado.style.textAlign = "center";
                tdEstado.innerHTML = estadoTimbrado;
                
                // Última venta
                const tdUltimaVenta = document.createElement("td");
                tdUltimaVenta.style.textAlign = "center";
                if (cliente.ULTIMA_VENTA) {
                    // Formatear la fecha de YYYY-MM-DD a DD/MM/YYYY
                    const fecha = cliente.ULTIMA_VENTA;
                    const fechaFormateada = fecha.split('-').reverse().join('/');
                    tdUltimaVenta.textContent = fechaFormateada;
                } else {
                    tdUltimaVenta.textContent = "-";
                }
                
                // Botón Estado de Cuenta Detallado
                const tdEstadoCuentaDetallado = document.createElement("td");
                tdEstadoCuentaDetallado.style.textAlign = "center";
                const btnEstadoCuentaDetallado = document.createElement("button");
                btnEstadoCuentaDetallado.className = "btn btn-sm btn-warning btnEstadoCuentaDetallado";
                btnEstadoCuentaDetallado.setAttribute("data-clave", cliente.CLAVE || "");
                btnEstadoCuentaDetallado.setAttribute("data-nombre", cliente.NOMBRE || "Sin nombre");
                btnEstadoCuentaDetallado.style.padding = "0.15rem 0.35rem";
                btnEstadoCuentaDetallado.style.fontSize = "0.75rem";
                btnEstadoCuentaDetallado.innerHTML = '<i class="bx bx-file-blank"></i> Ver';
                tdEstadoCuentaDetallado.appendChild(btnEstadoCuentaDetallado);
                
                // Botón Visualizar
                const tdVisualizar = document.createElement("td");
                const btnVisualizar = document.createElement("button");
                btnVisualizar.className = "btnVisualizarCliente";
                btnVisualizar.name = "btnVisualizarCliente";
                btnVisualizar.setAttribute("data-id", cliente.CLAVE);
                btnVisualizar.style.display = "inline-flex";
                btnVisualizar.style.alignItems = "center";
                btnVisualizar.style.padding = "0.25rem 0.5rem";
                btnVisualizar.style.fontSize = "0.8rem";
                btnVisualizar.style.fontFamily = "Lato";
                btnVisualizar.style.color = "#fff";
                btnVisualizar.style.backgroundColor = "#007bff";
                btnVisualizar.style.border = "none";
                btnVisualizar.style.borderRadius = "0.25rem";
                btnVisualizar.style.cursor = "pointer";
                btnVisualizar.style.transition = "background-color 0.3s ease";
                btnVisualizar.innerHTML = '<i class="fas fa-eye" style="margin-right: 0.25rem;"></i> Ver';
                tdVisualizar.appendChild(btnVisualizar);
                
                // Agregar todas las celdas a la fila
                row.appendChild(tdClave);
                row.appendChild(tdNombre);
                row.appendChild(tdSaldo);
                row.appendChild(tdEstado);
                row.appendChild(tdUltimaVenta);
                row.appendChild(tdEstadoCuentaDetallado);
                
                // Botón Estado de Cuenta
                const tdEstadoCuenta = document.createElement("td");
                tdEstadoCuenta.style.textAlign = "center";
                const btnEstadoCuenta = document.createElement("button");
                btnEstadoCuenta.className = "btn btn-sm btn-danger btnEstadoCuenta";
                btnEstadoCuenta.setAttribute("data-clave", cliente.CLAVE || "");
                btnEstadoCuenta.setAttribute("data-nombre", cliente.NOMBRE || "Sin nombre");
                btnEstadoCuenta.style.padding = "0.15rem 0.35rem";
                btnEstadoCuenta.style.fontSize = "0.75rem";
                btnEstadoCuenta.innerHTML = '<i class="bx bx-file-blank"></i> Ver';
                tdEstadoCuenta.appendChild(btnEstadoCuenta);
                
                row.appendChild(tdEstadoCuenta);
                row.appendChild(tdVisualizar);
                
                clientesTable.appendChild(row);
              });
              buildPagination(response.total);
              agregarEventosBotones();
              agregarEventosReportes();
            } else {
              const clientesTable = document.getElementById("datosClientes");
              clientesTable.innerHTML = `<tr><td colspan="8" style="text-align: center;">No hay datos disponibles</td></tr>`;
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
              document.getElementById("correo").value =
              cliente.EMAILPRED || "Sin correo";
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
  //Cierra el modal
  modal.hide();
}
//Funcion para obtener a todos los clientes disponibles de acuerdo a las validaciones correspondientes
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
              
              // Crear las celdas directamente
              const tdClave = document.createElement("td");
              tdClave.textContent = cliente.CLAVE || "Sin clave";
              
              const tdNombre = document.createElement("td");
              tdNombre.textContent = cliente.NOMBRE || "Sin nombre";
              
              const tdSaldo = document.createElement("td");
              tdSaldo.style.textAlign = "right";
              tdSaldo.innerHTML = saldoFormateado;
              
              const tdEstado = document.createElement("td");
              tdEstado.style.textAlign = "center";
              tdEstado.innerHTML = estadoTimbrado;
              
              // Última venta
              const tdUltimaVenta = document.createElement("td");
              tdUltimaVenta.style.textAlign = "center";
              if (cliente.ULTIMA_VENTA) {
                  // Formatear la fecha de YYYY-MM-DD a DD/MM/YYYY
                  const fecha = cliente.ULTIMA_VENTA;
                  const fechaFormateada = fecha.split('-').reverse().join('/');
                  tdUltimaVenta.textContent = fechaFormateada;
              } else {
                  tdUltimaVenta.textContent = "-";
              }
              
              // Botón Estado de Cuenta Detallado
              const tdEstadoCuentaDetallado = document.createElement("td");
              tdEstadoCuentaDetallado.style.textAlign = "center";
              const btnEstadoCuentaDetallado = document.createElement("button");
              btnEstadoCuentaDetallado.className = "btn btn-sm btn-warning btnEstadoCuentaDetallado";
              btnEstadoCuentaDetallado.setAttribute("data-clave", cliente.CLAVE || "");
              btnEstadoCuentaDetallado.setAttribute("data-nombre", cliente.NOMBRE || "Sin nombre");
              btnEstadoCuentaDetallado.style.padding = "0.15rem 0.35rem";
              btnEstadoCuentaDetallado.style.fontSize = "0.75rem";
              btnEstadoCuentaDetallado.innerHTML = '<i class="bx bx-file-blank"></i> Ver';
              tdEstadoCuentaDetallado.appendChild(btnEstadoCuentaDetallado);
              
              // Botón Visualizar
              const tdVisualizar = document.createElement("td");
              const btnVisualizar = document.createElement("button");
              btnVisualizar.className = "btnVisualizarCliente";
              btnVisualizar.name = "btnVisualizarCliente";
              btnVisualizar.setAttribute("data-id", cliente.CLAVE);
              btnVisualizar.style.display = "inline-flex";
              btnVisualizar.style.alignItems = "center";
              btnVisualizar.style.padding = "0.25rem 0.5rem";
              btnVisualizar.style.fontSize = "0.8rem";
              btnVisualizar.style.fontFamily = "Lato";
              btnVisualizar.style.color = "#fff";
              btnVisualizar.style.backgroundColor = "#007bff";
              btnVisualizar.style.border = "none";
              btnVisualizar.style.borderRadius = "0.25rem";
              btnVisualizar.style.cursor = "pointer";
              btnVisualizar.style.transition = "background-color 0.3s ease";
              btnVisualizar.innerHTML = '<i class="fas fa-eye" style="margin-right: 0.25rem;"></i> Ver';
              tdVisualizar.appendChild(btnVisualizar);
              
              // Agregar todas las celdas a la fila
              row.appendChild(tdClave);
              row.appendChild(tdNombre);
              row.appendChild(tdSaldo);
              row.appendChild(tdEstado);
              row.appendChild(tdUltimaVenta);
              row.appendChild(tdEstadoCuentaDetallado);
              
              // Botón Estado de Cuenta
              const tdEstadoCuenta = document.createElement("td");
              tdEstadoCuenta.style.textAlign = "center";
              const btnEstadoCuenta = document.createElement("button");
              btnEstadoCuenta.className = "btn btn-sm btn-danger btnEstadoCuenta";
              btnEstadoCuenta.setAttribute("data-clave", cliente.CLAVE || "");
              btnEstadoCuenta.setAttribute("data-nombre", cliente.NOMBRE || "Sin nombre");
              btnEstadoCuenta.style.padding = "0.15rem 0.35rem";
              btnEstadoCuenta.style.fontSize = "0.75rem";
              btnEstadoCuenta.innerHTML = '<i class="bx bx-file-blank"></i> Ver';
              tdEstadoCuenta.appendChild(btnEstadoCuenta);
              
              row.appendChild(tdEstadoCuenta);
              row.appendChild(tdVisualizar);
              
              clientesTable.appendChild(row);
            });
            buildPagination(response.total);
            agregarEventosBotones();
            agregarEventosReportes();
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

// Definimos makeBtn como función declarada
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
//buildPagination puede usar makeBtn sin problema
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

// Variable global para almacenar el cliente actual
let clienteActualReporte = null;
let nombreClienteActualReporte = null;

// Función para agregar eventos a los botones de reportes
function agregarEventosReportes() {
  // Remover event listeners anteriores para evitar duplicados
  document.querySelectorAll(".btnEstadoCuentaDetallado").forEach((boton) => {
    const nuevoBoton = boton.cloneNode(true);
    boton.parentNode.replaceChild(nuevoBoton, boton);
  });
  
  document.querySelectorAll(".btnEstadoCuenta").forEach((boton) => {
    const nuevoBoton = boton.cloneNode(true);
    boton.parentNode.replaceChild(nuevoBoton, boton);
  });

  // Botones de Estado de Cuenta Detallado
  document.querySelectorAll(".btnEstadoCuentaDetallado").forEach((boton) => {
    boton.addEventListener("click", function () {
      const clave = this.getAttribute("data-clave");
      const nombre = this.getAttribute("data-nombre");
      abrirModalEstadoCuentaDetallado(clave, nombre);
    });
  });

  // Botones de Estado de Cuenta
  document.querySelectorAll(".btnEstadoCuenta").forEach((boton) => {
    boton.addEventListener("click", function () {
      const clave = this.getAttribute("data-clave");
      const nombre = this.getAttribute("data-nombre");
      abrirModalEstadoCuenta(clave, nombre);
    });
  });
}

// Función para abrir el modal de Estado de Cuenta General
function abrirModalEstadoCuentaGeneral(claveCliente, nombreCliente) {
  clienteActualReporte = claveCliente;
  nombreClienteActualReporte = nombreCliente;
  
  document.getElementById("modalClienteNombreGeneral").textContent = nombreCliente || "Cliente";
  document.getElementById("filtroFechaInicioEstadoCuentaGeneral").value = "";
  document.getElementById("filtroFechaFinEstadoCuentaGeneral").value = "";
  
  const modal = new bootstrap.Modal(document.getElementById("modalEstadoCuentaGeneral"));
  modal.show();
  
  cargarEstadoCuentaGeneral();
}

// Función para cerrar el modal de Estado de Cuenta General
function cerrarModalEstadoCuentaGeneral() {
  const modal = bootstrap.Modal.getInstance(document.getElementById("modalEstadoCuentaGeneral"));
  if (modal) {
    modal.hide();
  }
}

// Función para cargar el Estado de Cuenta General
function cargarEstadoCuentaGeneral() {
  if (!clienteActualReporte) {
    return;
  }
  
  const tablaBody = document.getElementById("datosEstadoCuentaGeneral");
  const filtroFechaInicio = document.getElementById("filtroFechaInicioEstadoCuentaGeneral").value;
  const filtroFechaFin = document.getElementById("filtroFechaFinEstadoCuentaGeneral").value;
  
  tablaBody.innerHTML = `
    <tr>
      <td colspan="10" class="text-center">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Cargando...</span>
        </div>
      </td>
    </tr>
  `;
  
  $.post(
    "../Servidor/PHP/reportesGeneral.php",
    {
      numFuncion: "3",
      filtroFechaInicio: filtroFechaInicio,
      filtroFechaFin: filtroFechaFin,
      filtroCliente: clienteActualReporte,
    },
    function (response) {
      try {
        if (typeof response === "string") {
          response = JSON.parse(response);
        }
        
        if (response.success && response.data && response.data.length > 0) {
          tablaBody.innerHTML = "";
          
          const fragment = document.createDocumentFragment();
          response.data.forEach((reporte) => {
            const cargos = Number(reporte.CARGOS || 0);
            const abonos = Number(reporte.ABONOS || 0);
            const saldo = Number(reporte.SALDO || 0);
            
            const row = document.createElement("tr");
            row.innerHTML = `
              <td>${reporte.CLAVE || ""}</td>
              <td>${reporte.TIPO || ""}</td>
              <td>${reporte.CONCEPTO || ""}</td>
              <td>${reporte.DOCUMENTO || ""}</td>
              <td>${reporte.NUM || ""}</td>
              <td>${reporte.FECHA_APLICACION || ""}</td>
              <td>${reporte.FECHA_VENCIMIENTO || ""}</td>
              <td style="text-align:right;">${cargos.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })}</td>
              <td style="text-align:right;">${abonos.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })}</td>
              <td style="text-align:right;">${saldo.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })}</td>
            `;
            fragment.appendChild(row);
          });
          
          tablaBody.appendChild(fragment);
        } else {
          tablaBody.innerHTML = `
            <tr>
              <td colspan="10" class="text-center">No se encontraron registros</td>
            </tr>
          `;
        }
      } catch (error) {
        console.error("Error al procesar JSON:", error);
        tablaBody.innerHTML = `
          <tr>
            <td colspan="10" class="text-center text-danger">Error al cargar los datos</td>
          </tr>
        `;
      }
    },
    "json"
  ).fail(function (jqXHR, textStatus, errorThrown) {
    console.error("Error en la solicitud:", textStatus, errorThrown);
    tablaBody.innerHTML = `
      <tr>
        <td colspan="10" class="text-center text-danger">Error al cargar los datos</td>
      </tr>
    `;
  });
}

// Función para abrir el modal de Estado de Cuenta Detallado
function abrirModalEstadoCuentaDetallado(claveCliente, nombreCliente) {
  clienteActualReporte = claveCliente;
  nombreClienteActualReporte = nombreCliente;
  
  document.getElementById("modalClienteNombreDetallado").textContent = nombreCliente || "Cliente";
  document.getElementById("filtroFechaInicioEstadoCuentaDetallado").value = "";
  document.getElementById("filtroFechaFinEstadoCuentaDetallado").value = "";
  
  const modal = new bootstrap.Modal(document.getElementById("modalEstadoCuentaDetallado"));
  modal.show();
  
  cargarEstadoCuentaDetallado();
}

// Función para cerrar el modal de Estado de Cuenta Detallado
function cerrarModalEstadoCuentaDetallado() {
  const modal = bootstrap.Modal.getInstance(document.getElementById("modalEstadoCuentaDetallado"));
  if (modal) {
    modal.hide();
  }
}

// Función para cargar el Estado de Cuenta Detallado
function cargarEstadoCuentaDetallado() {
  if (!clienteActualReporte) {
    return;
  }
  
  const tablaBody = document.getElementById("datosEstadoCuentaDetallado");
  const filtroFechaInicio = document.getElementById("filtroFechaInicioEstadoCuentaDetallado").value;
  const filtroFechaFin = document.getElementById("filtroFechaFinEstadoCuentaDetallado").value;
  
  tablaBody.innerHTML = `
    <tr>
      <td colspan="10" class="text-center">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Cargando...</span>
        </div>
      </td>
    </tr>
  `;
  
  $.post(
    "../Servidor/PHP/reportesGeneral.php",
    {
      numFuncion: "5",
      filtroFechaInicio: filtroFechaInicio,
      filtroFechaFin: filtroFechaFin,
      filtroCliente: clienteActualReporte,
    },
    function (response) {
      try {
        if (typeof response === "string") {
          response = JSON.parse(response);
        }
        
        if (response.success && response.data && response.data.length > 0) {
          tablaBody.innerHTML = "";
          
          const fragment = document.createDocumentFragment();
          response.data.forEach((reporte) => {
            const cargo = reporte.CARGO !== null && reporte.CARGO !== "" ? Number(reporte.CARGO) : null;
            const abono = reporte.ABONO !== null && reporte.ABONO !== "" ? Number(reporte.ABONO) : null;
            const saldo = reporte.SALDO !== null && reporte.SALDO !== "" ? Number(reporte.SALDO) : null;
            
            const row = document.createElement("tr");
            row.innerHTML = `
              <td>${reporte.CLAVE || ""}</td>
              <td>${reporte.TIPO || ""}</td>
              <td>${reporte.CONCEPTO || ""}</td>
              <td>${reporte.DOCUMENTO || ""}</td>
              <td>${reporte.NUM || ""}</td>
              <td>${reporte.FECHA_APLICACION || ""}</td>
              <td>${reporte.FECHA_VENCIMIENTO || ""}</td>
              <td style="text-align:right;">${cargo !== null ? cargo.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' }) : ""}</td>
              <td style="text-align:right;">${abono !== null ? abono.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' }) : ""}</td>
              <td style="text-align:right;">${saldo !== null ? saldo.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' }) : ""}</td>
            `;
            fragment.appendChild(row);
          });
          
          tablaBody.appendChild(fragment);
        } else {
          tablaBody.innerHTML = `
            <tr>
              <td colspan="10" class="text-center">No se encontraron registros</td>
            </tr>
          `;
        }
      } catch (error) {
        console.error("Error al procesar JSON:", error);
        tablaBody.innerHTML = `
          <tr>
            <td colspan="10" class="text-center text-danger">Error al cargar los datos</td>
          </tr>
        `;
      }
    },
    "json"
  ).fail(function (jqXHR, textStatus, errorThrown) {
    console.error("Error en la solicitud:", textStatus, errorThrown);
    tablaBody.innerHTML = `
      <tr>
        <td colspan="10" class="text-center text-danger">Error al cargar los datos</td>
      </tr>
    `;
  });
}

// Función para abrir el modal de Cobranza
function abrirModalCobranza(claveCliente, nombreCliente) {
  clienteActualReporte = claveCliente;
  nombreClienteActualReporte = nombreCliente;
  
  document.getElementById("modalClienteNombreCobranza").textContent = nombreCliente || "Cliente";
  document.getElementById("filtroFechaInicioCobranza").value = "";
  document.getElementById("filtroFechaFinCobranza").value = "";
  
  const modal = new bootstrap.Modal(document.getElementById("modalCobranza"));
  modal.show();
  
  cargarCobranza();
}

// Función para cerrar el modal de Cobranza
function cerrarModalCobranza() {
  const modal = bootstrap.Modal.getInstance(document.getElementById("modalCobranza"));
  if (modal) {
    modal.hide();
  }
}

// Función para cargar Cobranza
function cargarCobranza() {
  if (!clienteActualReporte) {
    return;
  }
  
  const tablaBody = document.getElementById("datosCobranza");
  const filtroFechaInicio = document.getElementById("filtroFechaInicioCobranza").value;
  const filtroFechaFin = document.getElementById("filtroFechaFinCobranza").value;
  
  tablaBody.innerHTML = `
    <tr>
      <td colspan="13" class="text-center">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Cargando...</span>
        </div>
      </td>
    </tr>
  `;
  
  $.post(
    "../Servidor/PHP/reportesGeneral.php",
    {
      numFuncion: "4",
      filtroFechaInicio: filtroFechaInicio,
      filtroFechaFin: filtroFechaFin,
      filtroCliente: clienteActualReporte,
      filtroVendedor: "",
    },
    function (response) {
      try {
        if (typeof response === "string") {
          response = JSON.parse(response);
        }
        
        if (response.success && response.data && response.data.length > 0) {
          tablaBody.innerHTML = "";
          
          const fragment = document.createDocumentFragment();
          response.data.forEach((reporte) => {
            const cargos = Number(reporte.CARGOS || 0);
            const abonos = Number(reporte.ABONOS || 0);
            const saldo = Number(reporte.SALDO || 0);
            
            const row = document.createElement("tr");
            row.innerHTML = `
              <td>${reporte.CLAVE || ""}</td>
              <td>${reporte.NOMBRE_CLIENTE || ""}</td>
              <td>${reporte.TELEFONO_CLIENTE || ""}</td>
              <td>${reporte.TIPO || ""}</td>
              <td>${reporte.CONCEPTO || ""}</td>
              <td>${reporte.DOCUMENTO || ""}</td>
              <td>${reporte.NUM || ""}</td>
              <td>${reporte.FECHA_APLICACION || ""}</td>
              <td>${reporte.FECHA_VENCIMIENTO || ""}</td>
              <td style="text-align:right;">${cargos.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })}</td>
              <td style="text-align:right;">${abonos.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })}</td>
              <td style="text-align:right;">${saldo.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })}</td>
              <td>${reporte.MONEDA || ""}</td>
            `;
            fragment.appendChild(row);
          });
          
          tablaBody.appendChild(fragment);
        } else {
          tablaBody.innerHTML = `
            <tr>
              <td colspan="13" class="text-center">No se encontraron registros</td>
            </tr>
          `;
        }
      } catch (error) {
        console.error("Error al procesar JSON:", error);
        tablaBody.innerHTML = `
          <tr>
            <td colspan="13" class="text-center text-danger">Error al cargar los datos</td>
          </tr>
        `;
      }
    },
    "json"
  ).fail(function (jqXHR, textStatus, errorThrown) {
    console.error("Error en la solicitud:", textStatus, errorThrown);
    tablaBody.innerHTML = `
      <tr>
        <td colspan="13" class="text-center text-danger">Error al cargar los datos</td>
      </tr>
    `;
  });
}

// ==================== FUNCIONES PARA ESTADO DE CUENTA GENERAL ====================

function descargarPDFEstadoCuentaGeneral() {
  if (!clienteActualReporte) {
    Swal.fire({
      title: "Aviso",
      text: "No hay cliente seleccionado.",
      icon: "warning",
    });
    return;
  }
  
  const filtroFechaInicio = document.getElementById("filtroFechaInicioEstadoCuentaGeneral").value;
  const filtroFechaFin = document.getElementById("filtroFechaFinEstadoCuentaGeneral").value;
  
  // Descargar directamente sin mostrar spinner (el navegador manejará la descarga)
  window.location.href = `../Servidor/PHP/reportesEstadoCuenta.php?tipo=general&cliente=${encodeURIComponent(clienteActualReporte)}&fechaInicio=${encodeURIComponent(filtroFechaInicio)}&fechaFin=${encodeURIComponent(filtroFechaFin)}&accion=descargar`;
}

function enviarWhatsAppEstadoCuentaGeneral() {
  if (!clienteActualReporte) {
    Swal.fire({
      title: "Aviso",
      text: "No hay cliente seleccionado.",
      icon: "warning",
    });
    return;
  }
  
  const filtroFechaInicio = document.getElementById("filtroFechaInicioEstadoCuentaGeneral").value;
  const filtroFechaFin = document.getElementById("filtroFechaFinEstadoCuentaGeneral").value;
  
  Swal.fire({
    title: "Enviando por WhatsApp...",
    text: "Por favor espere",
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });
  
  $.post(
    "../Servidor/PHP/reportesEstadoCuenta.php",
    {
      tipo: "general",
      cliente: clienteActualReporte,
      fechaInicio: filtroFechaInicio,
      fechaFin: filtroFechaFin,
      accion: "whatsapp"
    },
    function (response) {
      Swal.close();
      if (response.success) {
        Swal.fire({
          title: "Éxito",
          text: response.message || "Reporte enviado por WhatsApp correctamente",
          icon: "success",
        });
      } else {
        Swal.fire({
          title: "Error",
          text: response.message || "Error al enviar por WhatsApp",
          icon: "error",
        });
      }
    },
    "json"
  ).fail(function () {
    Swal.close();
    Swal.fire({
      title: "Error",
      text: "Error al enviar por WhatsApp",
      icon: "error",
    });
  });
}

function enviarCorreoEstadoCuentaGeneral() {
  if (!clienteActualReporte) {
    Swal.fire({
      title: "Aviso",
      text: "No hay cliente seleccionado.",
      icon: "warning",
    });
    return;
  }
  
  const filtroFechaInicio = document.getElementById("filtroFechaInicioEstadoCuentaGeneral").value;
  const filtroFechaFin = document.getElementById("filtroFechaFinEstadoCuentaGeneral").value;
  
  Swal.fire({
    title: "Enviando por correo...",
    text: "Por favor espere",
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });
  
  $.post(
    "../Servidor/PHP/reportesEstadoCuenta.php",
    {
      tipo: "general",
      cliente: clienteActualReporte,
      fechaInicio: filtroFechaInicio,
      fechaFin: filtroFechaFin,
      accion: "correo"
    },
    function (response) {
      Swal.close();
      if (response.success) {
        Swal.fire({
          title: "Éxito",
          text: response.message || "Reporte enviado por correo correctamente",
          icon: "success",
        });
      } else {
        Swal.fire({
          title: "Error",
          text: response.message || "Error al enviar por correo",
          icon: "error",
        });
      }
    },
    "json"
  ).fail(function () {
    Swal.close();
    Swal.fire({
      title: "Error",
      text: "Error al enviar por correo",
      icon: "error",
    });
  });
}

// ==================== FUNCIONES PARA ESTADO DE CUENTA DETALLADO ====================

function descargarPDFEstadoCuentaDetallado() {
  if (!clienteActualReporte) {
    Swal.fire({
      title: "Aviso",
      text: "No hay cliente seleccionado.",
      icon: "warning",
    });
    return;
  }
  
  const filtroFechaInicio = document.getElementById("filtroFechaInicioEstadoCuentaDetallado").value;
  const filtroFechaFin = document.getElementById("filtroFechaFinEstadoCuentaDetallado").value;
  
  // Descargar directamente sin mostrar spinner (el navegador manejará la descarga)
  window.location.href = `../Servidor/PHP/reportesEstadoCuenta.php?tipo=detallado&cliente=${encodeURIComponent(clienteActualReporte)}&fechaInicio=${encodeURIComponent(filtroFechaInicio)}&fechaFin=${encodeURIComponent(filtroFechaFin)}&accion=descargar`;
}

function enviarWhatsAppEstadoCuentaDetallado() {
  if (!clienteActualReporte) {
    Swal.fire({
      title: "Aviso",
      text: "No hay cliente seleccionado.",
      icon: "warning",
    });
    return;
  }
  
  const filtroFechaInicio = document.getElementById("filtroFechaInicioEstadoCuentaDetallado").value;
  const filtroFechaFin = document.getElementById("filtroFechaFinEstadoCuentaDetallado").value;
  
  Swal.fire({
    title: "Enviando por WhatsApp...",
    text: "Por favor espere",
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });
  
  $.post(
    "../Servidor/PHP/reportesEstadoCuenta.php",
    {
      tipo: "detallado",
      cliente: clienteActualReporte,
      fechaInicio: filtroFechaInicio,
      fechaFin: filtroFechaFin,
      accion: "whatsapp"
    },
    function (response) {
      Swal.close();
      if (response.success) {
        Swal.fire({
          title: "Éxito",
          text: response.message || "Reporte enviado por WhatsApp correctamente",
          icon: "success",
        });
      } else {
        Swal.fire({
          title: "Error",
          text: response.message || "Error al enviar por WhatsApp",
          icon: "error",
        });
      }
    },
    "json"
  ).fail(function () {
    Swal.close();
    Swal.fire({
      title: "Error",
      text: "Error al enviar por WhatsApp",
      icon: "error",
    });
  });
}

function enviarCorreoEstadoCuentaDetallado() {
  if (!clienteActualReporte) {
    Swal.fire({
      title: "Aviso",
      text: "No hay cliente seleccionado.",
      icon: "warning",
    });
    return;
  }
  
  const filtroFechaInicio = document.getElementById("filtroFechaInicioEstadoCuentaDetallado").value;
  const filtroFechaFin = document.getElementById("filtroFechaFinEstadoCuentaDetallado").value;
  
  Swal.fire({
    title: "Enviando por correo...",
    text: "Por favor espere",
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });
  
  $.post(
    "../Servidor/PHP/reportesEstadoCuenta.php",
    {
      tipo: "detallado",
      cliente: clienteActualReporte,
      fechaInicio: filtroFechaInicio,
      fechaFin: filtroFechaFin,
      accion: "correo"
    },
    function (response) {
      Swal.close();
      if (response.success) {
        Swal.fire({
          title: "Éxito",
          text: response.message || "Reporte enviado por correo correctamente",
          icon: "success",
        });
      } else {
        Swal.fire({
          title: "Error",
          text: response.message || "Error al enviar por correo",
          icon: "error",
        });
      }
    },
    "json"
  ).fail(function () {
    Swal.close();
    Swal.fire({
      title: "Error",
      text: "Error al enviar por correo",
      icon: "error",
    });
  });
}

// ==================== FUNCIONES PARA COBRANZA ====================

function descargarPDFCobranza() {
  if (!clienteActualReporte) {
    Swal.fire({
      title: "Aviso",
      text: "No hay cliente seleccionado.",
      icon: "warning",
    });
    return;
  }
  
  const filtroFechaInicio = document.getElementById("filtroFechaInicioCobranza").value;
  const filtroFechaFin = document.getElementById("filtroFechaFinCobranza").value;
  
  // Descargar directamente sin mostrar spinner (el navegador manejará la descarga)
  window.location.href = `../Servidor/PHP/reportesEstadoCuenta.php?tipo=cobranza&cliente=${encodeURIComponent(clienteActualReporte)}&fechaInicio=${encodeURIComponent(filtroFechaInicio)}&fechaFin=${encodeURIComponent(filtroFechaFin)}&accion=descargar`;
}

function enviarWhatsAppCobranza() {
  if (!clienteActualReporte) {
    Swal.fire({
      title: "Aviso",
      text: "No hay cliente seleccionado.",
      icon: "warning",
    });
    return;
  }
  
  const filtroFechaInicio = document.getElementById("filtroFechaInicioCobranza").value;
  const filtroFechaFin = document.getElementById("filtroFechaFinCobranza").value;
  
  Swal.fire({
    title: "Enviando por WhatsApp...",
    text: "Por favor espere",
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });
  
  $.post(
    "../Servidor/PHP/reportesEstadoCuenta.php",
    {
      tipo: "cobranza",
      cliente: clienteActualReporte,
      fechaInicio: filtroFechaInicio,
      fechaFin: filtroFechaFin,
      accion: "whatsapp"
    },
    function (response) {
      Swal.close();
      if (response.success) {
        Swal.fire({
          title: "Éxito",
          text: response.message || "Reporte enviado por WhatsApp correctamente",
          icon: "success",
        });
      } else {
        Swal.fire({
          title: "Error",
          text: response.message || "Error al enviar por WhatsApp",
          icon: "error",
        });
      }
    },
    "json"
  ).fail(function () {
    Swal.close();
    Swal.fire({
      title: "Error",
      text: "Error al enviar por WhatsApp",
      icon: "error",
    });
  });
}

function enviarCorreoCobranza() {
  if (!clienteActualReporte) {
    Swal.fire({
      title: "Aviso",
      text: "No hay cliente seleccionado.",
      icon: "warning",
    });
    return;
  }
  
  const filtroFechaInicio = document.getElementById("filtroFechaInicioCobranza").value;
  const filtroFechaFin = document.getElementById("filtroFechaFinCobranza").value;
  
  Swal.fire({
    title: "Enviando por correo...",
    text: "Por favor espere",
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });
  
  $.post(
    "../Servidor/PHP/reportesEstadoCuenta.php",
    {
      tipo: "cobranza",
      cliente: clienteActualReporte,
      fechaInicio: filtroFechaInicio,
      fechaFin: filtroFechaFin,
      accion: "correo"
    },
    function (response) {
      Swal.close();
      if (response.success) {
        Swal.fire({
          title: "Éxito",
          text: response.message || "Reporte enviado por correo correctamente",
          icon: "success",
        });
      } else {
        Swal.fire({
          title: "Error",
          text: response.message || "Error al enviar por correo",
          icon: "error",
        });
      }
    },
    "json"
  ).fail(function () {
    Swal.close();
    Swal.fire({
      title: "Error",
      text: "Error al enviar por correo",
      icon: "error",
    });
  });
}

// ==================== FUNCIONES PARA ESTADO DE CUENTA ====================

function abrirModalEstadoCuenta(claveCliente, nombreCliente) {
  clienteActualReporte = claveCliente;
  nombreClienteActualReporte = nombreCliente;
  
  document.getElementById("modalClienteNombreEstadoCuenta").textContent = nombreCliente || "Cliente";
  
  const modal = new bootstrap.Modal(document.getElementById("modalEstadoCuenta"));
  modal.show();
  
  cargarEstadoCuenta();
}

function cerrarModalEstadoCuenta() {
  const modal = bootstrap.Modal.getInstance(document.getElementById("modalEstadoCuenta"));
  if (modal) {
    modal.hide();
  }
}

function cargarEstadoCuenta() {
  if (!clienteActualReporte) {
    return;
  }
  
  const tablaBody = document.getElementById("datosEstadoCuenta");
  
  tablaBody.innerHTML = `
    <tr>
      <td colspan="8" class="text-center">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Cargando...</span>
        </div>
      </td>
    </tr>
  `;
  
  $.post(
    "../Servidor/PHP/reportesGeneral.php",
    {
      numFuncion: "6",
      filtroCliente: clienteActualReporte,
    },
    function (response) {
      try {
        if (typeof response === "string") {
          response = JSON.parse(response);
        }
        
        if (response.success && response.data && response.data.length > 0) {
          tablaBody.innerHTML = "";
          
          const fragment = document.createDocumentFragment();
          response.data.forEach((reporte) => {
            const montoOriginal = Number(reporte.MONTO_ORIGINAL || 0);
            const montoPagado = Number(reporte.MONTO_PAGADO || 0);
            const saldoRestante = Number(reporte.SALDO_RESTANTE || 0);
            
            // Determinar color según estado
            let estadoClass = "";
            if (reporte.ESTADO_CUENTA === "VENCIDA") {
              estadoClass = "text-danger fw-bold";
            } else if (reporte.ESTADO_CUENTA === "PENDIENTE") {
              estadoClass = "text-warning fw-bold";
            } else {
              estadoClass = "text-success";
            }
            
            const row = document.createElement("tr");
            row.innerHTML = `
              <td>${reporte.FACTURA || ""}</td>
              <td>${reporte.FECHA_APLICACION || ""}</td>
              <td>${reporte.FECHA_VENCIMIENTO || ""}</td>
              <td style="text-align:right;">${montoOriginal.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })}</td>
              <td style="text-align:right;">${montoPagado.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })}</td>
              <td style="text-align:right;">${saldoRestante.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })}</td>
              <td>${reporte.MONEDA || ""}</td>
              <td class="${estadoClass}">${reporte.ESTADO_CUENTA || ""}</td>
            `;
            fragment.appendChild(row);
          });
          
          tablaBody.appendChild(fragment);
        } else {
          tablaBody.innerHTML = `
            <tr>
              <td colspan="8" class="text-center">No se encontraron facturas pendientes</td>
            </tr>
          `;
        }
      } catch (error) {
        console.error("Error al procesar JSON:", error);
        tablaBody.innerHTML = `
          <tr>
            <td colspan="8" class="text-center text-danger">Error al cargar los datos</td>
          </tr>
        `;
      }
    },
    "json"
  ).fail(function (jqXHR, textStatus, errorThrown) {
    console.error("Error en la solicitud:", textStatus, errorThrown);
    tablaBody.innerHTML = `
      <tr>
        <td colspan="8" class="text-center text-danger">Error al cargar los datos</td>
      </tr>
    `;
  });
}

function descargarPDFEstadoCuenta() {
  if (!clienteActualReporte) {
    Swal.fire({
      title: "Aviso",
      text: "No hay cliente seleccionado.",
      icon: "warning",
    });
    return;
  }
  
  window.location.href = `../Servidor/PHP/reportesEstadoCuenta.php?tipo=facturasnopagadas&cliente=${encodeURIComponent(clienteActualReporte)}&accion=descargar`;
}

function enviarWhatsAppEstadoCuenta() {
  if (!clienteActualReporte) {
    Swal.fire({
      title: "Aviso",
      text: "No hay cliente seleccionado.",
      icon: "warning",
    });
    return;
  }
  
  Swal.fire({
    title: "Enviando por WhatsApp...",
    text: "Por favor espere",
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });
  
  $.post(
    "../Servidor/PHP/reportesEstadoCuenta.php",
    {
      tipo: "facturasnopagadas",
      cliente: clienteActualReporte,
      accion: "whatsapp"
    },
    function (response) {
      Swal.close();
      if (response.success) {
        Swal.fire({
          title: "Éxito",
          text: response.message || "Reporte enviado por WhatsApp correctamente",
          icon: "success",
        });
      } else {
        Swal.fire({
          title: "Error",
          text: response.message || "Error al enviar por WhatsApp",
          icon: "error",
        });
      }
    },
    "json"
  ).fail(function () {
    Swal.close();
    Swal.fire({
      title: "Error",
      text: "Error al enviar por WhatsApp",
      icon: "error",
    });
  });
}

function enviarCorreoEstadoCuenta() {
  if (!clienteActualReporte) {
    Swal.fire({
      title: "Aviso",
      text: "No hay cliente seleccionado.",
      icon: "warning",
    });
    return;
  }
  
  Swal.fire({
    title: "Enviando por correo...",
    text: "Por favor espere",
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });
  
  $.post(
    "../Servidor/PHP/reportesEstadoCuenta.php",
    {
      tipo: "facturasnopagadas",
      cliente: clienteActualReporte,
      accion: "correo"
    },
    function (response) {
      Swal.close();
      if (response.success) {
        Swal.fire({
          title: "Éxito",
          text: response.message || "Reporte enviado por correo correctamente",
          icon: "success",
        });
      } else {
        Swal.fire({
          title: "Error",
          text: response.message || "Error al enviar por correo",
          icon: "error",
        });
      }
    },
    "json"
  ).fail(function () {
    Swal.close();
    Swal.fire({
      title: "Error",
      text: "Error al enviar por correo",
      icon: "error",
    });
  });
}
