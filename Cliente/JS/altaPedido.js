// --------Funciones para mostrar articulos----------------

// -----------------------------------------------------------------------------

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
// Funcion para validar las exisencias de un producto
function validarExistencias(nuevaFila, cantidad) {
  const cve_art = nuevaFila.querySelector(".producto");
  const subtotal = nuevaFila.querySelector(".subtotalPartida");
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
        //Se obtienen las existencias del almacen 1 (MULT)
        let existenciasAlmacen = datos.ExistenciasAlmacen;
        //Se optienen las existencias en general (INVE)
        let existenciasTotales = datos.ExistenciasTotales;
        //Obtenemos los apartados del priducto
        let apartados = datos.APART;
        //Si los apartados son negativos, se vuelven 0
        if (apartados < 0) {
          apartados = 0;
        }
        //Si las existencias del almacen 1 son negativas, se vuelven 0
        if (existenciasAlmacen < 0) {
          existenciasAlmacen = 0;
        }
        console.log("apartados: ", apartados);
        //Se obtienen las existencias reales
        let existenciasReales = existenciasAlmacen - apartados;
        console.log("existenciasReales: ", existenciasAlmacen - apartados);
        //Si las existencias reales son negativas, se vuelven 0
        if (existenciasReales < 0) {
          existenciasReales = 0;
        }
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
          subtotal.value = 0;
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
function mostrarTodosProductos(element) {
  // numFuncion=5 indica en PHP que queremos la acción “obtener productos”
  const numFuncion = 5;
  const chechk = element.checked;
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
        mostrarListaProductosCheck(response.productos);
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
  let existenciasReal = 0;

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
      if (producto.EXIST < 0) {
        producto.EXIST = 0;
      }
      if (producto.APART < 0) {
        producto.APART = 0;
      }
      existenciasReal = producto.EXIST - producto.APART;
      if (existenciasReal < 0) {
        existenciasReal = 0;
      }
      //celdaExist.textContent = producto.EXIST;
      celdaExist.textContent = existenciasReal;
      fila.appendChild(celdaClave);
      fila.appendChild(celdaDescripcion);
      fila.appendChild(celdaExist);
      fila.onclick = async function () {
        //if (producto.EXIST > 0) {
        if (existenciasReal > 0) {
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

// FUNCION PARA LISTAR Productos
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
      if (producto.EXIST < 0) {
        producto.EXIST = 0;
      }
      if (producto.APART < 0) {
        producto.APART = 0;
      }
      existenciasReal = producto.EXIST - producto.APART;
      if (existenciasReal < 0) {
        existenciasReal = 0;
      }
      //celdaExist.textContent = producto.EXIST;
      celdaExist.textContent = existenciasReal;
      fila.appendChild(celdaClave);
      fila.appendChild(celdaDescripcion);
      fila.appendChild(celdaExist);
      fila.onclick = async function () {
        //if (producto.EXIST > 0) {
        if (existenciasReal > 0) {
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

// Cierra el modal usando la API de Bootstrap
function cerrarModal() {
  const modal = bootstrap.Modal.getInstance(
    document.getElementById("modalProductos")
  );
  if (modal) {
    modal.hide();
  }
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
  if (creditoPedido == "S") {
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
  }
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

// MODAL MOSTRAR CLIENTES

// Variables globales
let clienteSeleccionado = false;
let clienteId = null; // Variable para almacenar el ID del cliente
let clientesData = []; // Para almacenar los datos originales de los clientes

// Función para abrir el modal y cargar los clientes
function abrirModalClientes() {
  const modalElement = document.getElementById("modalClientes");
  const modal = new bootstrap.Modal(modalElement);
  const datosClientes = document.getElementById("datosClientes"); //Tbody de la tabla del modal
  const token = document.getElementById("csrf_token").value; //Token de seguridad

  // Solicitar datos al servidor
  $.post(
    "../Servidor/PHP/clientes.php",
    { numFuncion: "9", token: token },
    function (response) {
      try {
        if (response.success && response.data) {
          clientesData = response.data; // Guardar los datos originales
          datosClientes.innerHTML = ""; // Limpiar la tabla

          // Renderizar los datos en la tabla
          renderClientes(clientesData);
        } else {
          datosClientes.innerHTML =
            '<tr><td colspan="4">No se encontraron clientes</td></tr>';
        }
      } catch (error) {
        console.error("Error al cargar clientes:", error);
      }
    }
  );
  //Abrir modal
  modal.show();
}

// Función para renderizar los clientes en la tabla
function renderClientes(clientes) {
  const datosClientes = document.getElementById("datosClientes");
  datosClientes.innerHTML = "";
  clientes.forEach((cliente) => {
    const fila = document.createElement("tr");
    //Contruir fila con datos
    fila.innerHTML = `
            <td>${cliente.CLAVE}</td>
            <td>${cliente.NOMBRE}</td>
            <td>${cliente.TELEFONO || "Sin teléfono"}</td>
            <td>$${parseFloat(cliente.SALDO || 0).toFixed(2)}</td>
        `;

    // Agregar evento de clic para seleccionar cliente desde el modal
    fila.addEventListener("click", () => seleccionarClienteDesdeModal(cliente));

    datosClientes.appendChild(fila);
  });
}

// Función para seleccionar un cliente desde el modal
function seleccionarClienteDesdeModal(cliente) {
  const clienteInput = document.getElementById("cliente");
  clienteInput.value = `${cliente.CLAVE} - ${cliente.NOMBRE}`; // Actualizar el valor del input
  clienteId = cliente.CLAVE; // Guardar el ID del cliente

  // Actualizar estado de cliente seleccionado
  sessionStorage.setItem("clienteSeleccionado", true);
  llenarDatosCliente(cliente); //Llenar los campos del cliente con su informacion
  cerrarModalClientes(); // Cerrar el modal
  desbloquearCampos(); //Desbloque los campos para el formulario
}

function validarCreditoCliente(clienteId) {
  //Borrar los mensajes de credito
  //Mensaje Credito
  let creditoVal = null;
  fetch(`../Servidor/PHP/clientes.php?clienteId=${clienteId}&numFuncion=3`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const { conCredito, limiteCredito, saldo } = data;
        if (conCredito === "S") {
          Swal.fire({
            title: "Cliente válido",
            text: "El cliente tiene crédito disponible.",
            icon: "success",
          });
          $("#conCredito").val("S");
        } else {
          Swal.fire({
            title: "Sin crédito",
            text: "El cliente no maneja crédito.",
            icon: "info",
          });
          $("#conCredito").val("N");
        }
      } else {
        Swal.fire({
          title: "Aviso",
          text: data.message || "No se pudo validar el cliente.",
          icon: "error",
        });
      }
    })
    .catch((error) => {
      console.error("Error al validar el cliente:", error);
      Swal.fire({
        title: "Aviso",
        text: "Ocurrió un error al validar el cliente.",
        icon: "error",
      });
    });
}

// Función para mostrar sugerencias de clientes
function showCustomerSuggestions() {
  const clienteInput = document.getElementById("cliente");
  const clienteInputValue = clienteInput.value.trim();
  const sugerencias = document.getElementById("clientesSugeridos");

  sugerencias.classList.remove("d-none"); // Mostrar las sugerencias

  // Generar las sugerencias en base al texto ingresado
  const clientesFiltrados = clientesData.filter((cliente) =>
    cliente.NOMBRE.toLowerCase().includes(clienteInputValue.toLowerCase())
  );

  sugerencias.innerHTML = ""; // Limpiar las sugerencias anteriores

  if (clientesFiltrados.length === 0) {
    sugerencias.innerHTML = "<li>No se encontraron coincidencias</li>";
  } else {
    clientesFiltrados.forEach((cliente) => {
      const sugerencia = document.createElement("li");
      sugerencia.textContent = `${cliente.CLAVE} - ${cliente.NOMBRE}`;
      sugerencia.classList.add("suggestion-item");

      // Evento para seleccionar cliente desde las sugerencias
      sugerencia.addEventListener("click", (e) => {
        e.stopPropagation(); // Evitar que el evento de clic global oculte las sugerencias
        seleccionarClienteDesdeSugerencia(cliente);
      });

      sugerencias.appendChild(sugerencia);
    });
  }
}
// Función para seleccionar un cliente desde las sugerencias
function seleccionarClienteDesdeSugerencia(cliente) {
  const clienteInput = document.getElementById("cliente");
  clienteInput.value = cliente.CLAVE; // Solo guarda la clave, sin el nombre
  clienteId = cliente.CLAVE; // Guardar el ID del cliente

  // Actualizar estado de cliente seleccionado
  sessionStorage.setItem("clienteSeleccionado", true);

  // Limpiar y ocultar sugerencias
  const sugerencias = document.getElementById("clientesSugeridos");
  sugerencias.innerHTML = ""; // Limpiar las sugerencias
  sugerencias.classList.add("d-none"); // Ocultar las sugerencias
  llenarDatosCliente(cliente);
  desbloquearCampos();
}
// Función para mostrar sugerencias de prodcuctos
function showCustomerSuggestionsProductos() {
  const productoInput = document.getElementsByClassName("producto");
  const productoInputValue = productoInput.value.trim();
  const sugerencias = document.getElementById("productosSugeridos");

  sugerencias.classList.remove("d-none"); // Mostrar las sugerencias

  // Generar las sugerencias en base al texto ingresado
  const productosFiltrados = productosData.filter((producto) =>
    producto.NOMBRE.toLowerCase().includes(productoInputValue.toLowerCase())
  );

  sugerencias.innerHTML = ""; // Limpiar las sugerencias anteriores

  if (productosFiltrados.length === 0) {
    sugerencias.innerHTML = "<li>No se encontraron coincidencias</li>";
  } else {
    productosFiltrados.forEach((producto) => {
      const sugerencia = document.createElement("li");
      sugerencia.textContent = `${producto.CLAVE} - ${producto.NOMBRE}`;
      sugerencia.classList.add("suggestion-item");

      // Evento para seleccionar cliente desde las sugerencias
      sugerencia.addEventListener("click", (e) => {
        e.stopPropagation(); // Evitar que el evento de clic global oculte las sugerencias
        seleccionarProductoDesdeSugerencia(producto);
      });

      sugerencias.appendChild(sugerencia);
    });
  }
}
// Función para seleccionar un produto desde las sugerencias
async function seleccionarProductoDesdeSugerencia(inputProducto, producto) {
  inputProducto.val(`${producto.CVE_ART}`); // Mostrar el producto seleccionado
  const filaProd = inputProducto.closest("tr")[0]; // Asegurar que obtenemos el elemento DOM
  const CVE_UNIDAD = filaProd.querySelector(".CVE_UNIDAD");
  const CVE_PRODSERV = filaProd.querySelector(".CVE_PRODSERV");
  const COSTO_PROM = filaProd.querySelector(".COSTO_PROM");
  CVE_UNIDAD.value = `${producto.CVE_UNIDAD}`;
  CVE_PRODSERV.value = `${producto.CVE_PRODSERV}`;
  COSTO_PROM.value = `${producto.COSTO_PROM}`;
  console.log(producto.COSTO_PROM);
  console.log(COSTO_PROM.value);
  if (!filaProd) {
    console.error("Error: No se encontró la fila del producto.");
    return; // 🚨 Salir de la función si `filaProd` no es válido
  }

  // Convertir `filaProd` en un objeto jQuery para compatibilidad
  const $filaProd = $(filaProd);

  // Actualizar el campo de esquema de impuestos
  $("#CVE_ESQIMPU").val(producto.CVE_ESQIMPU);

  // Actualizar la unidad de medida si el campo existe
  const campoUnidad = $filaProd.find(".unidad");
  if (campoUnidad.length) {
    campoUnidad.val(producto.UNI_MED);
  }

  // Desbloquear y establecer cantidad en 0
  const campoCantidad = $filaProd.find("input.cantidad");
  if (campoCantidad.length) {
    campoCantidad.prop("readonly", false).val(0);
  }

  // Ocultar sugerencias después de seleccionar
  $filaProd.find(".suggestions-list-productos").empty().hide();

  // Obtener precio del producto y actualizar la fila
  await completarPrecioProducto(producto.CVE_ART, filaProd); // Pasar el nodo DOM, no jQuery
}
function llenarDatosProducto(producto) {}
function desbloquearCampos() {
  $(
    "#entrega, #supedido, #entrega, #condicion, #descuentofin, #enviar, #datosEnvio, #observaciones"
  ).prop("disabled", false);
}
function llenarDatosCliente(cliente) {
  $("#rfc").val(cliente.RFC || "");
  $("#nombre").val(cliente.NOMBRE || "");
  $("#calle").val(cliente.CALLE || "");
  //$("#enviar").val(cliente.CALLE || "");
  $("#numE").val(cliente.NUMEXT || "");
  $("#numI").val(cliente.NUMINT || "");
  $("#colonia").val(cliente.COLONIA || "");
  $("#codigoPostal").val(cliente.CODIGO || "");
  $("#poblacion").val(cliente.LOCALIDAD || "");
  $("#pais").val(cliente.PAIS || "");
  $("#regimenFiscal").val(cliente.REGIMEN_FISCAL || "");
  $("#cliente").val(cliente.CLAVE || "");
  $("#listaPrecios").val(cliente.LISTA_PREC || "");
  $("#descuentoCliente").val(cliente.DESCUENTO || 0);
  // Validar el crédito del cliente
  validarCreditoCliente(cliente.CLAVE);
}
// Filtrar clientes según la entrada de búsqueda en el modal
function filtrarClientes() {
  const criterio = document.getElementById("filtroCriterioClientes").value;
  const busqueda = document
    .getElementById("campoBusquedaClientes")
    .value.toLowerCase();

  const clientesFiltrados = clientesData.filter((cliente) => {
    const valor = cliente[criterio]?.toLowerCase() || "";
    return valor.includes(busqueda);
  });

  renderClientes(clientesFiltrados);
}
// Boton eliminar campo INPUT
const inputCliente = $("#cliente");
const clearButton = $("#clearInput");
const suggestionsList = $("#clientesSugeridos");
const suggestionsListProductos = $("#productosSugeridos");
// Mostrar/ocultar el botón "x"
function toggleClearButton() {
  if (inputCliente.val().trim() !== "") {
    clearButton.show();
  } else {
    clearButton.hide();
  }
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
function obtenerMunicipios(edo, municipio) {
  // Habilitamos el select
  //$("#estadoContacto").prop("disabled", false);
  $.ajax({
    url: "../Servidor/PHP/ventas.php",
    method: "POST",
    data: { numFuncion: "23", estado: edo },
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
        $("#municipioContacto").val(municipio);
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
function obtenerEstadosNuevos() {
  // Habilitamos el select
  $("#estadoNuevoContacto").prop("disabled", false);

  $.ajax({
    url: "../Servidor/PHP/ventas.php",
    method: "POST",
    data: { numFuncion: "22" },
    dataType: "json",
    success: function (resEstado) {
      if (resEstado.success && Array.isArray(resEstado.data)) {
        const $estadoNuevoContacto = $("#estadoNuevoContacto");
        $estadoNuevoContacto.empty();
        $estadoNuevoContacto.append(
          "<option selected disabled>Selecciona un Estado</option>"
        );
        // Filtrar según el largo del RFC
        resEstado.data.forEach((estado) => {
          $estadoNuevoContacto.append(
            `<option value="${estado.Clave}" 
                data-Pais="${estado.Pais}">
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
function obtenerMunicipiosNuevos() {
  // Habilitamos el select
  $("#municipioNuevoContacto").prop("disabled", false);
  const estado = document.getElementById("estadoNuevoContacto").value;
  $.ajax({
    url: "../Servidor/PHP/ventas.php",
    method: "POST",
    data: { numFuncion: "23", estado: estado },
    dataType: "json",
    success: function (resMunicipio) {
      if (resMunicipio.success && Array.isArray(resMunicipio.data)) {
        const $municipioNuevoContacto = $("#municipioNuevoContacto");
        $municipioNuevoContacto.empty();
        $municipioNuevoContacto.append(
          "<option selected disabled>Selecciona un Municipio</option>"
        );
        // Filtrar según el largo del RFC
        resMunicipio.data.forEach((municipio) => {
          $municipioNuevoContacto.append(
            `<option value="${municipio.Clave}" 
                data-estado="${municipio.Estado}"
                data-descripcion="${municipio.Descripcion || ""}">
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
function obtenerMunicipiosPedido() {
  // Habilitamos el select
  $("#municipioContacto").prop("disabled", false);
  const estado = document.getElementById("estadoContacto").value;
  $.ajax({
    url: "../Servidor/PHP/ventas.php",
    method: "POST",
    data: { numFuncion: "23", estado: estado },
    dataType: "json",
    success: function (resMunicipio) {
      if (resMunicipio.success && Array.isArray(resMunicipio.data)) {
        const $municipioNuevoContacto = $("#municipioContacto");
        $municipioNuevoContacto.empty();
        $municipioNuevoContacto.append(
          "<option selected disabled>Selecciona un Municipio</option>"
        );
        // Filtrar según el largo del RFC
        resMunicipio.data.forEach((municipio) => {
          $municipioNuevoContacto.append(
            `<option value="${municipio.Clave}" 
                data-estado="${municipio.Estado}"
                data-descripcion="${municipio.Descripcion || ""}">
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
// Limpiar todos los campos
function clearAllFields() {
  // Limpiar valores de los inputs
  $("#cliente").val("");
  $("#rfc").val("");
  $("#nombre").val("");
  $("#calle").val("");
  $("#numE").val("");
  $("#numI").val("");
  $("#colonia").val("");
  $("#codigoPostal").val("");
  $("#poblacion").val("");
  $("#pais").val("");
  $("#regimenFiscal").val("");
  $("#destinatario").val("");
  $("#listaPrecios").val("");

  // Limpiar la lista de sugerencias
  suggestionsList.empty().hide();

  // Ocultar el botón "x"
  clearButton.hide();
}
// Vincular los eventos de búsqueda y cambio de criterio
document.addEventListener("DOMContentLoaded", () => {
  document
    .getElementById("campoBusquedaClientes")
    .addEventListener("input", filtrarClientes);
  document
    .getElementById("filtroCriterioClientes")
    .addEventListener("change", filtrarClientes);

  const clienteInput = document.getElementById("cliente");
  clienteInput.addEventListener("input", () => {
    if (!clienteSeleccionado) {
      showCustomerSuggestions();
    }
  });
});
// Ocultar sugerencias al hacer clic fuera del input
document.addEventListener("click", function (e) {
  const sugerencias = document.getElementById("clientesSugeridos");
  const clienteInput = document.getElementById("cliente");

  if (!sugerencias.contains(e.target) && !clienteInput.contains(e.target)) {
    sugerencias.classList.add("d-none");
  }
});
// Función para cerrar el modal
function cerrarModalClientes() {
  const modalElement = document.getElementById("modalClientes");
  const modal = bootstrap.Modal.getInstance(modalElement);
  modal.hide();
}
function obtenerDatosEnvio() {
  const clienteId = document.getElementById("cliente").value;

  $.ajax({
    url: "../Servidor/PHP/clientes.php",
    method: "GET",
    data: { numFuncion: "5", clave: clienteId }, // Llamar la función para obtener vendedores
    success: function (response) {
      try {
        const res =
          typeof response === "string" ? JSON.parse(response) : response;

        if (res.success && Array.isArray(res.data)) {
          const selectDatosEnvio = $("#selectDatosEnvio");
          selectDatosEnvio.empty();
          selectDatosEnvio.append(
            "<option selected disabled>Selecciona un Dato</option>"
          );

          res.data.forEach((dato) => {
            selectDatosEnvio.append(
              `<option value="${dato.id}" data-id="${dato.idDocumento}" data-titulo="${dato.tituloEnvio}">
                ${dato.tituloEnvio}
              </option>`
            );
          });

          // Habilitar el select si hay vendedores disponibles
          //selectDatosEnvio.prop("disabled", res.data.length === 0);
        } else {
          /*Swal.fire({
            icon: "warning",
            title: "Aviso",
            text: res.message || "No se Encontraron Datos de Envio.",
          });*/
          //$("#selectDatosEnvio").prop("disabled", true);
        }
      } catch (error) {
        console.error("Error al Procesar la Respuesta:", error);
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Error al Cargar Datos de Envio.",
        });
      }
    },
    error: function () {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Error al Obtener la Lista de Datos.",
      });
    },
  });
}
function actualizarDatos() {
  /*const idDocumento = document.getElementById("idDatos").value;
  const nombreContacto = document.getElementById("nombreContacto").value;*/
  Swal.fire({
    icon: "success",
    title: "Exito",
    text: "Se Establecieron los Datos de Envio.",
    confirmButtonText: "Aceptar",
  }).then(() => {
    const titulo = document.getElementById("titutoDatos").value;
    $("#enviar").val(titulo);
    //alert(titulo);
    $("#modalEnvio").modal("hide");
  });
  /*$.ajax({
    url: "../Servidor/PHP/clientes.php",
    method: "POST",
    data: {
      numFuncion: "8",
      idDocumento: idDocumento,
      nombreContacto: nombreContacto,
    },
    dataType: "json",
    success: function (envios) {
      if (envios.success) {
        
      } else {
        Swal.fire({
          icon: "warning",
          title: "Aviso",
          text: envios.message || "No se encontraron datos de Envio.",
        });
      }
    },
    error: function () {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Error al obtener los datos de envio.",
      });
    },
  });*/
}
function guardarDatosEnvio() {
  // 1. Obtener el ID del cliente seleccionado
  const clienteId = document.getElementById("cliente").value;

  // 2. Leer todos los campos del formulario de nuevo envío
  const tituloEnvio = document.getElementById("titutoContacto").value;
  const nombreContacto = document.getElementById("nombreNuevoContacto").value;
  const compañia = document.getElementById("compañiaNuevoContacto").value;
  const telefonoContacto = document.getElementById(
    "telefonoNuevoContacto"
  ).value;
  const correoContacto = document.getElementById("correoNuevoContacto").value;
  const linea1Contacto = document.getElementById(
    "direccion1NuevoContacto"
  ).value;
  const linea2Contacto = document.getElementById(
    "direccion2NuevoContacto"
  ).value;
  const codigoContacto = document.getElementById("codigoNuevoContacto").value;
  const estadoContacto = document.getElementById("estadoNuevoContacto").value;
  const municipioContacto = document.getElementById(
    "municipioNuevoContacto"
  ).value;

  // 3. Validar que no falte ninguno de los campos requeridos
  if (
    !nombreContacto ||
    !tituloEnvio ||
    !compañia ||
    !correoContacto ||
    !telefonoContacto ||
    !linea1Contacto ||
    !linea2Contacto ||
    !codigoContacto ||
    !estadoContacto ||
    !municipioContacto
  ) {
    Swal.fire({
      icon: "warning",
      title: "Aviso",
      text: "Faltan datos.",
    });
    return; // Abortamos si falta algún campo
  }

  // 4. Enviar los datos al servidor vía AJAX
  $.ajax({
    url: "../Servidor/PHP/clientes.php", // Punto final en PHP que procesará el guardado
    method: "POST",
    data: {
      numFuncion: "6", // Identificador de la función en el servidor
      clienteId: clienteId, // ID del cliente al que pertenece esta dirección
      tituloEnvio: tituloEnvio, // Título o alias de la dirección
      nombreContacto: nombreContacto,
      compañia: compañia,
      telefonoContacto: telefonoContacto,
      correoContacto: correoContacto,
      linea1Contacto: linea1Contacto,
      linea2Contacto: linea2Contacto,
      codigoContacto: codigoContacto,
      estadoContacto: estadoContacto,
      municipioContacto: municipioContacto,
    },
    dataType: "json",
    success: function (envios) {
      // Esta función se ejecuta si la petición AJAX devuelve HTTP 200
      if (envios.success) {
        Swal.fire({
          icon: "success",
          title: "Éxito",
          text: "Se guardaron los nuevos datos de envío.",
        }).then(() => {
          // Cuando cierren el cuadro de alerta, ocultamos el modal y recargamos listados
          $("#modalNuevoEnvio").modal("hide");
          mostrarMoldal(); // Suponemos que esta función recarga la lista de envíos
        });
      } else {
        // Si success = false, mostramos advertencia con el mensaje del servidor
        Swal.fire({
          icon: "warning",
          title: "Aviso",
          text: envios.message || "No se pudieron guardar los datos de envío.",
        });
      }
    },
    error: function () {
      // Si la petición AJAX falla (500, 404, etc.)
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Error al guardar los datos de envío.",
      });
    },
  });
}
function cerrarModalEnvio() {
  //limpiarFormulario();
  $("#modalEnvio").modal("hide"); // Cierra el modal usando Bootstrap
  // Restaurar el aria-hidden al cerrar el modal
  $("#modalEnvio").attr("aria-hidden", "true");
  // Eliminar el atributo inert del fondo al cerrar
  $(".modal-backdrop").removeAttr("inert");
}
function cerrarModalNuevoEnvio() {
  //limpiarFormularioNuevo();
  $("#modalNuevoEnvio").modal("hide"); // Cierra el modal usando Bootstrap
  // Restaurar el aria-hidden al cerrar el modal
  $("#modalNuevoEnvio").attr("aria-hidden", "true");
  // Eliminar el atributo inert del fondo al cerrar
  $(".modal-backdrop").removeAttr("inert");
}
function mostrarMoldal() {
  //limpiarFormulario();
  let estadoSelect = document.getElementById("estadoContacto").value;
  let tipoOperacion = document.getElementById("tipoOperacion").value;
  if (tipoOperacion === "alta") {
    if (estadoSelect === "Selecciona un estado") {
      obtenerEstados();
    }
    obtenerDatosEnvio();
  }
  $("#modalEnvio").modal("show");
}
function limpiarFormulario() {
  //Limpia los campos (los deja vacios)
  $("#idDatos").val("");
  $("#folioDatos").val("");
  $("#nombreContacto").val("");
  $("#compañiaContacto").val("");
  $("#telefonoContacto").val("");
  $("#correoContacto").val("");
  $("#direccion1Contacto").val("");
  $("#direccion2Contacto").val("");
  $("#codigoContacto").val(""); // Si es un select, también se debe resetear
  $("#estadoContacto").val("");
  $("#municipioContacto").val("");
}
function limpiarFormularioNuevo() {
  //Limpia los campos (los deja vacios)
  $("#titutoContacto").val("");
  $("#nombreNuevoContacto").val("");
  $("#compañiaNuevoContacto").val("");
  $("#telefonoNuevoContacto").val("");
  $("#correoNuevoContacto").val("");
  $("#direccion1NuevoContacto").val("");
  $("#direccion2NuevoContacto").val("");
  $("#codigoNuevoContacto").val(""); // Si es un select, también se debe resetear
  $("#estadoNuevoContacto").val("");
  $("#municipioNuevoContacto").val("");
}
function llenarDatosEnvio(idDocumento) {
  //alert(idDocumento);
  $.post(
    "../Servidor/PHP/clientes.php",
    {
      numFuncion: "7",
      idDocumento: idDocumento,
    },
    function (response) {
      if (response.success && response.data) {
        const data = response.data.fields;

        // Verifica la estructura de los datos en el console.log
        console.log(data); // Esto te mostrará el objeto completo
        $("#idDatos").val(idDocumento);
        $("#folioDatos").val(data.id.integerValue);
        $("#nombreContacto").val(data.nombreContacto.stringValue);
        $("#titutoDatos").val(data.tituloEnvio.stringValue);
        $("#compañiaContacto").val(data.compania.stringValue);
        $("#telefonoContacto").val(data.telefonoContacto.stringValue);
        $("#correoContacto").val(data.correoContacto.stringValue);
        $("#direccion1Contacto").val(data.linea1.stringValue);
        $("#direccion2Contacto").val(data.linea2.stringValue);
        $("#codigoContacto").val(data.codigoPostal.stringValue);
        $("#estadoContacto").val(data.estado.stringValue);
        const municipio = data.municipio.stringValue;
        const edo = document.getElementById("estadoContacto").value;
        obtenerMunicipios(edo, municipio);
      } else {
        console.warn(
          "Error:",
          response.message || "Error al Obtener los Datos."
        );
        alert(response.message || "Error al Obtener los Datos.");
      }
    },
    "json"
  ).fail(function (jqXHR, textStatus, errorThrown) {
    console.error("Error en la Petición:", textStatus, errorThrown);
  });
}
function obtenerTotales() {
  const partidasData = obtenerDatosPartidas(); // debe devolver un array de objetos
  const formularioData = obtenerDatosFormulario(); // idem

  const form = new FormData();
  form.append("numFuncion", "29");
  form.append("formulario", JSON.stringify(formularioData));
  form.append("partidas", JSON.stringify(partidasData));

  fetch("../Servidor/PHP/ventas.php", {
    method: "POST",
    body: form,
  })
    .then((res) => res.json())
    .then((data) => {
      if (!data.success) {
        return Swal.fire(
          "Error",
          data.message || "No se pudieron calcular totales",
          "error"
        );
      }
      const subtotalPedido =
        data.subtotal.toFixed(2) - data.descuento.toFixed(2);
      // ¡Ahora sí puedes leer data.subtotal, data.iva y data.importe!
      $("#subtotal").val(data.subtotal.toFixed(2));
      $("#descuento").val(data.descuento.toFixed(2));
      $("#subtotalPedido").val(subtotalPedido.toFixed(2));
      $("#iva").val(data.iva.toFixed(2));
      $("#importe").val(data.importe.toFixed(2));
    })
    .catch((err) => {
      console.error("Error al obtener los totales:", err);
      Swal.fire("Error", "No se pudo contactar al servidor.", "error");
    });
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
$(document).ready(function () {
  $("#guardarPedido").click(async function (event) {
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
});
$("#cerrarModalHeader").on("click", function () {
  //Funcion para cerrar el modal de datos de envio
  cerrarModalEnvio();
});
$("#cerrarModalHeaderNuevo").on("click", function () {
  //Funcion para cerrar el modal de nuevos datos de envio
  cerrarModalNuevoEnvio();
});
$("#cerrarModalFooter").on("click", function () {
  //Funcion para cerrar el modal de datos de envio
  cerrarModalEnvio();
});
$("#cerrarModalFooterNuevo").on("click", function () {
  //Funcion para cerrar el modal de nuevos datos de envio
  cerrarModalNuevoEnvio();
});
$("#AyudaCondicion").click(function () {
  //Boton de ayuda para el campo de condicion
  event.preventDefault();
  Swal.fire({
    title: "Ayuda",
    text: "Podrás capturar la condición bajo la cual se efectuará el pago del cliente (por ejemplo, Efectivo, Crédito, Cóbrese o Devuélvase - C.O.D., etc.). ",
    icon: "info",
    confirmButtonText: "Entendido",
  });
});

$("#AyudaDescuento").click(function () {
  //Boton de ayuda para el campo de descuento
  event.preventDefault();
  Swal.fire({
    title: "Ayuda",
    text: "Al momento de indicar la clave del cliente en el documento de venta, se muestra automáticamente el porcentaje de descuento asignado a dicho cliente desde el Módulo de clientes. ",
    icon: "info",
    confirmButtonText: "Entendido",
  });
});

$("#AyudaDescuentofin").click(function () {
  //Boton de ayuda para el campo de descuento financiero
  event.preventDefault();
  Swal.fire({
    title: "Ayuda",
    text: "Un descuento financiero, es aquel que se otorga en circunstancias particulares (por Ejemplo, por pronto pago o por adquirir grandes volúmenes de mercancía). ",
    icon: "info",
    confirmButtonText: "Entendido",
  });
});

$("#AyudaEnviarA").click(function () {
  //Boton de ayuda para el campo de Enviar a
  event.preventDefault();
  Swal.fire({
    title: "Ayuda",
    text: "Escribe quien enviaran ",
    icon: "info",
    confirmButtonText: "Entendido",
  });
});

$("#cancelarPedido").click(function () {
  //Boton para redigir a la seccion de pedidos
  window.location.href = "Ventas.php";
});
$("#datosEnvio").click(function () {
  //Funcion para mostrar el modal de los datos de envio
  mostrarMoldal();
});
$("#nuevosDatosEnvio").click(function () {
  //!!!!!!!
  $("#modalEnvio").modal("hide");
  limpiarFormularioNuevo();
  obtenerEstadosNuevos();
  $("#modalNuevoEnvio").modal("show");
});
$("#verTotales").click(function () {
  obtenerTotales();
  $("#modalTotales").modal("show");
});
$("#estadoNuevoContacto").on("change", function () {
  obtenerMunicipiosNuevos();
});
$("#estadoContacto").on("change", function () {
  obtenerMunicipiosPedido();
});
$("#guardarDatosEnvio").click(function () {
  guardarDatosEnvio();
});
$("#actualizarDatos").click(function () {
  actualizarDatos();
});
$("#selectDatosEnvio").on("change", function () {
  const datosSeleccionado = $(this).find(":selected");

  if (datosSeleccionado.val()) {
    const idDocumento = datosSeleccionado.data("id");
    const id = datosSeleccionado.val();
    const titulo = datosSeleccionado.data("titulo");
    const claveCliente = datosSeleccionado.val();
    llenarDatosEnvio(idDocumento);
  } else {
    Swal.fire({
      icon: "error",
      title: "Error",
      text: "Error al Cargar los Datos de Envio.",
    });
  }
});
$("#cliente").on("keydown", function (e) {
  const clienteSeleccionado =
    sessionStorage.getItem("clienteSeleccionado") === "true";
  if (e.key === "Tab" && !clienteSeleccionado) {
    Swal.fire({
      icon: "warning",
      title: "Aviso",
      text: "No se Puede Avanzar sin Tener un Cliente.",
    });
    return;
    e.preventDefault();
  }
});
