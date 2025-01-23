// Maneja la creación de la fila de partidas
function agregarFilaPartidas() {
  const clienteSeleccionado =
    sessionStorage.getItem("clienteSeleccionado") === "true";
  if (!clienteSeleccionado) {
    Swal.fire({
      title: "Error",
      text: `Debes seleccionar un Cliente primero`,
      icon: "warning",
      confirmButtonText: "Entendido",
    });
    return;
    Swal.fire({
      title: 'Error',
      text: `Debes seleccionar un Cliente primero`,
      icon: 'warning',
      confirmButtonText: 'Entendido'
    });
    return;
  }

  const tablaProductos = document.querySelector("#tablaProductos tbody");

  // Crear un identificador único para la partida
  const numPar = partidasData.length + 1;

  // Crear una nueva fila
  const nuevaFila = document.createElement("tr");
  nuevaFila.setAttribute("data-num-par", numPar); // Identificar la fila
  nuevaFila.innerHTML = `
  <td>
          <button type="button" class="btn btn-danger btn-sm eliminarPartida" onclick="eliminarPartidaFormulario(${numPar})">
              <i class="bx bx-trash"></i>
          </button>
      </td>    
      <td><input type="number" class="cantidad" value="0" readonly /></td>
      <td>
          <div class="d-flex flex-column position-relative">
              <div class="d-flex align-items-center">
                   <input type="text" class="producto" placeholder="" 
              oninput="mostrarSugerencias(this)" />
                  <button 
              type="button" 
              class="btn ms-2" 
              onclick="mostrarProductos(this.closest('tr').querySelector('.producto'))">
              <i class="bx bx-search"></i>
              </button>
              </div>
               <ul class="lista-sugerencias position-absolute bg-white list-unstyled border border-secondary mt-1 p-2 d-none"></ul>
          </div>
      </td>

      <td><input type="text" class="unidad" readonly /></td>
      <td><input type="number" class="descuento1" style="width: 70px;" value="0"  readonly /></td>
      <td><input type="number" class="descuento2" style="width: 70px;" value="0" readonly /></td>
      <td><input type="number" class="ieps" value="0" readonly /></td>
      <td><input type="number" class="iva" value="0" readonly /></td>
      <td><input type="number" class="comision" value="0" readonly /></td>
      <td><input type="number" class="precioUnidad" value="0" readonly /></td>
      <td><input type="number" class="subtotalPartida" value="0" readonly /></td>
      <td><input type="number" class="impuesto2" value="0" readonly hidden /></td>
      <td><input type="number" class="impuesto3" value="0" readonly hidden /></td>
  `;

  // Añadir evento al botón de eliminar
  const botonEliminar = nuevaFila.querySelector(".eliminarPartida");
  botonEliminar.addEventListener("click", () => {
    Swal.fire({
      title: '¿Estás seguro?',
      text: '¿Deseas eliminar esta partida?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        nuevaFila.remove(); // Eliminar la fila
        Swal.fire({
          title: 'Eliminada',
          text: 'La partida ha sido eliminada.',
          icon: 'success',
          confirmButtonText: 'Entendido'
        });
      }
    });
  });
  // Validar que la cantidad no sea negativa
  const cantidadInput = nuevaFila.querySelector(".cantidad");
  cantidadInput.addEventListener("input", () => {
    if (parseFloat(cantidadInput.value) < 0) {
      Swal.fire({
        title: "Error",
        text: "La cantidad no puede ser negativa.",
        icon: "error",
        confirmButtonText: "Entendido",
      });
      cantidadInput.value = 0; // Restablecer el valor a 0
    } else {
      calcularSubtotal(nuevaFila); // Recalcular subtotal si el valor es válido
    }
    if (parseFloat(cantidadInput.value) < 0) {
      Swal.fire({
        title: 'Error',
        text: 'La cantidad no puede ser negativa.',
        icon: 'error',
        confirmButtonText: 'Entendido'
      });
      cantidadInput.value = 0; // Restablecer el valor a 0
    } else {
      calcularSubtotal(nuevaFila); // Recalcular subtotal si el valor es válido
    }
  });

  tablaProductos.appendChild(nuevaFila);
}

// // Ocultar sugerencias al hacer clic fuera del input
// document.addEventListener("click", function (e) {
//     const listas = document.querySelectorAll(".lista-sugerencias");
//     listas.forEach(lista => {
//         if (!lista.contains(e.target) && !lista.previousElementSibling.contains(e.target)) {
//             lista.classList.add("d-none");
//         }
//     });
// });

function obtenerProductos(input) {
  const numFuncion = 5; // Número de función para identificar la acción (en este caso obtener productos)
  const xhr = new XMLHttpRequest();
  xhr.open("GET", "../Servidor/PHP/ventas.php?numFuncion=" + numFuncion, true);
  xhr.setRequestHeader("Content-Type", "application/json");

  xhr.onload = function () {
    if (xhr.status === 200) {
      const response = JSON.parse(xhr.responseText);
      if (response.success) {
        // Procesamos la respuesta de los productos
        mostrarListaProductos(response.productos, input);
      } else {
        alert("Error: " + response.message);
      }
    } else {
      alert("Hubo un problema con la consulta de productos.");
    }
  };

  xhr.onerror = function () {
    alert("Hubo un problema con la conexión.");
  };

  xhr.send();
}

async function obtenerImpuesto(cveEsqImpu) {
  return new Promise((resolve, reject) => {
    $.ajax({
      url: "../Servidor/PHP/ventas.php",
      type: "POST",
      data: { cveEsqImpu: cveEsqImpu, numFuncion: "7" },
      success: function (response) {
        try {
          // Usa el objeto directamente
          if (response.success) {
            const { IMPUESTO1, IMPUESTO2, IMPUESTO4 } = response.impuestos;
            resolve({
              impuesto1: IMPUESTO1,
              impuesto2: IMPUESTO2,
              impuesto3: IMPUESTO3,
              impuesto4: IMPUESTO4,
            });
          } else {
            console.error("Error del servidor:", response.message);
            reject("Error del servidor: " + response.message);
          }
        } catch (error) {
          console.error("Error al procesar la respuesta:", error);
          reject("Error al procesar la respuesta: " + error.message);
        }
      },
      error: function (xhr, status, error) {
        reject("Error en la solicitud AJAX: " + error);
      },
    });
  });
}

async function completarPrecioProducto(cveArt, filaTabla) {
  try {
    // Obtener la lista de precios correctamente
    const listaPrecioElement = document.querySelector(".listaPrecios");
    const cvePrecio = listaPrecioElement ? listaPrecioElement.value : "1";
    // Obtener el precio del producto
    const precio = await obtenerPrecioProducto(cveArt, cvePrecio);
    if (!precio) {
      alert("No se pudo obtener el precio del producto.");
      return;
    }
    // Seleccionar el input correspondiente dentro de la fila
    const precioInput = filaTabla.querySelector(".precioUnidad");
    if (precioInput) {
      precioInput.value = parseFloat(precio).toFixed(2); // Establecer el precio con 2 decimales
      precioInput.readOnly = true;
    } else {
      console.error("No se encontró el campo 'precioUnidad' en la fila.");
    }
    // Obtener y manejar impuestos
    var CVE_ESQIMPU = document.getElementById("CVE_ESQIMPU").value; // Asegurarse de usar el valor
    if (!CVE_ESQIMPU) {
      console.error("CVE_ESQIMPU no tiene un valor válido.");
      return;
    }
    const impuestos = await obtenerImpuesto(CVE_ESQIMPU);

    // Obtén los campos de la fila
    const impuesto1Input = filaTabla.querySelector(".ieps");
    const impuesto2Input = filaTabla.querySelector(".descuento2");
    const impuesto4Input = filaTabla.querySelector(".iva");
    const impuesto3Input = filaTabla.querySelector(".impuesto3");

    // Verifica si los campos existen y asigna los valores de los impuestos
    if (impuesto1Input && impuesto4Input) {
      impuesto1Input.value = parseFloat(impuestos.impuesto1);
      impuesto4Input.value = parseFloat(impuestos.impuesto4);
      impuesto3Input.value = parseFloat(impuestos.impuesto3);
      impuesto1Input.readOnly = true;
      impuesto4Input.readOnly = true;
    } else {
      console.error(
        "No se encontraron uno o más campos 'descuento' en la fila."
      );
    }

    // Maneja los impuestos como sea necesario
  } catch (error) {
    console.error("Error al completar el precio del producto:", error);
  }
}

// Función para obtener el precio del producto
async function obtenerPrecioProducto(claveProducto, listaPrecioCliente) {
  try {
    const response = await $.get("../Servidor/PHP/ventas.php", {
      numFuncion: "6", // Cambia según la función que uses en tu PHP
      claveProducto: claveProducto,
      listaPrecioCliente: listaPrecioCliente,
    });
    if (response.success) {
      return response.precio; // Retorna el precio
    } else {
      alert(response.message); // Muestra el mensaje de error
      return null;
    }
  } catch (error) {
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
      celdaExist.textContent = producto.EXIST;
      // const celdaLinea = document.createElement("td");
      // celdaLinea.textContent = producto.LIN_PROD;

      // const celdaDisponible = document.createElement("td");
      // celdaDisponible.textContent = producto.DISPONIBLE;

      // const celdaClaveAlterna = document.createElement("td");
      // celdaClaveAlterna.textContent = producto.CVE_ALT;
      fila.appendChild(celdaClave);
      fila.appendChild(celdaDescripcion);
      fila.appendChild(celdaExist);
      // fila.appendChild(celdaLinea);
      // fila.appendChild(celdaDisponible);
      // fila.appendChild(celdaClaveAlterna);
      fila.onclick = async function () {
        if (producto.EXIST > 0) {
          input.value = producto.CVE_ART;
          $('#CVE_ESQIMPU').val(producto.CVE_ESQIMPU);


          const filaTabla = input.closest("tr");
          const campoUnidad = filaTabla.querySelector(".unidad");
          if (campoUnidad) {
            campoUnidad.value = producto.UNI_MED;
          }
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
            title: 'Error',
            text: `El producto "${producto.CVE_ART}" no tiene existencias disponibles.`,
            icon: 'warning',
            confirmButtonText: 'Entendido'
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
  const cantidadInput = fila.querySelector(".cantidad");
  const precioInput = fila.querySelector(".precioUnidad");
  const subtotalInput = fila.querySelector(".subtotalPartida");

  const cantidad = parseFloat(cantidadInput.value) || 0; // Manejar valores no numéricos
  const precio = parseFloat(precioInput.value) || 0;

  const subtotal = cantidad * precio;
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
function guardarPedido(id) {
  try {
    // Obtener la información del formulario y las partidas
    const formularioData = obtenerDatosFormulario();
    console.log("Datos del formulario obtenidos:", formularioData);

    const partidasData = obtenerDatosPartidas();
    console.log("Datos de partidas obtenidos:", partidasData);

    // Determinar si es alta o edición
    formularioData.tipoOperacion = id === 0 ? "alta" : "editar";
    console.log("Datos preparados para enviar:", formularioData, partidasData);

    // Enviar los datos al backend
    enviarDatosBackend(formularioData, partidasData);
  } catch (error) {
    console.error("Error en guardarPedido:", error);
  }
}
function validarFormulario() {
  // Validar los campos obligatorios
  const cliente = document.getElementById("cliente").value.trim();
  const nombre = document.getElementById("nombre").value.trim();
  const rfc = document.getElementById("rfc").value.trim();
  const codigoPostal = document.getElementById("codigoPostal").value.trim();
  const calle = document.getElementById("calle").value.trim();
  const colonia = document.getElementById("colonia").value.trim();

  // Validación de los campos obligatorios
  if (!cliente || !nombre || !rfc || !codigoPostal || !calle || !colonia) {
    return false; // Si algún campo obligatorio está vacío, el formulario no es válido
  }

  return true; // Todos los campos obligatorios están completos
}
function validarPartidas() {
  // Aquí validas las partidas
  // Asegúrate de que cada fila en la tabla de productos esté completa y válida
  let valido = true;
  const filas = document.querySelectorAll("#tablaProductos tbody tr");
  filas.forEach((fila) => {
    const cantidad = fila.querySelector(".cantidad").value.trim();
    const producto = fila.querySelector(".producto").value.trim();
    const unidad = fila.querySelector(".unidad").value.trim();

    if (!cantidad || !producto || !unidad) {
      valido = false; // Si algún campo de la partida está vacío, no es válida
    }
  });
  return valido;
}
function obtenerDatosFormulario() {
  const now = new Date(); // Obtiene la fecha y hora actual
  const diaAlta = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')} ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')}`;

  const formularioData = {
    claveVendedor: document.getElementById("vendedor").value,
    factura: document.getElementById("factura").value,
    numero: document.getElementById("numero").value,
    diaAlta: diaAlta, // Fecha y hora
    cliente: document.getElementById("cliente").value,
    rfc: document.getElementById("rfc").value,
    nombre: document.getElementById("nombre").value,
    calle: document.getElementById("calle").value,
    numE: document.getElementById("numE").value,
    numI: document.getElementById("numI").value,
    descuento: document.getElementById("descuento").value,
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
    destinatario: document.getElementById("destinatario").value,
  };
  return formularioData;
}
function obtenerDatosPartidas() {
  // Aquí obtienes las partidas de la tabla
  const partidasData = [];
  const filas = document.querySelectorAll("#tablaProductos tbody tr");
  filas.forEach((fila) => {
    const partida = {
      cantidad: fila.querySelector(".cantidad").value,
      producto: fila.querySelector(".producto").value,
      unidad: fila.querySelector(".unidad").value,
      descuento1: fila.querySelector(".descuento1").value,
      descuento2: fila.querySelector(".descuento2").value,
      ieps: fila.querySelector(".ieps").value,
      impuesto2: fila.querySelector(".impuesto2").value,
      isr: fila.querySelector(".impuesto3").value,
      iva: fila.querySelector(".iva").value,
      comision: fila.querySelector(".comision").value,
      precioUnitario: fila.querySelector(".precioUnidad").value,
      subtotal: fila.querySelector(".subtotalPartida").value,
    };
    partidasData.push(partida);
  });
  return partidasData;
}
function enviarDatosBackend(formularioData, partidasData) {
  // Aquí se prepara un objeto FormData para enviar los datos como si fueran un formulario
  const formData = new FormData();
  // Agregamos los datos necesarios al FormData
  formData.append("numFuncion", "8");
  formData.append("formulario", JSON.stringify(formularioData));
  formData.append("partidas", JSON.stringify(partidasData));

  // Enviamos la solicitud con fetch
  fetch("../Servidor/PHP/ventas.php", {
    method: "POST",
    body: formData, // Pasamos el FormData directamente
  })
    .then((response) => response.json()) // Asumimos que el servidor responde con JSON
    .then((data) => {
      if (data.success) {
        if (data.validacion && data.validacion.existe) {
          let mensaje = "Hay existencias suficientes.\n\n";
          data.validacion.productos.forEach((producto) => {
            mensaje += `Producto: ${producto.codigo}, Existencias: ${producto.existencias}\n`;
          });
        }
        Swal.fire({
          title: "¡Pedido guardado exitosamente!",
          text: "El pedido se procesó correctamente.",
          icon: "success",
          confirmButtonText: "Aceptar",
        }).then(() => {
          // Redirigir al usuario o realizar otra acción
          //window.location.href = "Ventas.php";
        });
      } else if (data.exist) {
        console.error("Error en la respuesta:", data);
        Swal.fire({
          title: "Error al guardar el pedido",
          html: `
                <p>${
                  data.message ||
                  "No hay suficientes existencias para algunos productos."
                }</p>
          title: 'Error al guardar el pedido',
          html: 
                <p>${data.message || 'No hay suficientes existencias para algunos productos.'}</p>
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
                            <strong>Apartados:</strong> ${
                              producto.apartados || 0
                            }, 
                            <strong>Disponibles:</strong> ${
                              producto.disponible || 0
                            }
                        </li>
                    `
                      )
                      .join("")}
                </ul>
            `,
          icon: "error",
          confirmButtonText: "Aceptar",
          icon: 'error',
          confirmButtonText: 'Aceptar'
        });
      }
      if (data.credit) {
        console.error("Error en la respuesta:", data);
        Swal.fire({
          title: "Error al guardar el pedido",
          html: `
                        <p>${data.message || "Ocurrió un error inesperado."}</p>
                        <p><strong>Saldo actual:</strong> ${data.saldoActual?.toFixed(2) || "N/A"
            }</p>
                        <p><strong>Límite de crédito:</strong> ${data.limiteCredito?.toFixed(2) || "N/A"
            }</p>
                    `,
          icon: "error",
          confirmButtonText: "Aceptar",
        });
      } else{
          console.error("Error en la respuesta:", data);
          Swal.fire({
            title: "Error al guardar el pedido",
            html: `
                          <p>${data.message || "Ocurrió un error inesperado."}</p>
                          `,
            icon: "error",
            confirmButtonText: "Aceptar",
          });
      }
    })
    .catch((error) => {
      console.error("Error al enviar los datos:", error);
      alert("Ocurrió un error al enviar los datos." + error);
    });
}
function editarPedido(pedidoID) {
  // Datos necesarios para la edición
  const datos = {
    numFuncion: "11", // Ejemplo: Caso 4 para editar pedido
    pedidoID: pedidoID,
    cliente: $("#cliente").val(),
    // Otros datos del formulario
  };

  $.post("../Servidor/PHP/ventas.php", datos, function (response) {
    try {
      const data = JSON.parse(response);
      if (data.success) {
        Swal.fire({
          title: "Editado",
          text: "El pedido ha sido actualizado correctamente",
          icon: "success",
          confirmButtonText: "Entendido",
        }).then(() => {
          window.location.reload(); // Recargar la página para ver los cambios
        });
      } else {
        Swal.fire({
          title: "Error",
          text: data.message || "No se pudo actualizar el pedido",
          icon: "error",
          confirmButtonText: "Entendido",
        });
      }
    } catch (error) {
      console.error("Error al procesar la respuesta:", error);
    }
  }).fail(function (xhr, status, error) {
    console.error("Error de AJAX:", error);
  });
}
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
// MODAL MOSTRAR CLIENTES

// Variables globales
let clienteSeleccionado = false;
let clienteId = null; // Variable para almacenar el ID del cliente
let clientesData = []; // Para almacenar los datos originales de los clientes

// Función para abrir el modal y cargar los clientes
function abrirModalClientes() {
  const modalElement = document.getElementById("modalClientes");
  const modal = new bootstrap.Modal(modalElement);
  const datosClientes = document.getElementById("datosClientes");

  // Solicitar datos al servidor
  $.post(
    "../Servidor/PHP/clientes.php",
    { numFuncion: "1" },
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

  modal.show();
}

// Función para renderizar los clientes en la tabla
function renderClientes(clientes) {
  const datosClientes = document.getElementById("datosClientes");
  datosClientes.innerHTML = "";
  clientes.forEach((cliente) => {
    const fila = document.createElement("tr");
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
  llenarDatosCliente(cliente);
  cerrarModalClientes(); // Cerrar el modal
}

function validarCreditoCliente(clienteId) {
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
        } else {
          Swal.fire({
            title: "Sin crédito",
            text: "El cliente no maneja crédito.",
            icon: "info",
          });
        }
      } else {
        Swal.fire({
          title: "Error",
          text: data.message || "No se pudo validar el cliente.",
          icon: "error",
        });
      }
    })
    .catch((error) => {
      console.error("Error al validar el cliente:", error);
      Swal.fire({
        title: "Error",
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
  clienteInput.value = `${cliente.CLAVE} - ${cliente.NOMBRE}`; // Mostrar cliente seleccionado
  clienteId = cliente.CLAVE; // Guardar el ID del cliente

  // Actualizar estado de cliente seleccionado
  sessionStorage.setItem("clienteSeleccionado", true);

  // Limpiar y ocultar sugerencias
  const sugerencias = document.getElementById("clientesSugeridos");
  sugerencias.innerHTML = ""; // Limpiar las sugerencias
  sugerencias.classList.add("d-none"); // Ocultar las sugerencias
  llenarDatosCliente(cliente);
}
function llenarDatosCliente(cliente) {
  $("#rfc").val(cliente.RFC || "");
  $("#nombre").val(cliente.NOMBRE || "");
  $("#calle").val(cliente.CALLE || "");
  $("#numE").val(cliente.NUMEXT || "");
  $("#numI").val(cliente.NUMINT || "");
  $("#colonia").val(cliente.COLONIA || "");
  $("#codigoPostal").val(cliente.CODIGO || "");
  $("#poblacion").val(cliente.LOCALIDAD || "");
  $("#pais").val(cliente.PAIS || "");
  $("#regimenFiscal").val(cliente.REGIMEN_FISCAL || "");
  $("#cliente").val(cliente.CLAVE || "");
  $("#listaPrecios").val(cliente.LISTA_PREC || "");

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

// Mostrar/ocultar el botón "x"
function toggleClearButton() {
  if (inputCliente.val().trim() !== "") {
    clearButton.show();
  } else {
    clearButton.hide();
  }
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

// // Agrega la fila de partidas al hacer clic en la sección de partidas o tabulando hacia ella
// document.getElementById("clientesSugeridos").addEventListener("click", showCustomerSuggestions);

document.getElementById("añadirPartida").addEventListener("click", function () {
  agregarFilaPartidas();
});
document
  .getElementById("tablaProductos")
  .addEventListener("keydown", function (event) {
    if (event.key === "Tab") {
      // Verifica si la tecla presionada es el tabulador
      agregarFilaPartidas();
    }
  });
document.getElementById("tablaProductos").addEventListener("keydown", function (event) {
  if (event.key === "Tab") {  // Verifica si la tecla presionada es el tabulador
    agregarFilaPartidas();
  }
});

$(document).ready(function () {
  $("#guardarPedido").click(async function (event) {
    event.preventDefault();

    // Obtener el ID actual del pedido desde el formulario
    let id = document.getElementById("numero").value;
    console.log("ID actual del pedido:", id);

    try {
      // Obtener el siguiente folio del backend
      const folio = await obtenerFolioSiguiente();
      console.log("Folio obtenido:", folio);

      if (folio == id) {
        // Es un nuevo pedido
        console.log("Guardando nuevo pedido...");
        id = 0; // Se establece 0 para identificar que es una alta
      } else {
        console.log("Editando pedido existente...");
        document.getElementById("numero").value = id
      }
      //document.getElementById("numero").value = id;
      console.log(document.getElementById("numero").value);
      guardarPedido(id); // Llamar a guardarPedido con el id
    } catch (error) {
      console.error("Error al obtener el folio:", error);
    }
  });
});

$("#AyudaCondicion").click(function () {
  event.preventDefault();
  Swal.fire({
    title: "Ayuda",
    text: "Condicion es:",
    icon: "info",
    confirmButtonText: "Entendido",
  });
});

$("#AyudaEnviarA").click(function () {
  event.preventDefault();
  Swal.fire({
    title: "Ayuda",
    text: "Escribe quien enviaran ",
    icon: "info",
    confirmButtonText: "Entendido",
  });
});

function validarCorreo() {
  alert("1");
  fetch("../Servidor/PHP/ventas.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(formularioData),
  })
    .then((response) => response.json())
    .then((data) => {
      alert(data.message);
    })
    .catch((error) => console.error("Error:", error));
  alert("3");
}
$("#btnValidarCorreo").click(function () {
  alert("Entra");
  validarCorreo();
});
