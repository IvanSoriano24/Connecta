const noEmpresa = sessionStorage.getItem("noEmpresaSeleccionada");
let partidasData = []; // Este contiene las partidas actuales del formulario

function agregarEventosBotones() {
  const botonesVer = document.querySelectorAll(".btnVerPedido");
  botonesVer.forEach((boton) => {
    boton.addEventListener("click", async function () {
      /*const pedidoID = this.dataset.id; // Obtener el ID del pedido
            console.log("Redirigiendo con pedidoID:", pedidoID);
            window.location.href = "verRemision.php?pedidoID=" + pedidoID;*/
      const pedidoID = this.dataset.id; // Obtener el ID del pedido
      window.location.href = "verRemision.php?pedidoID=" + pedidoID;
    });
  });
  const botonesFacturar = document.querySelectorAll(".btnFacturar");
  //const rowActual = this.dataset.row;
  botonesFacturar.forEach((boton) => {
    boton.addEventListener("click", async function () {
      const pedidoID = this.dataset.id; // Obtener el ID del pedido
      try {
        //Verifica si ya fue facturada
        const res = await verificarPedido(pedidoID);
        if (res.success) {
          //Si ya fue facturada, muestra este mensaje
          Swal.fire({
            title: "Aviso",
            text: "Esta Remision ya ha Sido Facturada",
            icon: "warning",
            confirmButtonText: "Entendido",
          });
        } else if (res.fail) {
          //Si no esta facturada, realizara la facturacion
          /*Swal.fire({
                        title: "Aviso",
                        text: "Funcion en Construccion",
                        icon: "error",
                        confirmButtonText: "Entendido",
                    });*/
          facturarRemision(pedidoID); // Llama a la función para facturar la remision
        } else {
          console.error("Respuesta inesperada:", res);
        }
      } catch (error) {
        console.error("Error al verificar el pedido:", error);
        Swal.fire({
          title: "Aviso",
          text: "Hubo un problema al verificar el pedido",
          icon: "error",
          confirmButtonText: "Entendido",
        });
      }
    });
  });
}
function facturarRemision(pedidoID) {
  //Llamada al servidor con el id de la remision
  $.post(
    "../Servidor/PHP/remision.php",
    { numFuncion: "9", pedidoID: pedidoID },
    function (response) {
      try {
        if (typeof response === "string") {
          response = JSON.parse(response);
        }
        // Localizamos la fila y la celda del icono de estado
        const $fila        = $(`button.btnFacturar[data-id="${pedidoID}"]`).closest("tr");
        const $celdaIcono  = $fila.find(".estadoFactura ion-icon");

        if (response.success) {
          //Si la factura fue exitosa, mostrar mensaje
          Swal.fire({
            title: "Facturado",
            text:  "La remisión ha sido facturada correctamente",
            icon:  "success",
            confirmButtonText: "Entendido",
          }).then(() => {
            datosPedidos(); // actualiza la tabla
          });

        } else {
          //Sino fue exitosa, mostrar advertencia
          Swal.fire({
            title: "Aviso",
            //text:  "No se pudo facturar la remisión",
            text:  response.message || "No se pudo facturar la remisión",
            icon:  "warning",
            confirmButtonText: "Entendido",
          }).then(() => {
            // (Opcional) Si se duplica los botones
            // $fila.find(".estadoFactura .btn-danger").remove();

            //Poner el icono en rojo
            $celdaIcono.css("color", "red");

            // 2) Añadimos un botón de errores para mostrarlos en el modal
            /*const $btnErrores = $(`<td>
              <button class="btn btn-sm btn-danger ms-2" title="Ver errores">
                <i class="bi bi-exclamation-triangle-fill"></i>
              </button>
              </td>
            `).appendTo( $celdaIcono.parent() )
             .on("click", () => {
               mostrarModalErrores(response.message);
             });*/


          });
        }
      } catch (error) {
        console.error("Error al procesar la respuesta JSON:", error);
      }
    }
  ).fail(function (jqXHR, textStatus, errorThrown) {
    Swal.fire({
      title: "Aviso",
      text:  "Hubo un problema al intentar facturar la remisión",
      icon:  "warning",
      confirmButtonText: "Entendido",
    });
    console.log("Detalles del error:", jqXHR.responseText);
  });
}

function mostrarModalErrores(message){
  //Abir modal
  $("#modalErrores").modal("show");

}
function cerrarModalErrores(){
 $("#modalErrores").modal("hide"); // Cierra el modal usando Bootstrap
  $("#modalErrores").attr("aria-hidden", "true");
  // Eliminar el atributo inert del fondo al cerrar
  $(".modal-backdrop").removeAttr("inert");
}
function verificarPedido(pedidoID) {
  return new Promise((resolve, reject) => {
    $.post(
      "../Servidor/PHP/remision.php",
      { numFuncion: "8", pedidoID: pedidoID },
      function (response) {
        try {
          // Si se configuró dataType "json" en $.post, response ya es un objeto.
          // Si no, se puede verificar:
          if (typeof response === "string") {
            response = JSON.parse(response);
          }
          resolve(response);
        } catch (error) {
          reject("Error al parsear la respuesta: " + error);
        }
      }
    ).fail(function (jqXHR, textStatus, errorThrown) {
      reject("Error en la solicitud AJAX: " + errorThrown);
    });
  });
}
// CARGAR Los Datos
// Función para mostrar mensaje cuando no hay datos
function mostrarSinDatos() {
  const pedidosTable = document.getElementById("datosPedidos");
  if (!pedidosTable) {
    console.error("No se encontró el elemento con id 'datosPedidos'");
    return;
  }
  pedidosTable.innerHTML = "";
  const row = document.createElement("tr");
  // Si cuentas con un <thead>, define el número de columnas, de lo contrario, usa un valor fijo
  const numColumns = pedidosTable.querySelector("thead")
    ? pedidosTable.querySelector("thead").rows[0].cells.length
    : 13;
  row.innerHTML = `<td colspan="${numColumns}" style="text-align: center;">No hay datos disponibles</td>`;
  pedidosTable.appendChild(row);
}
/***********************************************************************/
function makeBtn(text, page, disabled, active) {
  const $btn = $("<button>")
    .text(text)
    .prop("disabled", disabled)
    .toggleClass("active", active);

  if (!disabled) {
    $btn.on("click", () => {
      paginaActual = page;
      datosPedidos(true); // o datosPedidos(true) si así la llamas
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
  let end = start + maxButtons - 1;
  if (end > totalPages) {
    end = totalPages;
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
  $cont.append(
    makeBtn("›", paginaActual + 1, paginaActual === totalPages, false)
  );
  $cont.append(makeBtn("»", totalPages, paginaActual === totalPages, false));

  console.log("paginaActual: ", paginaActual);
}
/***********************************************************************/
// Función para cargar los pedidos con el filtro seleccionado y guardar el filtro en localStorage
function cargarPedidos(estadoRemision, filtroFecha) {
  document.getElementById("searchTerm").value = "";
  // Guarda el filtro seleccionado
  localStorage.setItem("filtroSeleccionado", filtroFecha);
  localStorage.setItem("estadoRemision", estadoRemision);
  /*let filtroFecha = localStorage.getItem("filtroSeleccionado") || "Hoy";
    let estadoRemision = localStorage.getItem("estadoRemision") || "Activos";*/
  //console.log("Cargando pedidos con filtro:", filtroFecha);

  // Asegúrate de que la variable noEmpresa esté definida
  if (typeof noEmpresa === "undefined") {
    console.error("La variable noEmpresa no está definida");
    return;
  }

  $.post(
    "../Servidor/PHP/remision.php",
    {
      numFuncion: "2",
      noEmpresa: noEmpresa,
      filtroFecha: filtroFecha,
      estadoPedido: estadoRemision,
      filtroVendedor: filtroVendedor,
      pagina: paginaActual,
      porPagina: registrosPorPagina,
    },
    function (response) {
      console.log("Respuesta del servidor:", response);
      try {
        if (typeof response === "string") {
          response = JSON.parse(response);
        }
        if (response && response.success && response.data) {
          console.log("Datos: ", response);
          let pedidos = response.data;
          //console.log("Pedidos recibidos:", pedidos);
          // Ordenamos los pedidos (de mayor a menor clave)
          pedidos = pedidos.sort((a, b) => {
            const claveA = parseInt(a.Clave, 10) || 0;
            const claveB = parseInt(b.Clave, 10) || 0;
            return claveB - claveA;
          });
          datosPedidos(true);
        } else {
          console.warn(
            "No se recibieron datos o se devolvió un error:",
            response.message
          );
          mostrarSinDatos();
        }
      } catch (error) {
        console.error("Error al procesar la respuesta JSON:", error);
      }
    },
    "json"
  ).fail(function (jqXHR, textStatus, errorThrown) {
    console.error("Error en la solicitud:", textStatus, errorThrown);
  });
}

// Variables globales de paginación
let paginaActual = 1;
//const registrosPorPagina = 5; // Ajusta según convenga
let registrosPorPagina = 10; // Ajusta según convenga

// Función para cargar los pedidos con paginación.
// El parámetro "limpiarTabla" indica si se reinicia la tabla (true en carga inicial o al cambiar filtro)
// o se agregan filas al final (false al hacer "Mostrar más").
function datosPedidos(limpiarTabla = true) {
  // Recupera el filtro guardado o usa "Hoy" como valor predeterminado
  let filtroFecha = localStorage.getItem("filtroSeleccionado") || "Hoy";
  let estadoRemision = localStorage.getItem("estadoRemision") || "Vendidos";
  const pedidosTable = document.getElementById("datosPedidos");
  const numColumns = 12; // Número de columnas de tu tabla

  // Código del spinner (puedes reemplazarlo por el que prefieras)
  const spinnerHTML = `
        <svg viewBox="25 25 50 50" style="width:40px;height:40px;">
            <circle r="20" cy="50" cx="50"></circle>
        </svg>
    `;
  // Construir una fila con un spinner en cada celda
  let spinnerRow = "<tr>";
  for (let i = 0; i < numColumns; i++) {
    spinnerRow += `<td style="text-align: center;">${spinnerHTML}</td>`;
  }
  spinnerRow += "</tr>";

  // Si se debe limpiar la tabla, se reemplaza el contenido; si no, se agrega al final.
  if (limpiarTabla) {
    pedidosTable.innerHTML = spinnerRow;
  } else {
    pedidosTable.insertAdjacentHTML("beforeend", spinnerRow);
  }

  $.post(
    "../Servidor/PHP/remision.php",
    {
      numFuncion: "2",
      noEmpresa: noEmpresa,
      filtroFecha: filtroFecha,
      estadoPedido: estadoRemision,
      filtroVendedor: filtroVendedor,
      pagina: paginaActual,
      porPagina: registrosPorPagina,
    },
    function (response) {
      try {
        // Si la respuesta es una cadena, la parseamos
        if (typeof response === "string") {
          response = JSON.parse(response);
        }
        // Verificamos que la respuesta sea un objeto válido
        if (typeof response === "object" && response !== null) {
          // Limpiar la fila de spinner
          if (limpiarTabla) {
            pedidosTable.innerHTML = "";
          } else {
            // Remover la última fila (spinner) si se agregó al final
            const lastRow = pedidosTable.lastElementChild;
            if (lastRow) {
              pedidosTable.removeChild(lastRow);
            }
          }

          if (response.success && response.data) {
            let pedidos = response.data;
            // Ordenar pedidos por clave en orden descendente
            pedidos = pedidos.sort((a, b) => {
              const claveA = parseInt(a.Clave, 10) || 0;
              const claveB = parseInt(b.Clave, 10) || 0;
              return claveB - claveA;
            });

            // Crear un DocumentFragment para acumular las filas
            const fragment = document.createDocumentFragment();

            pedidos.forEach((pedido) => {
              const row = document.createElement("tr");
              const subtotalText = pedido.Subtotal
                ? `$${Number(pedido.Subtotal).toLocaleString("es-MX", {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                  })}`
                : "Sin subtotal";
              const importeText = pedido.ImporteTotal
                ? `$${Number(pedido.ImporteTotal).toLocaleString("es-MX", {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                  })}`
                : "Sin importe";
              /*const estadoFactura = pedido.DOC_SIG
                ? "<i class='bx bx-check-square' style='color: green; display: block; margin: 0 auto;'></i>"
                : "<i class='bx bx-check-square' style='color: gray; display: block; margin: 0 auto;'></i>"; // Centrado de la palomita con display: block y margin: 0 auto*/
              const estadoFactura = pedido.DOC_SIG
                ? `<ion-icon name="document-sharp" style="color:green;display:block;margin:0 auto;"></ion-icon>`
                : `<ion-icon name="document-sharp" style="color:gray; display:block;margin:0 auto;"></ion-icon>`;

              row.innerHTML = `
                                <td>${pedido.Tipo || "Sin tipo"}</td>
                                <td>${pedido.Clave || "Sin nombre"}</td>
                                <td >${pedido.Cliente || "Sin cliente"}</td>
                                <td>${pedido.Nombre || "Sin nombre"}</td>
                                <td>${pedido.Estatus || "0"}</td>
                                <td>${
                                  pedido.FechaElaboracion || "Sin fecha"
                                }</td>
                                <td style="text-align: right;">${subtotalText}</td>
                                <!--<td style="text-align: right;">${
                                  pedido.TotalComisiones
                                    ? `$${parseFloat(
                                        pedido.TotalComisiones
                                      ).toFixed(2)}`
                                    : "Sin Comisiones"
                                }</td>-->
                                <td style="text-align: right;">${importeText}</td>
                               <td class="nombreVendedor">${
                                 pedido.NombreVendedor || "Sin vendedor"
                               }
                                </td>
                                <td>
                                    <button class="btnVerPedido" name="btnVerPedido" data-id="${
                                      pedido.Clave
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
                                        transition: background-color 0.3s ease;">
                                        <i class="fas fa-eye" style="margin-right: 0.5rem;"></i> Ver Remisión
                                    </button>
                                </td>
                                <td>
                                    <button class="btnFacturar" name="btnFacturar" data-id="${
                                      pedido.Clave
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
                                        transition: background-color 0.3s ease;">
                                        Facturar
                                        <!--<i class="icon icon-bill" data-theme="outline" style="margin-right:.5rem;"></i>-->
                                    </button>
                                </td>
                                <td class="estadoFactura" name="estadoFactura" style="text-align: center;">${estadoFactura}</td> <!-- Centrar la palomita -->
                            `;
              fragment.appendChild(row);
            });

            // Agregar todas las filas de una sola vez
            pedidosTable.appendChild(fragment);
            buildPagination(response.total);

            // Si se retornaron menos registros que el límite, ocultamos el botón "Mostrar más"
            if (pedidos.length < registrosPorPagina) {
              //document.getElementById("btnMostrarMas").style.display = "none";
            } else {
              //document.getElementById("btnMostrarMas").style.display = "block";
            }

            // Llama a la función que asigna eventos a los botones, si está definida
            if (typeof agregarEventosBotones === "function") {
              agregarEventosBotones();
            }
          } else {
            // Si no hay datos, limpiar la tabla y mostrar un mensaje
            pedidosTable.innerHTML = `<tr><td colspan="${numColumns}" style="text-align: center;">No hay datos disponibles</td></tr>`;
            //document.getElementById("btnMostrarMas").style.display = "none";
            console.warn(
              "No se recibieron datos o se devolvió un error:",
              response.message
            );
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

// Función para obtener el siguiente folio
function obtenerDatosPedido(pedidoID) {
  $.post(
    "../Servidor/PHP/remision.php",
    {
      numFuncion: 3, // Función para obtener el pedido por ID
      pedidoID: pedidoID,
    },
    function (response) {
      console.log("Respuesta cruda:", response); // 👈 Imprime lo que llega
      if (response.success) {
        const pedido = response.pedido;
        console.log("Datos del pedido:", pedido);

        // Cargar datos del cliente

        document.getElementById("nombre").value = pedido.NOMBRE_CLIENTE || "";
        document.getElementById("rfc").value = pedido.RFC || "";
        document.getElementById("calle").value = pedido.CALLE || "";
        document.getElementById("numE").value = pedido.NUMEXT || "";
        document.getElementById("colonia").value = pedido.COLONIA || "";
        document.getElementById("codigoPostal").value = pedido.CODIGO || "";
        document.getElementById("pais").value = pedido.PAIS || "";
        document.getElementById("condicion").value = pedido.CONDICION || "";
        document.getElementById("almacen").value = pedido.NUM_ALMA || "";
        document.getElementById("comision").value = pedido.COM_TOT || "";
        document.getElementById("diaAlta").value = pedido.FECHA_DOC || "";
        document.getElementById("entrega").value = pedido.FECHA_ENT || "";
        document.getElementById("numero").value = pedido.FOLIO || "";

        //document.getElementById("enviar").value = pedido.CALLE_ENVIO || "";
        document.getElementById("descuentoCliente").value =
          pedido.DESCUENTO || "";
        document.getElementById("cliente").value = pedido.CLAVE || "";
        //document.getElementById("descuentofin").value = pedido.DES_FIN || "";
        document.getElementById("cliente").value = pedido.CVE_CLPV || "";
        document.getElementById("supedido").value = pedido.CONDICION || "";
        //document.getElementById("esquema").value = pedido.CONDICION || "";

        // Actualizar estado de cliente seleccionado en sessionStorage
        sessionStorage.setItem("clienteSeleccionado", true);

        // Cargar las partidas existentes
        //cargarPartidas(pedido.partidas);
        //alert("Datos del pedido cargados con éxito");

        console.log("Datos del pedido cargados correctamente.");

        $("#datosEnvio").prop("disabled", false);
        obtenerDatosEnvioEditar(pedido.DAT_ENVIO); // Función para cargar partidas del pedido
      } else {
        Swal.fire({
          title: "Aviso",
          text: "No se pudo cargar el pedido.",
          icon: "warning",
          confirmButtonText: "Aceptar",
        });
        //alert("No se pudo cargar el pedido: " + response.message);
      }
    },
    "json"
  ).fail(function (jqXHR, textStatus, errorThrown) {
    //console.log(errorThrown);
    Swal.fire({
      title: "Aviso",
      text: "Error al cargar el pedido.",
      icon: "error",
      confirmButtonText: "Aceptar",
    });
    //alert("Error al cargar el pedido: " + textStatus + " " + errorThrown);
    console.log("Error al cargar el pedido: " + textStatus + " " + errorThrown);
  });
}
function obtenerDatosEnvioEditar(envioID) {
  //
  $("#datosEnvio").prop("disabled", false);
  $("#selectDatosEnvio").prop("disabled", true);

  $.post(
    "../Servidor/PHP/clientes.php",
    {
      numFuncion: 11, // Función para obtener el pedido por ID
      envioID: envioID,
    },
    function (response) {
      if (response.success) {
        //console.log("Respuesta: ", response.data);
        const pedido = response.data;

        document.getElementById("enviar").value = pedido.CALLE || "";
      } else {
        Swal.fire({
          title: "Aviso",
          text: "No se Pudo cargar los Datos de Envio.",
          icon: "warning",
          confirmButtonText: "Aceptar",
        });
        //alert("No se pudo cargar el pedido: " + response.message);
      }
    },
    "json"
  ).fail(function (jqXHR, textStatus, errorThrown) {
    //console.log(errorThrown);
    Swal.fire({
      title: "Aviso",
      text: "Error al cargar el pedido.",
      icon: "error",
      confirmButtonText: "Aceptar",
    });
    //alert("Error al cargar el pedido: " + textStatus + " " + errorThrown);
    console.log("Error al cargar el pedido: " + textStatus + " " + errorThrown);
  });
}
function obtenerEstadosEdit(estadoSeleccionado, municipioSeleccionado) {
  $.ajax({
    url: "../Servidor/PHP/remision.php",
    method: "POST",
    data: { numFuncion: "7", estadoSeleccionado: estadoSeleccionado },
    dataType: "json",
    success: function (resEstado) {
      const $sel = $("#estadoContacto")
        .prop("disabled", true)
        .empty()
        .append("<option selected disabled>Selecciona un Estado</option>");

      if (resEstado.success) {
        // Normaliza a array aunque venga un solo objeto
        const estados = Array.isArray(resEstado.data)
          ? resEstado.data
          : [resEstado.data];
        console.log("Estado: ", estados);
        estados.forEach((e) => {
          $sel.append(
            `<option value="${e.Clave}" 
                data-Pais="${e.Pais}"
                data-Descripcion="${e.Descripcion}">
                ${e.Descripcion}
              </option>`
          );
        });
        if (estadoSeleccionado) {
          $sel.val(estadoSeleccionado);
          // Si además hay municipio, lo pasamos para poblar ese select
          if (municipioSeleccionado) {
            //obtenerMunicipios(estadoSeleccionado, municipioSeleccionado);
          }
        }
      } else {
        Swal.fire({
          icon: "warning",
          title: "Aviso",
          text: resEstado.message || "No se encontraron estados.",
        });
      }
    },
    error: function () {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "No pude cargar la lista de estados.",
      });
    },
  });
}
function obtenerMunicipios(edo, municipio) {
  // Habilitamos el select
  //$("#estadoContacto").prop("disabled", false);
  $.ajax({
    url: "../Servidor/PHP/remision.php",
    method: "POST",
    data: { numFuncion: "5", estado: edo },
    dataType: "json",
    success: function (resMunicipio) {
      if (resMunicipio.success && Array.isArray(resMunicipio.data)) {
        const $municipioNuevoContacto = $("#municipioContacto");
        $municipioNuevoContacto.empty();
        $municipioNuevoContacto.append(
          "<option selected disabled>Selecciona un Estado</option>"
        );
        // Filtrar según el largo del RFC
        resMunicipio.data.forEach((municipio) => {
          $municipioNuevoContacto.append(
            `<option value="${municipio.Clave}" 
                data-estado="${municipio.Estado}"
                data-Descripcion="${municipio.Descripcion || ""}">
                ${municipio.Descripcion}
              </option>`
          );
        });
      } else {
        Swal.fire({
          icon: "warning",
          title: "Aviso",
          text: resMunicipio.message || "No se encontraron municipios.",
        });
        //$("#municipioNuevoContacto").prop("disabled", true);
      }
    },
    error: function () {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Error al obtener la lista de estados.",
      });
    },
  });
}
function cargarPartidasPedido(pedidoID) {
  $.post(
    "../Servidor/PHP/remision.php",
    {
      numFuncion: "4",
      accion: "obtenerPartidas",
      clavePedido: pedidoID,
    },
    function (response) {
      if (response.success) {
        const partidas = response.partidas;
        partidasData = [...partidas]; // Almacena las partidas en el array global
        actualizarTablaPartidas(pedidoID); // Actualiza la tabla visualmente
        console.log("Partidas cargadas correctamente.");
      } else {
        console.error("Error al obtener partidas:", response.message);
        Swal.fire({
          title: "Aviso",
          text: "No se pudieron cargar las partidas.",
          icon: "warning",
          confirmButtonText: "Aceptar",
        });
        //alert("No se pudieron cargar las partidas: " + response.message);
      }
    },
    "json"
  ).fail(function (jqXHR, textStatus, errorThrown) {
    console.error("Error al cargar las partidas:", textStatus, errorThrown);
    Swal.fire({
      title: "Aviso",
      text: "Error al cargar las partidas.",
      icon: "error",
      confirmButtonText: "Aceptar",
    });
    //alert("Error al cargar las partidas: " + textStatus + " " + errorThrown);
  });
}
function actualizarTablaPartidas(pedidoID) {
  const tablaProductos = document.querySelector("#tablaProductos tbody");
  tablaProductos.innerHTML = ""; // Limpia la tabla

  partidasData.forEach((partida) => {
    const nuevaFila = document.createElement("tr");
    nuevaFila.setAttribute("data-num-par", partida.NUM_PAR); // Identifica cada fila por NUM_PAR

    nuevaFila.innerHTML = `
    <td>
        <div class="d-flex flex-column position-relative">
            <div class="d-flex align-items-center">
                <input type="text" class="producto" placeholder="" value="${partida.CVE_ART}" oninput="mostrarSugerencias(this)" readonly />
            </div>
            <ul class="lista-sugerencias position-absolute bg-white list-unstyled border border-secondary mt-1 p-2 d-none"></ul>
        </div>
    </td>
    <td><input type="number" class="cantidad" value="${partida.CANT}" style="text-align: right;" readonly /></td>
    <td><input type="text" class="unidad" value="${partida.UNI_VENTA}" readonly /></td>
    <td><input type="number" class="descuento" value="${partida.DESC1}" style="text-align: right;" readonly /></td>
    <td><input type="number" class="iva" value="${partida.IMPU4}" style="text-align: right;" readonly /></td>
    
    <td><input type="number" class="precioUnidad" value="${partida.PREC}" style="text-align: right;" readonly /></td>
    <td><input type="number" class="subtotalPartida" value="${partida.TOT_PARTIDA}" style="text-align: right;" readonly /></td>`;

    // Validar que la cantidad no sea negativa
    const cantidadInput = nuevaFila.querySelector(".cantidad");
    cantidadInput.addEventListener("input", () => {
      if (parseFloat(cantidadInput.value) < 0) {
        Swal.fire({
          title: "Aviso",
          text: "La cantidad no puede ser negativa.",
          icon: "warning",
          confirmButtonText: "Entendido",
        });
        cantidadInput.value = 0; // Restablecer el valor a 0
      } else {
        calcularSubtotal(nuevaFila); // Recalcular subtotal si el valor es válido
      }
    });
    tablaProductos.appendChild(nuevaFila);
  });
}

function calcularSubtotal(fila) {
  const cantidadInput = fila.querySelector(".cantidad");
  const precioInput = fila.querySelector(".precioUnidad");
  const subtotalInput = fila.querySelector(".subtotalPartida");

  const cantidad = parseFloat(cantidadInput.value) || 0; // Manejar valores no numéricos
  const precio = parseFloat(precioInput.value) || 0;

  const subtotal = cantidad * precio;
  subtotalInput.value = subtotal.toFixed(2); // Actualizar el subtotal con dos decimales
}
function limpiarTablaPartidas() {
  const tablaProductos = document.querySelector("#tablaProductos tbody");
  tablaProductos.innerHTML = ""; // Limpia todas las filas de la tabla
}
function obtenerFolioSiguiente() {
  return new Promise((resolve, reject) => {
    $.post(
      "../Servidor/PHP/remision.php",
      {
        numFuncion: "4", // Caso 3: Obtener siguiente folio
        accion: "obtenerFolioSiguiente",
      },
      function (response) {
        try {
          const data = JSON.parse(response);
          if (data.success) {
            console.log("El siguiente folio es: " + data.folioSiguiente);
            document.getElementById("numero").value = data.folioSiguiente;
            resolve(data.folioSiguiente); // Resuelve la promesa con el folio
          } else {
            console.log("Error: " + data.message);
            reject(data.message); // Rechaza la promesa con el mensaje de error
          }
        } catch (error) {
          reject("Error al procesar la respuesta: " + error.message);
        }
      }
    ).fail(function (xhr, status, error) {
      reject("Error de AJAX: " + error);
    });
  });
}
function llenarFiltroVendedor() {
  $.ajax({
    url: "../Servidor/PHP/usuarios.php",
    method: "GET",
    data: { numFuncion: "13" }, // Obtener todos los vendedores disponibles
    success: function (responseVendedores) {
      console.log("Respuesta del servidor (vendedores):", responseVendedores); // DEBUG

      try {
        const res =
          typeof responseVendedores === "string"
            ? JSON.parse(responseVendedores)
            : responseVendedores;

        if (res.success && Array.isArray(res.data)) {
          const selectVendedor = $("#filtroVendedor");
          selectVendedor.empty();
          selectVendedor.append("<option value='' selected>Todos</option>");

          res.data.forEach((vendedor) => {
            selectVendedor.append(
              `<option value="${vendedor.clave}">${vendedor.nombre}</option>`
            );
          });
          //console.log(data.data.claveUsuario);
        } else {
          Swal.fire({
            icon: "warning",
            title: "Aviso",
            text: res.message || "No se encontraron vendedores.",
          });
        }
      } catch (error) {
        console.error("Error al procesar los vendedores:", error);
      }
    },
    error: function () {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Error al obtener la lista de vendedores.",
      });
    },
  });
}

let filtroVendedor = "";
$(document).on("change", "#filtroVendedor", function () {
  filtroVendedor = $(this).val();
  // vuelve a cargar desde página 1
  paginaActual = 1;
  datosPedidos(true);
});
// Al cargar la página, se lee el filtro guardado y se carga la información
document.addEventListener("DOMContentLoaded", function () {
  let filtroGuardado = localStorage.getItem("filtroSeleccionado") || "Hoy";
  let estadoRemision = localStorage.getItem("estadoRemision") || "Vendidos";

  // 🔹 Resaltar el botón correspondiente al estado guardado
  $(".filtro-rol").removeClass("btn-primary").addClass("btn-secondary");
  $(`.filtro-rol[data-rol="${estadoRemision}"]`)
    .removeClass("btn-secondary")
    .addClass("btn-primary");

  // 🔹 Actualizar select del filtro, si aplica
  const filtroSelect = document.getElementById("filtroFecha");
  if (filtroSelect) {
    filtroSelect.value = filtroGuardado;
  }

  datosPedidos(true);
  inicializarEventosBotones();
});
function inicializarEventosBotones() {
  $(".filtro-rol")
    .off("click")
    .on("click", function () {
      let estadoRemision = $(this).data("rol"); // Obtener el rol del botón
      $(".filtro-rol").removeClass("btn-primary").addClass("btn-secondary"); // Resetear colores de botones
      $(this).removeClass("btn-secondary").addClass("btn-primary"); // Resaltar botón seleccionado
      var filtroSeleccionado = document.getElementById("filtroFecha").value;
      localStorage.setItem("estadoRemision", this.value);
      cargarPedidos(estadoRemision, filtroSeleccionado); // Filtrar la tabla
    });
}
$("#filtroFecha").change(function () {
  let estadoRemision = $(".filtro-rol.btn-primary").data("rol");
  var filtroSeleccionado = $(this).val(); // Obtener el valor seleccionado del filtro
  cargarPedidos(estadoRemision, filtroSeleccionado); // Llamar la función para cargar los pedidos con el filtro
});

let debounceTimeout;
function debouncedSearch() {
  clearTimeout(debounceTimeout);

  // Espera 3 segundos antes de ejecutar doSearch
  debounceTimeout = setTimeout(() => {
    doSearch(true);
  }, 500);
}

function doSearch(limpiarTabla = true) {
  const searchText = document.getElementById("searchTerm").value.toLowerCase();
  if (searchText.length >= 2) {
    // Recupera el filtro guardado o usa "Hoy" como valor predeterminado
    let filtroFecha = localStorage.getItem("filtroSeleccionado") || "Hoy";
    let estadoRemision = localStorage.getItem("estadoRemision") || "Vendidos";
    document.getElementById("filtroFecha").value = filtroFecha;
    const pedidosTable = document.getElementById("datosPedidos");
    const numColumns = 12; // Número de columnas de tu tabla

    // Código del spinner (puedes reemplazarlo por el que prefieras)
    const spinnerHTML = `
      <svg viewBox="25 25 50 50" style="width:40px;height:40px;">
          <circle r="20" cy="50" cx="50"></circle>
      </svg>
    `;
    // Construir una fila con un spinner en cada celda
    let spinnerRow = "<tr>";
    for (let i = 0; i < numColumns; i++) {
      spinnerRow += `<td style="text-align: center;">${spinnerHTML}</td>`;
    }
    spinnerRow += "</tr>";

    // Si se debe limpiar la tabla, se reemplaza el contenido; si no, se agrega al final.
    if (limpiarTabla) {
      pedidosTable.innerHTML = spinnerRow;
    } else {
      pedidosTable.insertAdjacentHTML("beforeend", spinnerRow);
    }

    $.post(
      "../Servidor/PHP/remision.php",
      {
        numFuncion: "6",
        noEmpresa: noEmpresa,
        filtroFecha: filtroFecha,
        estadoPedido: estadoRemision,
        filtroVendedor: filtroVendedor,
        pagina: paginaActual,
        porPagina: registrosPorPagina,
        filtroBusqueda: searchText,
      },
      function (response) {
        try {
          // Si la respuesta es una cadena, la parseamos
          if (typeof response === "string") {
            response = JSON.parse(response);
          }
          // Verificamos que la respuesta sea un objeto válido
          if (typeof response === "object" && response !== null) {
            // Limpiar la fila de spinner
            if (limpiarTabla) {
              pedidosTable.innerHTML = "";
            } else {
              // Remover la última fila (spinner) si se agregó al final
              const lastRow = pedidosTable.lastElementChild;
              if (lastRow) {
                pedidosTable.removeChild(lastRow);
              }
            }

            if (response.success && response.data) {
              let pedidos = response.data;
              // Ordenar pedidos por clave en orden descendente
              pedidos = pedidos.sort((a, b) => {
                const claveA = parseInt(a.Clave, 10) || 0;
                const claveB = parseInt(b.Clave, 10) || 0;
                return claveB - claveA;
              });

              // Crear un DocumentFragment para acumular las filas
              const fragment = document.createDocumentFragment();

              pedidos.forEach((pedido) => {
                const row = document.createElement("tr");
                const subtotalText = pedido.Subtotal
                  ? `$${Number(pedido.Subtotal).toLocaleString("es-MX", {
                      minimumFractionDigits: 2,
                      maximumFractionDigits: 2,
                    })}`
                  : "Sin subtotal";
                const importeText = pedido.ImporteTotal
                  ? `$${Number(pedido.ImporteTotal).toLocaleString("es-MX", {
                      minimumFractionDigits: 2,
                      maximumFractionDigits: 2,
                    })}`
                  : "Sin importe";
                /*const estadoFactura = pedido.DOC_SIG
                  ? "<i class='bx bx-check-square' style='color: green; display: block; margin: 0 auto;'></i>"
                  : "<i class='bx bx-check-square' style='color: gray; display: block; margin: 0 auto;'></i>"; // Centrado de la palomita con display: block y margin: 0 auto*/
                const estadoFactura = pedido.DOC_SIG
                  ? `<ion-icon name="document-sharp" style="color:green;display:block;margin:0 auto;"></ion-icon>`
                  : `<ion-icon name="document-sharp" style="color:gray; display:block;margin:0 auto;"></ion-icon>`;
                row.innerHTML = `
                  <td>${pedido.Tipo || "Sin tipo"}</td>
                  <td>${pedido.Clave || "Sin nombre"}</td>
                  <td >${pedido.Cliente || "Sin cliente"}</td>
                  <td>${pedido.Nombre || "Sin nombre"}</td>
                  <td>${pedido.Estatus || "0"}</td>
                  <td>${pedido.FechaElaboracion || "Sin fecha"}</td>
                  <td style="text-align: right;">${subtotalText}</td>
                  <!--<td style="text-align: right;">${
                    pedido.TotalComisiones
                      ? `$${parseFloat(pedido.TotalComisiones).toFixed(2)}`
                      : "Sin Comisiones"
                  }</td>-->
                  <td style="text-align: right;">${importeText}</td>
                <td class="nombreVendedor">${
                  pedido.NombreVendedor || "Sin vendedor"
                }</td>
                  <td>
                      <button class="btnVerPedido" name="btnVerPedido" data-id="${
                        pedido.Clave
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
                          transition: background-color 0.3s ease;">
                          <i class="fas fa-eye" style="margin-right: 0.5rem;"></i> Ver Remisión
                      </button>
                  </td>
                 <td>
                                    <button class="btnFacturar" name="btnFacturar" data-id="${
                                      pedido.Clave
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
                                        transition: background-color 0.3s ease;">
                                        Facturar
                                        <!--<i class="icon icon-bill" data-theme="outline" style="margin-right:.5rem;"></i>-->
                                    </button>
                                </td>
                  <td class="estadoFactura" name="estadoFactura "style="text-align: center;">${estadoFactura}</td> <!-- Centrar la palomita -->
                `;
                fragment.appendChild(row);
              });

              // Agregar todas las filas de una sola vez
              pedidosTable.appendChild(fragment);
              buildPagination(response.total);

              // Llama a la función que asigna eventos a los botones, si está definida
              if (typeof agregarEventosBotones === "function") {
                agregarEventosBotones();
              }
            } else {
              // Si no hay datos, limpiar la tabla y mostrar un mensaje
              pedidosTable.innerHTML = `<tr><td colspan="${numColumns}" style="text-align: center;">No hay datos disponibles</td></tr>`;
              //document.getElementById("btnMostrarMas").style.display = "none";
              console.warn(
                "No se recibieron datos o se devolvió un error:",
                response.message
              );
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
  } else {
    datosPedidos(true);
  }
}
document.addEventListener("DOMContentLoaded", function () {
  // Verificar si estamos en la página de creación o edición de pedidos
  if (window.location.pathname.includes("verRemision.php")) {
    // Obtener parámetros de la URL
    const urlParams = new URLSearchParams(window.location.search);
    const pedidoID = urlParams.get("pedidoID"); // Puede ser null si no está definido

    console.log("ID del pedido recibido:", pedidoID); // Log en consola para depuración

    if (pedidoID) {
      // Si es un pedido existente (pedidoID no es null)
      console.log("Cargando datos del pedido existente...");
      obtenerDatosPedido(pedidoID); // Función para cargar datos del pedido
      cargarPartidasPedido(pedidoID); // Función para cargar partidas del pedido
      $("#datosEnvio").prop("disabled", false);
      //obtenerDatosEnvioEditar(pedidoID); // Función para cargar partidas del pedido
    }
  }
});
