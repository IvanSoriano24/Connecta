const noEmpresa = sessionStorage.getItem("noEmpresaSeleccionada");
let partidasData = []; // Este contiene las partidas actuales del formulario

function agregarEventosBotones() {
  // Botones de editar
  const botonesEditar = document.querySelectorAll(".btnEditarPedido");
  botonesEditar.forEach((boton) => {
    boton.addEventListener("click", async function () {
      /*const pedidoID = this.dataset.id; // Obtener el ID del pedido
      console.log("Redirigiendo con pedidoID:", pedidoID);
      window.location.href = "altaPedido.php?pedidoID=" + pedidoID;*/
      const pedidoID = this.dataset.id; // Obtener el ID del pedido
      try {
        const res = await verificarPedido(pedidoID);
        if (res.success) {
          Swal.fire({
            title: "Aviso",
            text: "El pedido ya fue Remitido/Facturado, no es posible editarlo",
            icon: "error",
            confirmButtonText: "Entendido",
          });
        } else if (res.fail) {
          console.log("Redirigiendo con pedidoID:", pedidoID);
          window.location.href = "altaPedido.php?pedidoID=" + pedidoID;
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

  // Botones de eliminar
  const botonesEliminar = document.querySelectorAll(".btnCancelarPedido");
  botonesEliminar.forEach((boton) => {
    /*boton.addEventListener("click", function () {
      const pedidoID = this.dataset.id; // Obtener el ID del pedido
      console.log(pedidoID);
      Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción no se puede deshacer",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Sí, Cancelarlo",
        cancelButtonText: "Cancelar",
      }).then((result) => {
        if (result.isConfirmed) {
          eliminarPedido(pedidoID); // Llama a la función para eliminar el pedido
        }
      });
    });*/
    boton.addEventListener("click", async function () {
      const pedidoID = this.dataset.id; // Obtener el ID del pedido
      try {
        const res = await verificarPedido(pedidoID);
        if (res.success) {
          Swal.fire({
            title: "Aviso",
            text: "El pedido ya fue Remitido/Facturado, no es posible cancelarlo",
            icon: "error",
            confirmButtonText: "Entendido",
          });
        } else if (res.fail) {
          Swal.fire({
            title: "¿Estás seguro?",
            text: "Esta acción no se puede deshacer",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Sí, Cancelarlo",
            cancelButtonText: "Cancelar",
          }).then((result) => {
            if (result.isConfirmed) {
              eliminarPedido(pedidoID); // Llama a la función para eliminar el pedido
            }
          });
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

function eliminarPedido(pedidoID) {
  $.post(
    "../Servidor/PHP/ventas.php",
    { numFuncion: "10", pedidoID: pedidoID },
    function (response) {
      try {
        if (typeof response === "string") {
          response = JSON.parse(response);
        }
        if (response.success) {
          Swal.fire({
            title: "Eliminado",
            text: "El pedido ha sido cancelado correctamente",
            icon: "success",
            confirmButtonText: "Entendido",
          }).then(() => {
            datosPedidos(); // Actualizar la tabla después de eliminar
          });
        } else {
          Swal.fire({
            title: "Aviso",
            text: response.message || "No se pudo cancelar el pedido",
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
      text: "Hubo un problema al intentar eliminar el pedido",
      icon: "error",
      confirmButtonText: "Entendido",
    });
    console.log("Detalles del error:", jqXHR.responseText);
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

// Función para cargar los pedidos con el filtro seleccionado y guardar el filtro en localStorage
function cargarPedidos(filtroFecha) {
  // Guarda el filtro seleccionado
  localStorage.setItem("filtroSeleccionado", filtroFecha);
  console.log("Cargando pedidos con filtro:", filtroFecha);

  // Asegúrate de que la variable noEmpresa esté definida
  if (typeof noEmpresa === "undefined") {
    console.error("La variable noEmpresa no está definida");
    return;
  }

  $.post(
    "../Servidor/PHP/ventas.php",
    {
      numFuncion: "1",
      noEmpresa: noEmpresa,
      filtroFecha: filtroFecha,
      filtroVendedor: filtroVendedor,
    },
    function (response) {
      console.log("Respuesta del servidor:", response);
      try {
        if (typeof response === "string") {
          response = JSON.parse(response);
        }
        if (response && response.success && response.data) {
          let pedidos = response.data;
          console.log("Pedidos recibidos:", pedidos);
          // Ordenamos los pedidos (de mayor a menor clave)
          pedidos = pedidos.sort((a, b) => {
            const claveA = parseInt(a.Clave, 10) || 0;
            const claveB = parseInt(b.Clave, 10) || 0;
            return claveB - claveA;
          });
          mostrarPedidosEnTabla(pedidos);
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
const registrosPorPagina = 50; // Ajusta según convenga

// Función para cargar los pedidos con paginación.
// El parámetro "limpiarTabla" indica si se reinicia la tabla (true en carga inicial o al cambiar filtro)
// o se agregan filas al final (false al hacer "Mostrar más").
function datosPedidos(limpiarTabla = true) {
  // Recupera el filtro guardado o usa "Hoy" como valor predeterminado
  let filtroFecha = localStorage.getItem("filtroSeleccionado") || "Hoy";
  console.log(
    "Cargando datosPedidos con filtro:",
    filtroFecha,
    "Página:",
    paginaActual
  );

  const pedidosTable = document.getElementById("datosPedidos");
  if (!pedidosTable) {
    console.error("No se encontró el elemento con id 'datosPedidos'");
    return;
  }
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
    "../Servidor/PHP/ventas.php",
    {
      numFuncion: "1",
      noEmpresa: noEmpresa,
      filtroFecha: filtroFecha,
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
                               }</td>
                                <td>
                                    <button class="btnEditarPedido" name="btnEditarPedido" data-id="${
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
                                        <i class="fas fa-eye" style="margin-right: 0.5rem;"></i> Editar
                                    </button>
                                </td>
                                <td>
                                    <button class="btnCancelarPedido" name="btnCancelarPedido" data-id="${
                                      pedido.Clave
                                    }" style="
                                        display: inline-flex;
                                        align-items: center;
                                        padding: 0.5rem 1rem;
                                        font-size: 1rem;
                                        font-family: Lato;
                                        color: #fff;
                                        background-color: #dc3545;
                                        border: none;
                                        border-radius: 0.25rem;
                                        cursor: pointer;
                                        transition: background-color 0.3s ease;">
                                        <i class="fas fa-trash" style="margin-right: 0.5rem;"></i> Cancelar
                                    </button>
                                </td>
                            `;
              fragment.appendChild(row);
            });

            // Agregar todas las filas de una sola vez
            pedidosTable.appendChild(fragment);

            // Si se retornaron menos registros que el límite, ocultamos el botón "Mostrar más"
            if (pedidos.length < registrosPorPagina) {
              document.getElementById("btnMostrarMas").style.display = "none";
            } else {
              document.getElementById("btnMostrarMas").style.display = "block";
            }

            // Llama a la función que asigna eventos a los botones, si está definida
            if (typeof agregarEventosBotones === "function") {
              agregarEventosBotones();
            }
          } else {
            // Si no hay datos, limpiar la tabla y mostrar un mensaje
            pedidosTable.innerHTML = `<tr><td colspan="${numColumns}" style="text-align: center;">No hay datos disponibles</td></tr>`;
            document.getElementById("btnMostrarMas").style.display = "none";
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
    "../Servidor/PHP/ventas.php",
    {
      numFuncion: 2, // Función para obtener el pedido por ID
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

        document.getElementById("enviar").value = pedido.CALLE_ENVIO || "";
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

function cargarPartidasPedido(pedidoID) {
  $.post(
    "../Servidor/PHP/ventas.php",
    {
      numFuncion: "3",
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
        <button type="button" class="btn btn-danger btn-sm eliminarPartida" onclick="eliminarPartidaFormularioEditar(${partida.NUM_PAR}, ${pedidoID})">
            <i class="bx bx-trash"></i>
        </button>
    </td>
    <td>
        <div class="d-flex flex-column position-relative">
            <div class="d-flex align-items-center">
                <input type="text" class="producto" placeholder="" value="${partida.CVE_ART}" oninput="mostrarSugerencias(this)" />
                <button type="button" class="btn ms-2" onclick="mostrarProductos(this.closest('tr').querySelector('.producto'))"><i class="bx bx-search"></i></button>
            </div>
            <ul class="lista-sugerencias position-absolute bg-white list-unstyled border border-secondary mt-1 p-2 d-none"></ul>
        </div>
    </td>
    <td><input type="number" class="cantidad" value="${partida.CANT}" style="text-align: right;" /></td>
    <td><input type="text" class="unidad" value="${partida.UNI_VENTA}" readonly /></td>
    <td><input type="number" class="descuento" value="${partida.DESC1}" style="text-align: right;" /></td>
    <td><input type="number" class="iva" value="${partida.IMPU4}" style="text-align: right;" readonly /></td>
    
    <td><input type="number" class="precioUnidad" value="${partida.PREC}" style="text-align: right;" readonly /></td>
    <td><input type="number" class="subtotalPartida" value="${partida.TOT_PARTIDA}" style="text-align: right;" readonly /></td>
    <td><input type="number" class="impuesto2" value="0" readonly hidden /></td>
    <td><input type="number" class="impuesto3" value="0" readonly hidden /></td>
    <td><input type="number" class="ieps" value="${partida.IMPU1}" readonly hidden /></td>
    <td><input type="number" class="comision" value="${partida.COMI}" readonly hidden /></td>
    <td><input type="text" class="CVE_UNIDAD" value="0" readonly hidden /></td>
      <td><input type="text" class="COSTO_PROM" value="0" readonly hidden /></td>
`;

    // Validar que la cantidad no sea negativa
    const cantidadInput = nuevaFila.querySelector(".cantidad");
    cantidadInput.addEventListener("input", () => {
      if (parseFloat(cantidadInput.value) < 0) {
        Swal.fire({
          title: "Aviso",
          text: "La cantidad no puede ser negativa.",
          icon: "error",
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

function eliminarPartidaFormularioEditar(numPar, clavePedido) {
  Swal.fire({
    title: "¿Estás seguro?",
    text: "¿Deseas eliminar esta partida?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      // 🚀 Hacer la solicitud AJAX para eliminar en la base de datos
      $.ajax({
        url: "../Servidor/PHP/ventas.php",
        type: "POST",
        data: {
          numFuncion: "9", // Llamar al case 9 en PHP
          clavePedido: clavePedido, // ID del pedido
          numPar: numPar, // Número de partida a eliminar
        },
        dataType: "json",
        success: function (response) {
          if (response.success) {
            // 🔥 Eliminar la fila visualmente en el frontend
            const filaAEliminar = document.querySelector(
              `tr[data-num-par="${numPar}"]`
            );
            if (filaAEliminar) {
              filaAEliminar.remove();
            }

            // 🔥 Filtrar `partidasData` para excluir solo la partida eliminada
            partidasData = partidasData.filter(
              (partida) => partida.NUM_PAR !== numPar
            );

            console.log("Partidas actuales después de eliminar:", partidasData);

            // ✅ Mensaje de éxito
            Swal.fire({
              title: "Eliminada",
              text: response.message,
              icon: "success",
              confirmButtonText: "Entendido",
            });
          } else {
            // ❌ Si hubo error en el servidor
            Swal.fire({
              title: "Aviso",
              text: response.message,
              icon: "error",
              confirmButtonText: "Entendido",
            });
          }
        },
        error: function (xhr, status, error) {
          console.error("Error en la solicitud AJAX:", error);
          Swal.fire({
            title: "Aviso",
            text: "Hubo un problema al eliminar la partida.",
            icon: "error",
            confirmButtonText: "Entendido",
          });
        },
      });
    }
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
      "../Servidor/PHP/ventas.php",
      {
        numFuncion: "3", // Caso 3: Obtener siguiente folio
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
function obtenerFecha() {
  const today = new Date();
  const formattedDate = today.toISOString().split("T")[0];
  document.getElementById("diaAlta").value = formattedDate;
}
// Función que retorna una promesa para verificar el estado del pedido
function verificarPedido(pedidoID) {
  return new Promise((resolve, reject) => {
    $.post(
      "../Servidor/PHP/ventas.php",
      { numFuncion: "21", pedidoID: pedidoID },
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
          selectVendedor.append(
            "<option selected disabled>Seleccione un vendedor</option>"
          );

          res.data.forEach((vendedor) => {
            selectVendedor.append(
              `<option value="${vendedor.clave}">${vendedor.nombre} || ${vendedor.clave}</option>`
            );
          });
          console.log(data.data.claveUsuario);
          // ✅ Ahora obtenemos la clave del vendedor y la seleccionamos correctamente
          //obtenerClaveVendedor(data.data.claveUsuario);
        } else {
          Swal.fire({
            icon: "warning",
            title: "Aviso",
            text: res.message || "No se encontraron vendedores.",
          });
          $("#selectVendedor").prop("disabled", true);
        }
        //("#selectVendedor").prop("disabled", true);
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
$("#filtroFecha").change(function () {
  var filtroSeleccionado = $(this).val(); // Obtener el valor seleccionado del filtro
  cargarPedidos(filtroSeleccionado); // Llamar la función para cargar los pedidos con el filtro
});
$("#cancelarPedido").click(function () {
  window.location.href = "Ventas.php";
});
document.addEventListener("DOMContentLoaded", function () {
  llenarFiltroVendedor();
  let clienteSeleccionado =
    sessionStorage.getItem("clienteSeleccionado") === "true";
  // Detectar el clic en el enlace para "Crear Pedido"
  var altaPedidoBtn = document.getElementById("altaPedido");
  if (altaPedidoBtn) {
    altaPedidoBtn.addEventListener("click", function (e) {
      // Prevenir el comportamiento por defecto (redirigir)
      e.preventDefault();
      console.log("Redirigiendo a altaPedido.php...");
      // Redirigir a la página 'altaPedido.php' sin parámetro
      window.location.href = "altaPedido.php";
    });
  }

  // Verificar si estamos en la página de creación o edición de pedidos
  if (window.location.pathname.includes("altaPedido.php")) {
    // Obtener parámetros de la URL
    const urlParams = new URLSearchParams(window.location.search);
    const pedidoID = urlParams.get("pedidoID"); // Puede ser null si no está definido

    console.log("ID del pedido recibido:", pedidoID); // Log en consola para depuración

    if (pedidoID) {
      // Si es un pedido existente (pedidoID no es null)
      console.log("Cargando datos del pedido existente...");
      obtenerDatosPedido(pedidoID); // Función para cargar datos del pedido
      cargarPartidasPedido(pedidoID); // Función para cargar partidas del pedido
    } else {
      sessionStorage.setItem("clienteSeleccionado", false);
      clienteSeleccionado = false;
      // Si es un nuevo pedido (pedidoID es null)
      console.log("Preparando formulario para un nuevo pedido...");
      obtenerFecha(); // Establecer la fecha inicial del pedido
      limpiarTablaPartidas(); // Limpiar la tabla de partidas para el nuevo pedido
      obtenerFolioSiguiente(); // Generar el siguiente folio para el pedido
    }
  }
});
