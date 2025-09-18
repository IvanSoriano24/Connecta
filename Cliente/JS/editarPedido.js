// Maneja la creación de la fila de partidas
function agregarFilaPartidas() {
  //Verificamos si se tiene un cliente seleccionado
  const clienteSeleccionado =
    sessionStorage.getItem("clienteSeleccionado") === "true";
  if (!clienteSeleccionado) {
    Swal.fire({
      title: "Aviso",
      text: `Debes seleccionar un Cliente primero`,
      icon: "warning",
      confirmButtonText: "Entendido",
    });
    return;
  }

  //Obtenemos la tabla y las filas de las partidas
  const tablaProductos = document.querySelector("#tablaProductos tbody");
  const filas = tablaProductos.querySelectorAll("tr");

  //  Permitir agregar la primera fila sin validaciones previas
  if (filas.length > 0) {
    //Obtenemos los datos (producto, cantidad y total) de la ultima fila
    const ultimaFila = filas[filas.length - 1];
    const ultimoProducto = ultimaFila.querySelector(".producto").value.trim();
    const ultimaCantidad =
      parseFloat(ultimaFila.querySelector(".cantidad").value) || 0;
    const ultimoTotal =
      parseFloat(ultimaFila.querySelector(".subtotalPartida").value) || 0;
    //Validamos si hay un producto o si este tiene una cantidad es diferente a 0
    if (ultimoProducto === "" || ultimaCantidad === 0) {
      Swal.fire({
        title: "Aviso",
        text: "Debes seleccionar un producto y una cantidad mayor a 0 antes de agregar otra partida.",
        icon: "error",
        confirmButtonText: "Entendido",
      });
      return;
    }
    //Validamos si el total de la partida es diferente a 0
    if (ultimoTotal === 0) {
      Swal.fire({
        title: "Aviso",
        text: "No puedes agregar una partida con un producto de costo 0.",
        icon: "error",
        confirmButtonText: "Entendido",
      });
      return;
    }
  }

  // Crear un identificador único para la partida
  const numPar = partidasData.length + 1;

  // Crear una nueva fila
  const nuevaFila = document.createElement("tr");
  nuevaFila.setAttribute("data-num-par", numPar); // Identificar la fila oninput="mostrarSugerencias(this)
  nuevaFila.innerHTML = `
      <td>
          <button type="button" class="btn btn-danger btn-sm eliminarPartida" data-num-par="${numPar}" tabindex="-1" >
              <i class="bx bx-trash"></i>
          </button>
      </td>    
      <td>
          <div class="d-flex flex-column position-relative">
            <div class="d-flex align-items-center">
              <input type="text" class="producto" placeholder="Buscar producto..." />
              <button 
                type="button" 
                class="btn ms-2" 
                onclick="mostrarProductos(this.closest('tr').querySelector('.producto'))" tabindex="-1">
                <i class="bx bx-search"></i>
              </button>
            </div>
            <ul class="suggestions-list-productos position-absolute bg-white list-unstyled border border-secondary mt-1 p-2 d-none"></ul>
          </div>
      </td>
      <td><input type="number" class="cantidad" value="0" readonly style="text-align: right;" /></td>
      <td><input type="text" class="unidad" tabindex="-1" readonly /></td>
      <td><input type="number" class="descuento" style="text-align: right;" value="0" readonly /></td>
      <td><input type="number" class="iva" value="0" style="text-align: right;" tabindex="-1" readonly /></td>
      <td><input type="number" class="precioUnidad" value="0" style="text-align: right;" tabindex="-1" readonly /></td>
      <td><input type="number" class="subtotalPartida" value="0" style="text-align: right;" tabindex="-1" readonly /></td>
      <td><input type="number" class="impuesto2" value="0" readonly hidden /></td>
      <td><input type="number" class="impuesto3" value="0" readonly hidden /></td>
      <td><input type="text" class="CVE_UNIDAD" value="0" readonly hidden /></td>
      <td><input type="text" class="CVE_PRODSERV" value="0" readonly hidden /></td>
      <td><input type="text" class="COSTO_PROM" value="0" readonly hidden /></td>
      <td><input type="number" class="ieps" value="0" readonly hidden /></td>
      <td><input type="number" class="comision" value="0" readonly hidden/></td>
  `;

  // Añadir evento al botón de eliminar con delegación
  const botonEliminar = nuevaFila.querySelector(".eliminarPartida");
  botonEliminar.addEventListener("click", function () {
    eliminarPartidaFormulario(numPar, nuevaFila);
  });

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
      validarExistencias(nuevaFila, cantidadInput.value);
      //calcularSubtotal(nuevaFila); // Recalcular subtotal si el valor es válido
    }
  });
  const descuentoInput = nuevaFila.querySelector(".descuento");
  const descuentoGeneral = document.getElementById("descuentoCliente").value;
  descuentoInput.addEventListener("input", () => {
    if (parseFloat(descuentoInput.value) > descuentoGeneral) {
      Swal.fire({
        title: "Aviso",
        text: "El descuento no puede ser mayor a lo establecido.",
        icon: "warning",
        confirmButtonText: "Entendido",
      });
      descuentoInput.value = 0; // Restablecer el valor a 0
    }
  });

  tablaProductos.appendChild(nuevaFila);

  // Agregar la partida al array `partidasData`
  partidasData.push({
    NUM_PAR: numPar,
    CANT: 0,
    CVE_ART: "",
    DESC1: 0,
    DESC2: 0,
    IMPU1: 0,
    IMPU4: 0,
    COMI: 0,
    PREC: 0,
    TOT_PARTIDA: 0,
  });

  //console.log("Partidas actuales después de agregar:", partidasData);
}
function validarExistencias(nuevaFila, cantidad) {
  const cve_art = nuevaFila.querySelector(".producto");
  $.ajax({
    url: "../Servidor/PHP/ventas.php",
    method: "GET",
    data: {
      numFuncion: "31",
      cve_art: cve_art.value,
      cantidad: cantidad,
    },
    success(response) {
      let res;
      try {
        res = typeof response === "string" ? JSON.parse(response) : response;
      } catch (err) {
        console.error("JSON inválido:", err);
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Respuesta inválida del servidor.",
        });
        return;
      }

      if (res.success) {
        //console.log(res.data);
        const datos = res.data;
        let existenciasAlmacen = datos.ExistenciasAlmacen;
        let existenciasTotales = datos.ExistenciasTotales;
        let apartados = datos.APART;

        let existenciasReales = existenciasAlmacen - apartados;
        if (existenciasReales < 0) existenciasReales = 0;

        let otrosAlmacenes = existenciasTotales - existenciasAlmacen;

        if (existenciasReales < cantidad) {
          const mensajeHtml = `
            <p>No hay suficientes existencias para el producto <strong>${
              cve_art.value
            }</strong>.</p>
            <ul style="text-align:left">
              <li><strong>Solicitados:</strong> ${cantidad || 0}</li>
              <li><strong>Existencias Totales:</strong> ${
                existenciasTotales || 0
              }</li>
              <li><strong>Apartados:</strong> ${apartados || 0}</li>
              <li><strong>Disponibles en Almacen:</strong> ${
                existenciasReales || 0
              }</li>
              <li><strong>Otros almacenes:</strong> ${otrosAlmacenes || 0}</li>
            </ul>
          `;

          Swal.fire({
            title: "Advertencia sobre las existencias",
            html: mensajeHtml,
            icon: "warning",
            confirmButtonText: "Aceptar",
          });
        } else {
          calcularSubtotal(nuevaFila);
        }
      } else {
        Swal.fire({
          icon: "warning",
          title: "Aviso",
          text: res.message || "No se encontraron productos.",
        });
      }
    },
    error() {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "No se pudo obtener el producto.",
      });
    },
  });
}
function calcularSubtotal(fila) {
  //Obtiene el campo de la cantidad
  const cantidadInput = fila.querySelector(".cantidad");
  //Obtiene el campo del precio
  const precioInput = fila.querySelector(".precioUnidad");
  //Obtiene el campo del total
  const subtotalInput = fila.querySelector(".subtotalPartida");

  const cantidad = parseFloat(cantidadInput.value) || 0; // Obtiene el valor de la cantidad de producto
  const precio = parseFloat(precioInput.value) || 0; //Obtiene el valor del precio del producto

  const subtotal = cantidad * precio; //Realiza la operacion
  console.log("subtotalInput: ", subtotal);
  subtotalInput.value = subtotal.toFixed(2); // Actualizar el subtotal con dos decimales
}
function obtenerVendedores(tipoUsuario, claveUsuario) {
  const input = document.getElementById("vendedor");
  if (!input) return;

  $.ajax({
    url: "../Servidor/PHP/usuarios.php",
    method: "GET",
    data: {
      numFuncion: "13",
      tipoUsuario: tipoUsuario,
    },
    success(response) {
      let res;
      try {
        res = typeof response === "string" ? JSON.parse(response) : response;
      } catch (err) {
        console.error("JSON inválido:", err);
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Respuesta inválida del servidor.",
        });
        return;
      }

      if (res.success && Array.isArray(res.data)) {
        // 1. Creamos el <select> y copiamos atributos del <input>
        const select = document.createElement("select");
        select.id = input.id;
        select.name = input.name;
        select.style.cssText = input.style.cssText;
        select.tabIndex = input.tabIndex;

        // 2. Opción placeholder
        const ph = document.createElement("option");
        ph.value = "";
        ph.textContent = "Seleccione un vendedor";
        ph.disabled = true;

        if (!claveUsuario) {
          // Si no hay clave, mostramos y seleccionamos placeholder
          ph.selected = true;
        } else {
          // Si ya hay claveUsuario, lo oculta para no interferir
          ph.hidden = true;
          select.disabled = true;
        }
        select.append(ph);

        // 3. Rellenamos con los datos del servidor
        res.data.forEach((vendedor) => {
          const opt = document.createElement("option");
          opt.value = vendedor.clave;
          opt.textContent = `${vendedor.nombre} || ${vendedor.clave}`;
          if (vendedor.clave === claveUsuario) {
            opt.selected = true;
          }
          select.append(opt);
        });

        // 4. Reemplazamos el input por el select
        input.parentNode.replaceChild(select, input);
      } else {
        Swal.fire({
          icon: "warning",
          title: "Aviso",
          text: res.message || "No se encontraron vendedores.",
        });
      }
    },
    error() {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "No se pudo obtener la lista de vendedores.",
      });
    },
  });
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
function obtenerDatosPedido(pedidoID) {
  $.post(
    "../Servidor/PHP/ventas.php",
    {
      numFuncion: 2, // Función para obtener el pedido por ID
      pedidoID: pedidoID,
    },
    function (response) {
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
        document.getElementById("listaPrecios").value = pedido.LISTA_PREC || "";
        document.getElementById("condicion").value = pedido.CONDICION || "";
        document.getElementById("almacen").value = pedido.NUM_ALMA || "";
        document.getElementById("comision").value = pedido.COM_TOT || "";
        document.getElementById("diaAlta").value = pedido.FECHA_DOC || "";
        document.getElementById("entrega").value = pedido.FECHA_ENT || "";
        document.getElementById("numero").value = pedido.FOLIO || "";
        document.getElementById("tipoOperacion").value = "editar";

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
        <button type="button" class="btn btn-danger btn-sm eliminarPartida" onclick="eliminarPartidaFormularioEditar(${partida.NUM_PAR}, '${pedidoID}')">
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
    <td><input type="number" class="descuento" value="${partida.DESC1}" style="text-align: right;" readonly/></td>
    <td><input type="number" class="iva" value="${partida.IMPU4}" style="text-align: right;" readonly /></td>
    
    <td><input type="number" class="precioUnidad" value="${partida.PREC}" style="text-align: right;" readonly /></td>
    <td><input type="number" class="subtotalPartida" value="${partida.TOT_PARTIDA}" style="text-align: right;" readonly /></td>
    <td><input type="number" class="impuesto2" value="0" readonly hidden /></td>
    <td><input type="number" class="impuesto3" value="0" readonly hidden /></td>
    <td><input type="number" class="ieps" value="${partida.IMPU1}" readonly hidden /></td>
    <td><input type="number" class="comision" value="${partida.COMI}" readonly hidden /></td>
    <td><input type="text" class="CVE_UNIDAD" value="0" readonly hidden /></td> 
    <td><input type="text" class="CVE_PRODSERV" value="0" readonly hidden /></td>
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
let eliminacionesPendientes = [];
function eliminarPartidaFormularioEditar(numPar, clavePedido) {
  Swal.fire({
    title: "¿Estás seguro?",
    text: "¿Deseas eliminar esta partida?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (!result.isConfirmed) return;

    // Ubica la partida en memoria
    const idx = partidasData.findIndex((p) => p.NUM_PAR === numPar);
    if (idx === -1) return;

    const partida = partidasData[idx];

    // 1) Si la partida es NUEVA (no existe aún en SAE) => solo quita UI/memoria
    if (partida.__isNew === true) {
      // Quitar de UI
      const filaAEliminar = document.querySelector(
        `tr[data-num-par="${numPar}"]`
      );
      if (filaAEliminar) filaAEliminar.remove();

      // Quitar de memoria
      partidasData.splice(idx, 1);

      Swal.fire({
        title: "Eliminada",
        text: "Se quitó del formulario.",
        icon: "success",
      });
      return;
    }

    // 2) Si la partida ya EXISTE en SAE => encola eliminación para el GUARDAR
    eliminacionesPendientes.push({ numPar, clavePedido });

    // Quitar visualmente de la tabla
    const filaAEliminar = document.querySelector(
      `tr[data-num-par="${numPar}"]`
    );
    if (filaAEliminar) filaAEliminar.remove();

    // Quitar de memoria
    partidasData.splice(idx, 1);

    Swal.fire({
      title: "Marcada para eliminar",
      text: "Se borrará en SAE al guardar.",
      icon: "info",
    });
  });
}
function obtenerEstados() {
  // Habilitamos el select
  //$("#estadoContacto").prop("disabled", false);

  $.ajax({
    url: "../Servidor/PHP/ventas.php",
    method: "POST",
    data: { numFuncion: "22" },
    dataType: "json",
    success: function (resEstado) {
      if (resEstado.success && Array.isArray(resEstado.data)) {
        const $estadoNuevoContacto = $("#estadoContacto");
        $estadoNuevoContacto.empty();
        $estadoNuevoContacto.append(
          "<option selected disabled>Selecciona un Estado</option>"
        );
        // Filtrar según el largo del RFC
        resEstado.data.forEach((estado) => {
          $estadoNuevoContacto.append(
            `<option value="${estado.Clave}" 
                data-Pais="${estado.Pais}"
                data-Descripcion="${estado.Descripcion}">
                ${estado.Descripcion}
              </option>`
          );
        });
      } else {
        Swal.fire({
          icon: "warning",
          title: "Aviso",
          text: resEstado.message || "No se encontraron estados.",
        });
        //$("#estadoNuevoContacto").prop("disabled", true);
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
function obtenerDatosEnvioEditar(pedidoID) {
  //
  $("#datosEnvio").prop("disabled", false);
  $("#selectDatosEnvio").prop("disabled", false);
  $("#observaciones").prop("disabled", false);

  $.post(
    "../Servidor/PHP/clientes.php",
    {
      numFuncion: 10, // Función para obtener el pedido por ID
      pedidoID: pedidoID,
    },
    function (response) {
      if (response.success) {
        //const pedido = response.data;
        const data = response.data.fields;
        const name = response.data.name;
        const idDocumento = name.split("/").pop(); // Extrae el ID del documento
        //alert(idDocumento);
        // Verifica la estructura de los datos en el console.log
        console.log("Datos Envio: ", data); // Esto te mostrará el objeto completo
        $("#idDatos").val(idDocumento);
        //$("#folioDatos").val(data.id.integerValue);
        $("#nombreContacto").val(data.nombreContacto.stringValue);
        //$("#titutoDatos").val(data.tituloEnvio.stringValue);
        $("#compañiaContacto").val(data.companiaContacto.stringValue);
        $("#telefonoContacto").val(data.telefonoContacto.stringValue);
        $("#correoContacto").val(data.correoContacto.stringValue);
        $("#direccion1Contacto").val(data.direccion1Contacto.stringValue);
        $("#direccion2Contacto").val(data.direccion2Contacto.stringValue);
        $("#codigoContacto").val(data.codigoContacto.stringValue);
        //$("#estadoContacto").val(data.estado.stringValue);
        const municipio = data.municipioContacto.stringValue;
        const edo = data.estadoContacto.stringValue;
        obtenerEstadosEdit(edo, municipio);
        $("#observaciones").val(data.observaciones?.stringValue || "");
        //obtenerMunicipiosEdit(edo, municipio);
      } else if (response.datos) {
        const data = response.data;
        console.log("Data: ", data);
        $("#nombreContacto").val(data.nombreContacto);
        //$("#titutoDatos").val(data.tituloEnvio.stringValue);
        $("#compañiaContacto").val(data.companiaContacto);
        $("#telefonoContacto").val(data.telefonoContacto);
        $("#correoContacto").val(data.correoContacto);
        $("#direccion1Contacto").val(data.direccion1Contacto);
        $("#direccion2Contacto").val(data.direccion2Contacto);
        $("#codigoContacto").val(data.codigoContacto);
        //$("#estadoContacto").val(data.estado);
        const municipio = data.municipioContacto;
        const edo = data.estadoContacto;
        obtenerEstadosEdit(edo, municipio);
        $("#observaciones").val(data.observaciones || "");
      } else {
        /*Swal.fire({
          title: "Aviso",
          text: "No se pudo cargar el pedido.",
          icon: "warning",
          confirmButtonText: "Aceptar",
        });*/
        $(
          "#idDatos, #nombreContacto, #compañiaContacto, #telefonoContacto, #correoContacto, #direccion1Contacto, #direccion2Contacto, #codigoContacto, #observaciones"
        )
          .val("")
          .prop("disabled", true);
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
    url: "../Servidor/PHP/ventas.php",
    method: "POST",
    data: { numFuncion: "30" }, // ahora pide TODOS
    dataType: "json",
    success: function (res) {
      const $sel = $("#estadoContacto")
        .empty()
        .append("<option selected disabled>Selecciona un Estado</option>");

      if (!res.success) {
        return Swal.fire(
          "Aviso",
          res.message || "Error cargando estados",
          "warning"
        );
      }

      // res.data es un array de { Clave, Descripcion }
      res.data.forEach((e) => {
        $sel.append(
          `<option 
            value="${e.Clave}" 
            data-descripcion="${e.Descripcion}"
          >${e.Descripcion}</option>`
        );
      });

      // Si me pasaron uno para pre-seleccionar:
      if (estadoSeleccionado) {
        // buscar por texto (Descripción)
        $sel.find("option").each(function () {
          if ($(this).text().trim() === estadoSeleccionado.trim()) {
            $(this).prop("selected", true);
            return false; // rompe el each
          }
        });
        console.log($sel);
        // y luego cargar municipios de ese estado
        if (municipioSeleccionado) {
          obtenerMunicipiosEdit($sel.val(), municipioSeleccionado);
        }
      }
    },
    error: function () {
      Swal.fire("Error", "No pude cargar la lista de estados.", "error");
    },
  });
}
function obtenerMunicipiosEdit(edo, municipio) {
  $.ajax({
    url: "../Servidor/PHP/ventas.php",
    method: "POST",
    data: { numFuncion: "27", estado: edo, municipio: municipio },
    dataType: "json",
    success: function (res) {
      const $sel = $("#municipioContacto");
      $sel
        .empty()
        .append("<option selected disabled>Selecciona un municipio</option>");

      if (res.success && Array.isArray(res.data)) {
        // 1) Poblo el select
        res.data.forEach((mun) => {
          $sel.append(
            `<option value="${mun.Clave}"
                     data-estado="${mun.Estado}"
                     data-descripcion="${mun.Descripcion}">
               ${mun.Descripcion}
             </option>`
          );
        });

        // 2) Preselecciono el que coincide con `municipio` (que debe ser la Clave)
        if (municipio) {
          // Si `municipio` es la descripción:
          $sel
            .find("option")
            .filter((i, o) => $(o).text().trim() === municipio.trim())
            .prop("selected", true);
        }
      } else {
        Swal.fire({
          icon: "warning",
          title: "Aviso",
          text: res.message || "No se encontraron municipios.",
        });
        //$sel.prop("disabled", true);
      }
    },
    error: function () {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Error al obtener la lista de municipios.",
      });
    },
  });
}
function eliminarPartidaFormulario(numPar, filaAEliminar) {
  //Mensaje para confirmar
  Swal.fire({
    title: "¿Estás seguro?",
    text: "¿Deseas eliminar esta partida?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      //  Eliminar la fila visualmente
      filaAEliminar.remove();

      //  Eliminar la partida del array `partidasData`
      partidasData = partidasData.filter(
        (partida) => partida.NUM_PAR !== numPar
      );

      console.log(
        "Partida eliminada. Estado actual de partidasData:",
        partidasData
      );

      // Confirmación
      Swal.fire({
        title: "Eliminada",
        text: "La partida ha sido eliminada.",
        icon: "success",
        confirmButtonText: "Entendido",
      });
    }
  });
}
// Boton para mostrar Productos
function mostrarProductos(input) {
  // Abre el modal de productos automáticamente
  const modalProductos = new bootstrap.Modal(
    document.getElementById("modalProductos")
  );
  modalProductos.show();

  document.getElementById("campoBusqueda").value = "";

  // Llamar a la función AJAX para obtener los productos desde el servidor
  obtenerProductos(input);
}
function obtenerProductos(input) {
  // numFuncion=5 indica en PHP que queremos la acción “obtener productos”
  const numFuncion = 5;
  const chechk = false;
  //alert(chechk);
  // Creamos un nuevo objeto XMLHttpRequest
  const xhr = new XMLHttpRequest();

  // Configuramos la llamada GET a nuestro endpoint, pasando numFuncion por query string
  xhr.open(
    "GET",
    "../Servidor/PHP/ventas.php?numFuncion=" + numFuncion + "&chechk=" + chechk,
    true
  );

  // Indicamos que enviaremos/recibiremos JSON
  xhr.setRequestHeader("Content-Type", "application/json");

  // Cuando la respuesta llegue al 100%:
  xhr.onload = function () {
    // Si el servidor respondió con HTTP 200 (OK)
    if (xhr.status === 200) {
      // Parseamos el texto JSON que devolvió el servidor
      const response = JSON.parse(xhr.responseText);

      // Si el servidor indicó éxito en el JSON:
      if (response.success) {
        // Llamamos a la función que muestra la lista de productos,
        // pasándole el array de productos y el input donde se debe desplegar
        mostrarListaProductos(response.productos, input);
      } else {
        // Si success = false, mostramos un aviso con el mensaje devuelto
        Swal.fire({
          title: "Aviso",
          text: response.message,
          icon: "warning",
          confirmButtonText: "Aceptar",
        });
      }
    } else {
      // Si el HTTP status no es 200, hubo un error en la consulta
      Swal.fire({
        title: "Aviso",
        text: "Hubo un problema con la consulta de productos.",
        icon: "error",
        confirmButtonText: "Aceptar",
      });
    }
  };

  // Si ocurre algún error de red (por ejemplo, sin conexión)
  xhr.onerror = function () {
    Swal.fire({
      title: "Aviso",
      text: "Hubo un problema con la conexión.",
      icon: "error",
      confirmButtonText: "Aceptar",
    });
  };

  // Finalmente, enviamos la petición al servidor
  xhr.send();
}
function mostrarListaProductosCheck(productos) {
  const tablaProductos = document.querySelector("#tablalistaProductos tbody");
  const campoBusqueda = document.getElementById("campoBusqueda");
  const filtroCriterio = document.getElementById("filtroCriterio");

  // Función para renderizar productos
  function renderProductos(filtro = "") {
    tablaProductos.innerHTML = ""; // Limpiar la tabla antes de agregar nuevos productos
    const criterio = filtroCriterio.value; // Obtener criterio seleccionado
    const productosFiltrados = productos.filter((producto) =>
      producto[criterio].toLowerCase().includes(filtro.toLowerCase())
    );
    productosFiltrados.forEach(function (producto) {
      const fila = document.createElement("tr");
      const celdaClave = document.createElement("td");
      celdaClave.textContent = producto.CVE_ART;
      const celdaDescripcion = document.createElement("td");
      celdaDescripcion.textContent = producto.DESCR;
      const celdaExist = document.createElement("td");
      celdaExist.textContent = producto.EXIST;
      fila.appendChild(celdaClave);
      fila.appendChild(celdaDescripcion);
      fila.appendChild(celdaExist);
      fila.onclick = async function () {
        if (producto.EXIST > 0) {
          //input.value = producto.CVE_ART;
          const campoProducto = filaTabla.querySelector(".producto");
          campoProducto = producto.CVE_ART;
          $("#CVE_ESQIMPU").val(producto.CVE_ESQIMPU);
          const filaTabla = input.closest("tr");
          const campoUnidad = filaTabla.querySelector(".unidad");
          if (campoUnidad) {
            campoUnidad.value = producto.UNI_MED;
          }
          const CVE_UNIDAD = filaTabla.querySelector(".CVE_UNIDAD");
          const CVE_PRODSERV = filaTabla.querySelector(".CVE_PRODSERV");
          const COSTO_PROM = filaTabla.querySelector(".COSTO_PROM");

          CVE_UNIDAD.value = producto.CVE_UNIDAD;
          CVE_PRODSERV.value = producto.CVE_PRODSERV;
          COSTO_PROM.value = producto.COSTO_PROM;
          /*if (!producto.CVE_PRODSERV) {
            Swal.fire({
              title: "Datos Fiscales",
              text: "Este producto no cuenta con CVE_PRODSERV",
              icon: "warnig",
              confirmButtonText: "Entendido",
            });

            const precioInput = filaTabla.querySelector(".precioUnidad");
            const cantidadInput = filaTabla.querySelector(".cantidad");
            const unidadInput = filaTabla.querySelector(".unidad");
            const descuentoInput = filaTabla.querySelector(".descuento");
            const totalInput = filaTabla.querySelector(".subtotalPartida");
            precioInput.value = parseFloat(0).toFixed(2);
            cantidadInput.value = parseFloat(0).toFixed(2);
            unidadInput.value = parseFloat(0).toFixed(2);
            descuentoInput.value = parseFloat(0).toFixed(2);
            totalInput.value = parseFloat(0).toFixed(2);

            return; // 🚨 Salir de la función si `filaProd` no es válido
          }
          if (!producto.CVE_UNIDAD) {
            Swal.fire({
              title: "Datos Fiscales",
              text: "Este producto no cuenta con CVE_UNIDAD",
              icon: "warnig",
              confirmButtonText: "Entendido",
            });
            
            const precioInput = filaTabla.querySelector(".precioUnidad");
            const cantidadInput = filaTabla.querySelector(".cantidad");
            const unidadInput = filaTabla.querySelector(".unidad");
            const descuentoInput = filaTabla.querySelector(".descuento");
            const totalInput = filaTabla.querySelector(".subtotalPartida");
            precioInput.value = parseFloat(0).toFixed(2);
            cantidadInput.value = parseFloat(0).toFixed(2);
            unidadInput.value = parseFloat(0).toFixed(2);
            descuentoInput.value = parseFloat(0).toFixed(2);
            totalInput.value = parseFloat(0).toFixed(2);

            return; // 🚨 Salir de la función si `filaProd` no es válido
          }*/
          // Desbloquear o mantener bloqueado el campo de cantidad según las existencias
          const campoCantidad = filaTabla.querySelector("input.cantidad");
          if (campoCantidad) {
            // if (producto.EXIST > 0) {
            campoCantidad.readOnly = false;
            campoCantidad.value = 0; // Valor inicial opcional
          }
          // Cerrar el modal
          cerrarModal();
          await completarPrecioProducto(producto.CVE_ART, filaTabla);
        } else {
          // campoCantidad.readOnly = true;
          // campoCantidad.value = "Sin existencias"; // Mensaje opcional
          Swal.fire({
            title: "Aviso",
            text: `El producto "${producto.CVE_ART}" no tiene existencias disponibles.`,
            icon: "warning",
            confirmButtonText: "Entendido",
          });
        }
      };
      tablaProductos.appendChild(fila);
    });
  }
  // Evento para actualizar la tabla al escribir en el campo de búsqueda
  campoBusqueda.addEventListener("input", () => {
    renderProductos(campoBusqueda.value);
  });
  // Renderizar productos inicialmente
  renderProductos();
}
// Cierra el modal usando la API de Bootstrap
function cerrarModal() {
  const modal = bootstrap.Modal.getInstance(
    document.getElementById("modalProductos")
  );
  if (modal) {
    modal.hide();
  }
}
async function completarPrecioProducto(cveArt, filaTabla) {
  try {
    // Obtenemos el elemento <select> de lista de precios
    const listaPrecioElement = document.getElementById("listaPrecios");
    console.log(listaPrecioElement.value);

    // Buscamos el campo de descuento dentro de la fila
    let descuento = filaTabla.querySelector(".descuento");
    // Leemos el descuento predeterminado del cliente
    const descuentoCliente = document.getElementById("descuentoCliente").value;
    // Inicialmente, asignamos ese descuento al campo
    descuento.value = descuentoCliente;

    // Determinamos la clave de lista de precios; si no existe, usamos "1"
    const cvePrecio = listaPrecioElement ? listaPrecioElement.value : "1";

    // Llamamos a la función que obtiene precio y lista usada
    const resultado = await obtenerPrecioProducto(cveArt, cvePrecio);
    if (!resultado) return; // Si no hay resultado válido, salimos

    // Desestructuramos el objeto {precio, listaUsada}
    const { precio, listaUsada } = resultado;

    // Si no se obtuvo precio, mostramos alerta y salimos
    if (!precio) {
      Swal.fire({
        title: "Aviso",
        text: "No se pudo obtener el precio del producto.",
        icon: "warning",
        confirmButtonText: "Aceptar",
      });
      return;
    }

    // Si la lista usada no es la predeterminada (1), forzamos el descuento a 0
    if (listaUsada != 1) {
      descuento.value = 0;
    }

    // Buscamos el campo de precioUnitario dentro de la fila
    const precioInput = filaTabla.querySelector(".precioUnidad");
    if (precioInput) {
      // Asignamos el precio con dos decimales y lo dejamos readonly
      precioInput.value = parseFloat(precio).toFixed(2);
      precioInput.readOnly = true;
    } else {
      console.error("No se encontró el campo 'precioUnidad' en la fila.");
    }

    // Obtenemos la clave de esquema de impuestos del formulario principal
    const CVE_ESQIMPU = document.getElementById("CVE_ESQIMPU").value;
    if (!CVE_ESQIMPU) {
      console.error("CVE_ESQIMPU no tiene un valor válido.");
      return;
    }
    // Llamamos a la función que obtiene los valores de impuestos
    const impuestos = await obtenerImpuesto(CVE_ESQIMPU);

    // Obtenemos cada campo de impuesto dentro de la fila
    const impuesto1Input = filaTabla.querySelector(".ieps");
    const impuesto2Input = filaTabla.querySelector(".impuesto2");
    const impuesto4Input = filaTabla.querySelector(".iva");
    const impuesto3Input = filaTabla.querySelector(".impuesto3");

    // Si existen los campos, asignamos los valores y los dejamos readonly
    if (impuesto1Input && impuesto4Input) {
      impuesto1Input.value = parseFloat(impuestos.impuesto1);
      impuesto4Input.value = parseFloat(impuestos.impuesto4);
      impuesto3Input.value = parseFloat(impuestos.impuesto3);
      impuesto1Input.readOnly = true;
      impuesto4Input.readOnly = true;
    } else {
      console.error(
        "No se encontraron uno o más campos de impuestos en la fila."
      );
    }

    // Aquí podrías manejar impuesto2Input si lo usas en alguna lógica adicional
  } catch (error) {
    console.error("Error al completar el precio del producto:", error);
  }
}
async function obtenerImpuesto(cveEsqImpu) {
  return new Promise((resolve, reject) => {
    $.ajax({
      url: "../Servidor/PHP/ventas.php", // Endpoint en el servidor que procesa la solicitud
      type: "POST", // Método HTTP POST
      data: { cveEsqImpu: cveEsqImpu, numFuncion: "7" }, // Parámetros enviados al servidor
      success: function (response) {
        try {
          // Si el servidor indica éxito, extraemos los impuestos y resolvemos la promesa
          if (response.success) {
            const { IMPUESTO1, IMPUESTO2, IMPUESTO3, IMPUESTO4 } =
              response.impuestos;
            resolve({
              impuesto1: IMPUESTO1,
              impuesto2: IMPUESTO2,
              impuesto3: IMPUESTO3,
              impuesto4: IMPUESTO4,
            });
          } else {
            // Si success = false, mostramos error en consola y rechazamos la promesa
            console.error("Error del servidor:", response.message);
            reject("Error del servidor: " + response.message);
          }
        } catch (error) {
          // Si la respuesta no tiene el formato esperado, rechazamos la promesa
          console.error("Error al procesar la respuesta:", error);
          reject("Error al procesar la respuesta: " + error.message);
        }
      },
      error: function (xhr, status, error) {
        // Si falla la petición AJAX, rechazamos la promesa
        reject("Error en la solicitud AJAX: " + error);
      },
    });
  });
}
// Función para obtener el precio del producto
async function obtenerPrecioProducto(claveProducto, listaPrecioCliente) {
  try {
    // Realizamos un GET a ventas.php para el numFuncion=6, enviando claveProducto y listaPrecioCliente
    const response = await $.get("../Servidor/PHP/ventas.php", {
      numFuncion: "6",
      claveProducto: claveProducto,
      listaPrecioCliente: listaPrecioCliente,
    });

    // Si el servidor devuelve success = true, retornamos un objeto con precio y listaUsada
    if (response.success) {
      return {
        precio: parseFloat(response.precio),
        listaUsada: response.listaUsada,
      };
    } else {
      // Si success = false, mostramos un aviso y retornamos null
      Swal.fire({
        title: "Aviso",
        text: response.message,
        icon: "warning",
        confirmButtonText: "Aceptar",
      });
      return null;
    }
  } catch (error) {
    // Si hay un error en la llamada AJAX, lo registramos en consola y retornamos null
    console.error("Error al obtener el precio del producto:", error);
    return null;
  }
}
function mostrarListaProductos(productos, input) {
  const tablaProductos = document.querySelector("#tablalistaProductos tbody");
  const campoBusqueda = document.getElementById("campoBusqueda");
  const filtroCriterio = document.getElementById("filtroCriterio");

  // Función para renderizar productos
  function renderProductos(filtro = "") {
    tablaProductos.innerHTML = ""; // Limpiar la tabla antes de agregar nuevos productos
    const criterio = filtroCriterio.value; // Obtener criterio seleccionado
    const productosFiltrados = productos.filter((producto) =>
      producto[criterio].toLowerCase().includes(filtro.toLowerCase())
    );
    productosFiltrados.forEach(function (producto) {
      const fila = document.createElement("tr");
      const celdaClave = document.createElement("td");
      celdaClave.textContent = producto.CVE_ART;
      const celdaDescripcion = document.createElement("td");
      celdaDescripcion.textContent = producto.DESCR;
      const celdaExist = document.createElement("td");
      celdaExist.textContent = producto.EXIST;
      fila.appendChild(celdaClave);
      fila.appendChild(celdaDescripcion);
      fila.appendChild(celdaExist);
      fila.onclick = async function () {
        if (producto.EXIST > 0) {
          input.value = producto.CVE_ART;
          $("#CVE_ESQIMPU").val(producto.CVE_ESQIMPU);
          const filaTabla = input.closest("tr");
          const campoUnidad = filaTabla.querySelector(".unidad");
          if (campoUnidad) {
            campoUnidad.value = producto.UNI_MED;
          }
          const CVE_UNIDAD = filaTabla.querySelector(".CVE_UNIDAD");
          const CVE_PRODSERV = filaTabla.querySelector(".CVE_PRODSERV");
          const COSTO_PROM = filaTabla.querySelector(".COSTO_PROM");

          CVE_UNIDAD.value = producto.CVE_UNIDAD;
          CVE_PRODSERV.value = producto.CVE_PRODSERV;
          COSTO_PROM.value = producto.COSTO_PROM;
          /*if (!producto.CVE_PRODSERV) {
            Swal.fire({
              title: "Datos Fiscales",
              text: "Este producto no cuenta con CVE_PRODSERV",
              icon: "warnig",
              confirmButtonText: "Entendido",
            });

            const precioInput = filaTabla.querySelector(".precioUnidad");
            const cantidadInput = filaTabla.querySelector(".cantidad");
            const unidadInput = filaTabla.querySelector(".unidad");
            const descuentoInput = filaTabla.querySelector(".descuento");
            const totalInput = filaTabla.querySelector(".subtotalPartida");
            precioInput.value = parseFloat(0).toFixed(2);
            cantidadInput.value = parseFloat(0).toFixed(2);
            unidadInput.value = parseFloat(0).toFixed(2);
            descuentoInput.value = parseFloat(0).toFixed(2);
            totalInput.value = parseFloat(0).toFixed(2);

            return; // 🚨 Salir de la función si `filaProd` no es válido
          }
          if (!producto.CVE_UNIDAD) {
            Swal.fire({
              title: "Datos Fiscales",
              text: "Este producto no cuenta con CVE_UNIDAD",
              icon: "warnig",
              confirmButtonText: "Entendido",
            });
            
            const precioInput = filaTabla.querySelector(".precioUnidad");
            const cantidadInput = filaTabla.querySelector(".cantidad");
            const unidadInput = filaTabla.querySelector(".unidad");
            const descuentoInput = filaTabla.querySelector(".descuento");
            const totalInput = filaTabla.querySelector(".subtotalPartida");
            precioInput.value = parseFloat(0).toFixed(2);
            cantidadInput.value = parseFloat(0).toFixed(2);
            unidadInput.value = parseFloat(0).toFixed(2);
            descuentoInput.value = parseFloat(0).toFixed(2);
            totalInput.value = parseFloat(0).toFixed(2);

            return; // 🚨 Salir de la función si `filaProd` no es válido
          }*/
          // Desbloquear o mantener bloqueado el campo de cantidad según las existencias
          const campoCantidad = filaTabla.querySelector("input.cantidad");
          if (campoCantidad) {
            // if (producto.EXIST > 0) {
            campoCantidad.readOnly = false;
            campoCantidad.value = 0; // Valor inicial opcional
          }
          // Cerrar el modal
          cerrarModal();
          await completarPrecioProducto(producto.CVE_ART, filaTabla);
        } else {
          // campoCantidad.readOnly = true;
          // campoCantidad.value = "Sin existencias"; // Mensaje opcional
          Swal.fire({
            title: "Aviso",
            text: `El producto "${producto.CVE_ART}" no tiene existencias disponibles.`,
            icon: "warning",
            confirmButtonText: "Entendido",
          });
        }
      };
      tablaProductos.appendChild(fila);
    });
  }
  // Evento para actualizar la tabla al escribir en el campo de búsqueda
  campoBusqueda.addEventListener("input", () => {
    renderProductos(campoBusqueda.value);
  });
  // Renderizar productos inicialmente
  renderProductos();
}
function mostrarListaProductosCheck(productos) {
  const tablaProductos = document.querySelector("#tablalistaProductos tbody");
  const campoBusqueda = document.getElementById("campoBusqueda");
  const filtroCriterio = document.getElementById("filtroCriterio");

  // Función para renderizar productos
  function renderProductos(filtro = "") {
    tablaProductos.innerHTML = ""; // Limpiar la tabla antes de agregar nuevos productos
    const criterio = filtroCriterio.value; // Obtener criterio seleccionado
    const productosFiltrados = productos.filter((producto) =>
      producto[criterio].toLowerCase().includes(filtro.toLowerCase())
    );
    productosFiltrados.forEach(function (producto) {
      const fila = document.createElement("tr");
      const celdaClave = document.createElement("td");
      celdaClave.textContent = producto.CVE_ART;
      const celdaDescripcion = document.createElement("td");
      celdaDescripcion.textContent = producto.DESCR;
      const celdaExist = document.createElement("td");
      celdaExist.textContent = producto.EXIST;
      fila.appendChild(celdaClave);
      fila.appendChild(celdaDescripcion);
      fila.appendChild(celdaExist);
      fila.onclick = async function () {
        if (producto.EXIST > 0) {
          //input.value = producto.CVE_ART;
          const campoProducto = filaTabla.querySelector(".producto");
          campoProducto = producto.CVE_ART;
          $("#CVE_ESQIMPU").val(producto.CVE_ESQIMPU);
          const filaTabla = input.closest("tr");
          const campoUnidad = filaTabla.querySelector(".unidad");
          if (campoUnidad) {
            campoUnidad.value = producto.UNI_MED;
          }
          const CVE_UNIDAD = filaTabla.querySelector(".CVE_UNIDAD");
          const CVE_PRODSERV = filaTabla.querySelector(".CVE_PRODSERV");
          const COSTO_PROM = filaTabla.querySelector(".COSTO_PROM");

          CVE_UNIDAD.value = producto.CVE_UNIDAD;
          CVE_PRODSERV.value = producto.CVE_PRODSERV;
          COSTO_PROM.value = producto.COSTO_PROM;
          /*if (!producto.CVE_PRODSERV) {
            Swal.fire({
              title: "Datos Fiscales",
              text: "Este producto no cuenta con CVE_PRODSERV",
              icon: "warnig",
              confirmButtonText: "Entendido",
            });

            const precioInput = filaTabla.querySelector(".precioUnidad");
            const cantidadInput = filaTabla.querySelector(".cantidad");
            const unidadInput = filaTabla.querySelector(".unidad");
            const descuentoInput = filaTabla.querySelector(".descuento");
            const totalInput = filaTabla.querySelector(".subtotalPartida");
            precioInput.value = parseFloat(0).toFixed(2);
            cantidadInput.value = parseFloat(0).toFixed(2);
            unidadInput.value = parseFloat(0).toFixed(2);
            descuentoInput.value = parseFloat(0).toFixed(2);
            totalInput.value = parseFloat(0).toFixed(2);

            return; // 🚨 Salir de la función si `filaProd` no es válido
          }
          if (!producto.CVE_UNIDAD) {
            Swal.fire({
              title: "Datos Fiscales",
              text: "Este producto no cuenta con CVE_UNIDAD",
              icon: "warnig",
              confirmButtonText: "Entendido",
            });
            
            const precioInput = filaTabla.querySelector(".precioUnidad");
            const cantidadInput = filaTabla.querySelector(".cantidad");
            const unidadInput = filaTabla.querySelector(".unidad");
            const descuentoInput = filaTabla.querySelector(".descuento");
            const totalInput = filaTabla.querySelector(".subtotalPartida");
            precioInput.value = parseFloat(0).toFixed(2);
            cantidadInput.value = parseFloat(0).toFixed(2);
            unidadInput.value = parseFloat(0).toFixed(2);
            descuentoInput.value = parseFloat(0).toFixed(2);
            totalInput.value = parseFloat(0).toFixed(2);

            return; // 🚨 Salir de la función si `filaProd` no es válido
          }*/
          // Desbloquear o mantener bloqueado el campo de cantidad según las existencias
          const campoCantidad = filaTabla.querySelector("input.cantidad");
          if (campoCantidad) {
            // if (producto.EXIST > 0) {
            campoCantidad.readOnly = false;
            campoCantidad.value = 0; // Valor inicial opcional
          }
          // Cerrar el modal
          cerrarModal();
          await completarPrecioProducto(producto.CVE_ART, filaTabla);
        } else {
          // campoCantidad.readOnly = true;
          // campoCantidad.value = "Sin existencias"; // Mensaje opcional
          Swal.fire({
            title: "Aviso",
            text: `El producto "${producto.CVE_ART}" no tiene existencias disponibles.`,
            icon: "warning",
            confirmButtonText: "Entendido",
          });
        }
      };
      tablaProductos.appendChild(fila);
    });
  }
  // Evento para actualizar la tabla al escribir en el campo de búsqueda
  campoBusqueda.addEventListener("input", () => {
    renderProductos(campoBusqueda.value);
  });
  // Renderizar productos inicialmente
  renderProductos();
}
function consolidarPartidasEnTabla() {
  const mapa = {};

  // 1) Iterar cada fila de la tabla
  $("#tablaProductos tbody tr").each(function () {
    const $tr = $(this);
    const producto = $tr.find(".producto").val();
    const cantidad = parseFloat($tr.find(".cantidad").val()) || 0;
    const precio = parseFloat($tr.find(".precioUnidad").val()) || 0;

    if (!mapa[producto]) {
      // Primera vez que vemos este producto: almacenamos la fila y los datos
      mapa[producto] = {
        fila: $tr,
        cantidadAcumulada: cantidad,
        precioUnitario: precio,
      };
    } else {
      // Ya existía: sumamos la cantidad y removemos esta fila
      mapa[producto].cantidadAcumulada += cantidad;
      $tr.remove();
    }
  });

  // 2) Para cada producto agrupado, actualizamos la fila guardada
  Object.values(mapa).forEach(({ fila, cantidadAcumulada, precioUnitario }) => {
    // Recalcular subtotal
    const subtotal = cantidadAcumulada * precioUnitario;

    // Actualizar inputs en la primera fila
    fila.find(".cantidad").val(cantidadAcumulada);
    fila.find(".subtotalPartida").val(subtotal.toFixed(2));
  });
}
function guardarPedido(id) {
  try {
    //Obtenemos los datos de envio
    const envioData = extraerDatosEnvio();
    console.log("Datos de envio obtenidos:", envioData);
    //Validamos que esten completas
    const validacion = validarDatosEnvio();
    if (!validacion) {
      Swal.fire({
        title: "Error al guardar el pedido",
        text: "No se escogieron los datos de envio.",
        icon: "warning",
        confirmButtonText: "Aceptar",
      });
      return;
    }
    //Mensaje de carga
    Swal.fire({
      title: "Procesando pedido...",
      text: "Por favor, espera mientras se completa el pedido.",
      allowOutsideClick: false,
      allowEscapeKey: false,
      didOpen: () => {
        Swal.showLoading();
      },
    });
    //Funcion para unir las partidas cuyos productos sean el mismo
    consolidarPartidasEnTabla();
    // Obtener la información del formulario
    const formularioData = obtenerDatosFormulario();
    console.log("Datos del formulario obtenidos:", formularioData);
    // Obtener la información de las partidas
    const partidasData = obtenerDatosPartidas();

    console.log("Datos de partidas obtenidos:", partidasData);

    // Determinar si es alta o edición
    //formularioData.tipoOperacion = id === 0 ? "alta" : "editar";
    console.log("Datos preparados para enviar:", formularioData, partidasData);

    // Enviar los datos al backend
    enviarDatosBackend(formularioData, partidasData, envioData);
  } catch (error) {
    console.error("Error en guardarPedido:", error);
  }
}
function validarDatosEnvio() {
  //Obtenemos los valores
  const nombreContacto = document.getElementById("nombreContacto").value;
  const compañiaContacto = document.getElementById("compañiaContacto").value;
  const telefonoContacto = document.getElementById("telefonoContacto").value;
  const correoContacto = document.getElementById("correoContacto").value;
  const direccion1Contacto =
    document.getElementById("direccion1Contacto").value;
  const direccion2Contacto =
    document.getElementById("direccion2Contacto").value;
  const codigoContacto = document.getElementById("codigoContacto").value;

  const estadoContacto = $("#estadoContacto option:selected").data(
    "descripcion"
  );
  const municipioContacto = $("#municipioContacto option:selected").data(
    "descripcion"
  );
  const tipoOperacion = document.getElementById("tipoOperacion").value;

  console.log(tipoOperacion);
  console.log(nombreContacto);
  console.log(direccion1Contacto);
  console.log(direccion2Contacto);
  console.log(codigoContacto);
  console.log(estadoContacto);
  console.log(municipioContacto);

  if (tipoOperacion === "alta") {
    if (
      !nombreContacto ||
      !compañiaContacto ||
      !correoContacto ||
      !telefonoContacto ||
      !direccion1Contacto ||
      !direccion2Contacto ||
      !codigoContacto ||
      !estadoContacto ||
      !municipioContacto
    ) {
      return false;
    }

    return true; // Todos los campos obligatorios están completos
  } else {
    if (
      !nombreContacto ||
      !direccion1Contacto ||
      !direccion2Contacto ||
      !codigoContacto ||
      !estadoContacto ||
      !municipioContacto
    ) {
      return false;
    }

    return true; // Todos los campos obligatorios están completos
  }
}
function validarPartidas(partidasData) {
  partidasData.forEach((partida) => {
    if (partida.CVE_PRODSERV == "" || partida.CVE_UNIDAD == "") {
      console.log(partida);
      return false;
    }
  });
  return true;
}
function obtenerDatosFormulario() {
  const now = new Date(); // Obtiene la fecha y hora actual
  const fechaActual = `${now.getFullYear()}-${String(
    now.getMonth() + 1
  ).padStart(2, "0")}-${String(now.getDate()).padStart(2, "0")} ${String(
    now.getHours()
  ).padStart(2, "0")}:${String(now.getMinutes()).padStart(2, "0")}:${String(
    now.getSeconds()
  ).padStart(2, "0")}`;
  const diaAlta =
    new Date().toISOString().slice(0, 10).replace("T", " ") + " 00:00:00.000";
  //const fechaActual = now.toISOString().slice(0, 10); // Formato YYYY-MM-DD

  const campoEntrega = document.getElementById("entrega").value;
  //alert(fechaActual);
  // Si el usuario ha ingresado una fecha, se usa esa, de lo contrario, se usa la fecha actual
  //const entrega = campoEntrega ? campoEntrega : fechaActual;
  //const diaAlta = document.getElementById("diaAlta").value; // Fecha y hora
  const formularioData = {
    claveVendedor: document.getElementById("vendedor").value,
    //claveVendedor: "1",
    factura: document.getElementById("factura").value,
    numero: document.getElementById("numero").value,
    diaAlta: diaAlta, // Fecha y hora
    fechaAlta: fechaActual, // Fecha y hora
    cliente: document.getElementById("cliente").value,
    rfc: document.getElementById("rfc").value,
    nombre: document.getElementById("nombre").value,
    calle: document.getElementById("calle").value,
    numE: document.getElementById("numE").value,
    numI: document.getElementById("numI").value,
    descuentoCliente: document.getElementById("descuentoCliente").value,
    //descuentoFin: document.getElementById("descuentoFin").value,
    colonia: document.getElementById("colonia").value,
    codigoPostal: document.getElementById("codigoPostal").value,
    poblacion: document.getElementById("poblacion").value,
    pais: document.getElementById("pais").value,
    regimenFiscal: document.getElementById("regimenFiscal").value,
    entrega: document.getElementById("entrega").value,
    vendedor: document.getElementById("vendedor").value,
    condicion: document.getElementById("condicion").value,
    comision: document.getElementById("comision").value,
    enviar: document.getElementById("enviar").value,
    almacen: document.getElementById("almacen").value,
    destinatario: document.getElementById("destinatario").value, //
    conCredito: document.getElementById("conCredito").value,
    //conCredito: "S",
    token: document.getElementById("csrf_token").value,
    ordenCompra: document.getElementById("supedido").value,
    tipoOperacion: document.getElementById("tipoOperacion").value,
    CVE_ESQIMPU: document.getElementById("CVE_ESQIMPU").value, // Mover
    observaciones: document.getElementById("observaciones").value,
  };
  return formularioData;
}
function extraerDatosEnvio() {
  const now = new Date(); // Obtiene la fecha y hora actual
  const fechaActual = `${now.getFullYear()}-${String(
    now.getMonth() + 1
  ).padStart(2, "0")}-${String(now.getDate()).padStart(2, "0")} ${String(
    now.getHours()
  ).padStart(2, "0")}:${String(now.getMinutes()).padStart(2, "0")}:${String(
    now.getSeconds()
  ).padStart(2, "0")}`;
  const diaAlta =
    new Date().toISOString().slice(0, 10).replace("T", " ") + " 00:00:00.000";
  //const fechaActual = now.toISOString().slice(0, 10); // Formato YYYY-MM-DD
  //alert(fechaActual);
  // Si el usuario ha ingresado una fecha, se usa esa, de lo contrario, se usa la fecha actual
  //const entrega = campoEntrega ? campoEntrega : fechaActual;
  //const diaAlta = document.getElementById("diaAlta").value; // Fecha y hora
  const envioData = {
    claveVendedor: document.getElementById("vendedor").value,
    //claveVendedor: "1",
    idDocumento: document.getElementById("idDatos").value,
    nombreContacto: document.getElementById("nombreContacto").value,
    compañiaContacto: document.getElementById("compañiaContacto").value,
    diaAlta: diaAlta, // Fecha y hora
    fechaAlta: fechaActual, // Fecha y hora
    telefonoContacto: document.getElementById("telefonoContacto").value,
    correoContacto: document.getElementById("correoContacto").value,
    direccion1Contacto: document.getElementById("direccion1Contacto").value,
    direccion2Contacto: document.getElementById("direccion2Contacto").value,
    codigoContacto: document.getElementById("codigoContacto").value,

    estadoContacto: $("#estadoContacto option:selected").data("descripcion"),
    municipioContacto: $("#municipioContacto option:selected").data(
      "descripcion"
    ),
  };
  return envioData;
}
function obtenerDatosPartidas() {
  const partidasData = [];
  const filas = document.querySelectorAll("#tablaProductos tbody tr");

  filas.forEach((fila) => {
    const descuentoInput = fila.querySelector(".descuento").value;
    const partida = {
      cantidad: fila.querySelector(".cantidad").value,
      producto: fila.querySelector(".producto").value,
      unidad: fila.querySelector(".unidad").value,
      // Si el campo descuento está vacío, se asigna 0.
      descuento: descuentoInput.trim() === "" ? 0 : descuentoInput,
      ieps: fila.querySelector(".ieps").value,
      impuesto2: fila.querySelector(".impuesto2").value,
      isr: fila.querySelector(".impuesto3").value,
      iva: fila.querySelector(".iva").value,
      comision: fila.querySelector(".comision").value,
      precioUnitario: fila.querySelector(".precioUnidad").value,
      subtotal: fila.querySelector(".subtotalPartida").value,
      CVE_UNIDAD: fila.querySelector(".CVE_UNIDAD").value,
      CVE_PRODSERV: fila.querySelector(".CVE_PRODSERV").value,
      COSTO_PROM: fila.querySelector(".COSTO_PROM").value,
    };
    partidasData.push(partida);
  });
  return partidasData;
}
async function enviarDatosBackend(formularioData, partidasData, envioData) {
  // 2) Ejecuta eliminaciones pendientes, una por una
  for (const del of eliminacionesPendientes) {
    const fdDel = new FormData();
    fdDel.append("numFuncion", "9");
    fdDel.append("clavePedido", String(del.clavePedido));
    fdDel.append("numPar", String(del.numPar));

    const r = await fetch("../Servidor/PHP/ventas.php", {
      method: "POST",
      body: fdDel,
    });
    const j = await r.json();
    if (!j || j.success !== true) {
      // Si falla una eliminación, avisa y conserva en cola para reintentar si quieres
      console.error("Error al eliminar partida:", del, j);
      Swal.fire({
        title: "Aviso",
        text: j?.message || "Fallo al eliminar una partida en SAE.",
        icon: "warning",
      });
      // Opcional: vuelve a dejarla en la cola
      // continue; // y no la vacíes
    }
  }

  // 3) Si todo fue bien, limpia la cola
  eliminacionesPendientes = [];

  const creditoPedido = document.getElementById("conCredito").value;
  const tipoOperacion = document.getElementById("tipoOperacion").value;
  //Se crear un FormData para enviar los datos
  const formData = new FormData();
  let url = "";
  //Se agrega el numero de funcion
  /*if (creditoPedido == "S") {
    formData.append("numFuncion", "1");
    //Se los datos del pedido
    formData.append("formulario", JSON.stringify(formularioData));
    //Se agrega las partidas
    formData.append("partidas", JSON.stringify(partidasData));
    //Se agrega los datos de envio
    formData.append("envio", JSON.stringify(envioData));
    if (tipoOperacion == "alta") {
      url = "../Servidor/PHP/pedidosCredito.php";
    } else {
      url = "../Servidor/PHP/editarPedido.php";
    }
  } else {
    formData.append("numFuncion", "8");
    //Se los datos del pedido
    formData.append("formulario", JSON.stringify(formularioData));
    //Se agrega las partidas
    formData.append("partidas", JSON.stringify(partidasData));
    //Se agrega los datos de envio
    formData.append("envio", JSON.stringify(envioData));

    url = "../Servidor/PHP/ventas.php";
  }*/
  formData.append("numFuncion", "1");
  //Se los datos del pedido
  formData.append("formulario", JSON.stringify(formularioData));
  //Se agrega las partidas
  formData.append("partidas", JSON.stringify(partidasData));
  //Se agrega los datos de envio
  formData.append("envio", JSON.stringify(envioData));

  url = "../Servidor/PHP/editarPedido.php";
  fetch(url, {
    method: "POST",
    body: formData,
  })
    .then((response) => {
      console.log("Response completa:", response);
      return response.text(); // Obtener la respuesta como texto para depuración
    })
    .then((text) => {
      console.log("Texto recibido del servidor:", text);

      try {
        return JSON.parse(text); // Intentar convertir a JSON
      } catch (error) {
        console.error(
          "Error al convertir a JSON:",
          error,
          "Texto recibido:",
          text
        );
        throw new Error("El servidor no devolvió una respuesta inválida.");
      }
    })
    .then((data) => {
      if (!data) return;
      console.log("Respuesta del servidor:", data);

      if (data.success) {
        //Mensaje cuando el pedido se realizo con exito
        Swal.fire({
          title: "¡Pedido guardado exitosamente!",
          text: data.message || "El pedido se procesó correctamente.",
          icon: "success",
          confirmButtonText: "Aceptar",
        }).then(() => {
          // Redirigir al usuario o realizar otra acción
          window.location.href = "Ventas.php";
        });
      } else if (data.autorizacion) {
        //Mensaje cuando se tiene que autorizar el pedido por un administrador
        Swal.fire({
          title: "Saldo vencido",
          text:
            data.message || "El pedido se procesó pero debe ser autorizado.",
          icon: "warning",
          confirmButtonText: "Entendido",
        }).then(() => {
          // Redirigir al usuario o realizar otra acción
          window.location.href = "Ventas.php";
        });
      } else if (data.exist) {
        //Mensaje cuando no hay existencias para algunos productos
        Swal.fire({
          title: "Error al guardar el pedido",
          //Creacion de Mensaje con los productos, exitencias y apartados de estos
          html: `
            <p>${
              data.message ||
              "No hay suficientes existencias para algunos productos."
            }</p>
            <p><strong>Productos sin existencias:</strong></p>
            <ul>
              ${data.productosSinExistencia
                .map(
                  (producto) => `
                  <li>
                    <strong>Producto:</strong> ${producto.producto}, 
                    <strong>Existencias Totales:</strong> ${
                      producto.existencias || 0
                    }, 
                    <strong>Apartados:</strong> ${producto.apartados || 0}, 
                    <strong>Disponibles:</strong> ${producto.disponible || 0}
                  </li>
                `
                )
                .join("")}
            </ul>
          `,
          icon: "error",
          confirmButtonText: "Aceptar",
        });
      } else if (data.cxc) {
        //Mensaje cuando no se encontro un anticipo y tiene 72 horas para pagar
        Swal.fire({
          title: "Cuenta por pagar",
          text: data.message || "El cliente tiene una cuenta por pagar",
          icon: "warning",
          confirmButtonText: "Aceptar",
        }).then(() => {
          // Redirigir al usuario o realizar otra acción
          window.location.href = "Ventas.php";
        });
      } else if (data.telefono) {
        //Mensaje cuando solo se le pudo notificar al cliente por WhatsApp
        Swal.fire({
          title: "Pedido Guardado",
          text: data.message || "",
          icon: "info",
          confirmButtonText: "Aceptar",
        }).then(() => {
          // Redirigir al usuario o realizar otra acción
          window.location.href = "Ventas.php";
        });
      } else if (data.correo) {
        //Mensaje cuando solo se le pudo notificar al cliente por correo
        Swal.fire({
          title: "Pedido Guardado",
          text: data.message || "",
          icon: "info",
          confirmButtonText: "Aceptar",
        }).then(() => {
          // Redirigir al usuario o realizar otra acción
          window.location.href = "Ventas.php";
        });
      } else if (data.notificacion) {
        //Mensaje cuando no se pudo notificar al cliente y se le notifico al vendedor
        Swal.fire({
          title: "Pedido Guardado",
          text: data.message || "",
          icon: "info",
          confirmButtonText: "Aceptar",
        }).then(() => {
          // Redirigir al usuario o realizar otra acción
          window.location.href = "Ventas.php";
        });
      } else {
        Swal.fire({
          title: "Error al Guardar el Pedido",
          text: data.message || "Ocurrió un error inesperado.",
          icon: "warning",
          confirmButtonText: "Aceptar",
        }).then(() => {
          // Redirigir al usuario o realizar otra acción
          window.location.href = "Ventas.php";
        });
      }
    })
    .catch((error) => {
      console.error("Error al enviar los datos:", error);
      Swal.fire({
        title: "Error al enviar los datos",
        text: error.message,
        icon: "error",
        confirmButtonText: "Aceptar",
      }).then(() => {
        // Redirigir al usuario o realizar otra acción
        window.location.href = "Ventas.php";
      });
    });
  return false;
}
document.getElementById("añadirPartida").addEventListener("click", function () {
  agregarFilaPartidas();
});
document
  .getElementById("tablaProductos")
  .addEventListener("keydown", function (event) {
    if (
      event.key === "Tab" &&
      event.target.classList.contains("descuento") // Solo si el evento ocurre en un input de cantidad
    ) {
      agregarFilaPartidas();
    }
  });
document
  .getElementById("formularioPedido")
  .addEventListener("keydown", function (event) {
    // Si Tab y el target es el input con id "enviar"
    if (event.key === "Tab" && event.target.id === "enviar") {
      agregarFilaPartidas();
    }
  });
document.addEventListener("DOMContentLoaded", function () {
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
    obtenerEstados();
    obtenerDatosEnvioEditar(pedidoID); // Función para cargar partidas del pedido
  } else {
    sessionStorage.setItem("clienteSeleccionado", false);
    clienteSeleccionado = false;
    // Si es un nuevo pedido (pedidoID es null)
    console.log("Preparando formulario para un nuevo pedido...");
    obtenerFecha(); // Establecer la fecha inicial del pedido
    limpiarTablaPartidas(); // Limpiar la tabla de partidas para el nuevo pedido
    obtenerFolioSiguiente(); // Generar el siguiente folio para el pedido
  }
});

$(document).ready(function () {
  $("#actualizarPedido").click(async function (event) {
    //Funcion que se activa al guardar el pedido
    event.preventDefault();
    const $btn = $(this);
    // Desactiva el botón al hacer clic
    $btn.prop("disabled", true);
    const clienteSeleccionado =
      sessionStorage.getItem("clienteSeleccionado") === "true";
    // Obtener el ID actual del pedido desde el formulario
    let id = document.getElementById("numero").value;
    console.log("ID actual del pedido:", id);

    try {
      // Obtener el siguiente folio del backend
      const folio = await obtenerFolioSiguiente();
      console.log("Folio obtenido:", folio);
      // Verificar si es una alta de pedido o una edicion
      if (folio == id) {
        console.log("Guardando nuevo pedido...");
        id = 0;
      } else {
        console.log("Editando pedido existente...");
        document.getElementById("numero").value = id;
      }

      console.log(
        "Número final en el formulario:",
        document.getElementById("numero").value
      );
      if (!clienteSeleccionado) {
        //Mensaje cuando se quiera guardar pero no hay cliente
        Swal.fire({
          title: "Aviso",
          text: "Debes Tener un Cliente para Guardar.",
          icon: "error",
          confirmButtonText: "Entendido",
        });
        return;
      }
      const tablaProductos = document.querySelector("#tablaProductos tbody");
      const filas = tablaProductos.querySelectorAll("tr");
      if (!filas[filas.length - 1]) {
        //Mensaje cuando se quiera guardar pero no hay partidas
        Swal.fire({
          title: "Aviso",
          text: "Debes Crear una Partida Antes de Guardar.",
          icon: "warning",
          confirmButtonText: "Entendido",
        });
        return;
      }

      const ultimaFila = filas[filas.length - 1];
      const ultimoProducto = ultimaFila.querySelector(".producto").value.trim();
      const ultimaCantidad =
        parseFloat(ultimaFila.querySelector(".cantidad").value) || 0;
      const ultimoTotal =
        parseFloat(ultimaFila.querySelector(".subtotalPartida").value) || 0;

      if (ultimoProducto === "" || ultimaCantidad === 0) {
        //Mensaje cuando se quiera guardar pero no hay producto seleccionado o su cantidad es 0
        Swal.fire({
          title: "Aviso",
          text: "Debes seleccionar un producto y una cantidad mayor a 0 antes de guardar el pedido.",
          icon: "warning",
          confirmButtonText: "Entendido",
        });
        return;
      }
      if (ultimoTotal === 0) {
        //Mensaje cuando se quiera guardar el total es 0
        Swal.fire({
          title: "Aviso",
          text: "No puedes realizar un pedido con costo 0.",
          icon: "warning",
          confirmButtonText: "Entendido",
        });
        return;
      }
      const vendedor = document.getElementById("vendedor").value;
      if (vendedor === "") {
        //Mensaje cuando se quiera guardar pero no hay producto seleccionado o su cantidad es 0
        Swal.fire({
          title: "Aviso",
          text: "No tienes un Vendedor Seleccionado.",
          icon: "warning",
          confirmButtonText: "Entendido",
        });
        return;
      }

      guardarPedido(id);
      return false; // Evita la recarga de la página
    } catch (error) {
      console.error("Error al obtener el folio:", error);
      return false; // Previene la recarga en caso de error
    } finally {
      $btn.prop("disabled", false); // ← Lo habilitas si hubo error inesperado
    }
  });
  $("#cancelarPedido").click(function () {
    //Boton para redigir a la seccion de pedidos
    window.location.href = "Ventas.php";
  });
});
