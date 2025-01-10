let clienteSeleccionado = false;
let clienteId = null; // Variable para almacenar el ID del cliente

// Función para manejar la selección de cliente
function showCustomerSuggestions() {
    const clienteInput = document.getElementById("cliente"); // Obtienes el elemento de input
    const clienteInputValue = clienteInput.value; // Obtienes el valor del campo de texto
    const sugerencias = document.getElementById("clientesSugeridos");
    console.log("Cliente: " + clienteInputValue);
    const cliente = { id: 1, nombre: clienteInputValue }; // El cliente ya está en el input, solo lo tomas
    clienteId = cliente.id; // Asigna el ID del cliente
    clienteSeleccionado = true; // Marca que el cliente está seleccionado

    // Limpiar sugerencias para no mostrar nada después de seleccionar
    sugerencias.innerHTML = ""; // Limpiar las sugerencias
}

// Maneja la creación de la fila de partidas
function agregarFilaPartidas() {
    if (!clienteSeleccionado) {
        alert("Debe seleccionar un cliente primero.");
        return;
    }
    const tablaProductos = document.querySelector("#tablaProductos tbody");
    // Verificar si alguna fila tiene un producto y cantidad mayor a 0 antes de agregar una nueva
    const filas = tablaProductos.querySelectorAll("tr");
    for (let fila of filas) {
        const productoInput = fila.querySelector(".producto"); // Campo de producto
        const totalInput = fila.querySelector(".subtotalPartida");
        if (productoInput.value.trim() === "" || totalInput.value <= 0) {
            alert("Debe llenar los campos correspondientes");
            return;
        }
    }
    // Crear una nueva fila
    const nuevaFila = document.createElement("tr");
    nuevaFila.innerHTML = `
        <td><input type="number" class="cantidad" value="0" readonly /></td>
         <td>
            <div class="d-flex flex-column position-relative">
                <div class="d-flex align-items-center">
                     <input type="text" class="producto " placeholder="" 
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
        <td><input type="number" class="descuento1" value="0" /></td>
        <td><input type="number" class="descuento2" value="0" /></td>
        <td><input type="number" class="ieps" value="0" readonly /></td>
        <td><input type="number" class="iva" value="0" /></td>
        <td><input type="number" class="comision" value="0" /></td>
        <td><input type="number" class="precioUnidad" value="0" /></td>
        <td><input type="number" class="subtotalPartida" value="0" /></td>
    `;

    tablaProductos.appendChild(nuevaFila);
    const cantidadInput = nuevaFila.querySelector(".cantidad");
    cantidadInput.addEventListener("input", () => calcularSubtotal(nuevaFila));
}

// Ocultar sugerencias al hacer clic fuera del input
document.addEventListener("click", function (e) {
    const listas = document.querySelectorAll(".lista-sugerencias");
    listas.forEach(lista => {
        if (!lista.contains(e.target) && !lista.previousElementSibling.contains(e.target)) {
            lista.classList.add("d-none");
        }
    });
});

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
            url: '../Servidor/PHP/ventas.php',
            type: 'POST',
            data: { cveEsqImpu: cveEsqImpu, numFuncion: '7' },
            success: function(response) {
                console.log('Respuesta del servidor:', response); // Muestra la respuesta directamente
                try {
                    // Usa el objeto directamente
                    if (response.success) {
                        const { IMPUESTO1, IMPUESTO2, IMPUESTO4 } = response.impuestos;
                        console.log('Impuesto 1 (IEPS):', IMPUESTO1);
                        console.log('Impuesto 2 (IEPS):', IMPUESTO2);
                        console.log('Impuesto 4 (IVA):', IMPUESTO4);

                        resolve({ impuesto1: IMPUESTO1, impuesto2: IMPUESTO2, impuesto4: IMPUESTO4 });
                    } else {
                        console.error('Error del servidor:', response.message);
                        reject('Error del servidor: ' + response.message);
                    }
                } catch (error) {
                    console.error('Error al procesar la respuesta:', error);
                    reject('Error al procesar la respuesta: ' + error.message);
                }
            },
            error: function(xhr, status, error) {
                reject('Error en la solicitud AJAX: ' + error);
            }
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
            console.log(filaTabla.outerHTML);
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
        //const impuesto2Input = filaTabla.querySelector(".descuento2");
        const impuesto4Input = filaTabla.querySelector(".iva");

        // Verifica si los campos existen y asigna los valores de los impuestos
        if (impuesto1Input && impuesto4Input) {
            impuesto1Input.value = parseFloat(impuestos.impuesto1);
            impuesto4Input.value = parseFloat(impuestos.impuesto4);
            impuesto1Input.readOnly = true;
            impuesto4Input.readOnly = true;
        } else {
            console.error("No se encontraron uno o más campos 'descuento' en la fila.");
            console.log(filaTabla.outerHTML);
        }
        
        // Maneja los impuestos como sea necesario
    } catch (error) {
        console.error("Error al completar el precio del producto:", error);
    }
}


// Función para obtener el precio del producto
async function obtenerPrecioProducto(claveProducto, listaPrecioCliente) {
    try {
        const response = await $.get('../Servidor/PHP/ventas.php', {
            numFuncion: '6', // Cambia según la función que uses en tu PHP
            claveProducto: claveProducto,
            listaPrecioCliente: listaPrecioCliente
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

function validateFormulario() {
    const clienteInput = document.getElementById("cliente"); // Campo del cliente
    if (!clienteInput.value.trim()) {
        alert("Debe seleccionar un cliente.");
        return false;
    }
   if(validarPartidas()){
    guardarPerdido();
   }else{
    return false;
   }
}
function guardarPerdido(){
    alert("Se guarda");
}

// Boton para mostrar Productos
function mostrarProductos(input) {
    // Abre el modal de productos automáticamente
    const modalProductos = new bootstrap.Modal(document.getElementById('modalProductos'));
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
        const productosFiltrados = productos.filter(producto =>
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
                input.value = producto.CVE_ART;
                $('#CVE_ESQIMPU').val(producto.CVE_ESQIMPU);
                const filaTabla = input.closest("tr");
                const campoUnidad = filaTabla.querySelector(".unidad");
                if (campoUnidad) {
                    campoUnidad.value = producto.UNI_MED;
                }
                // Desbloquear el campo de cantidad
                const campoCantidad = filaTabla.querySelector("input.cantidad");
                if (campoCantidad) {
                    campoCantidad.readOnly = false;
                    campoCantidad.value = 0; // Valor inicial opcional
                }
                // Cerrar el modal
                cerrarModal();
                await completarPrecioProducto(producto.CVE_ART, filaTabla);
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
    const modal = bootstrap.Modal.getInstance(document.getElementById("modalProductos"));
    if (modal) {
        modal.hide();
    }
}

// Agrega la fila de partidas al hacer clic en la sección de partidas o tabulando hacia ella
document.getElementById("clientesSugeridos").addEventListener("click", showCustomerSuggestions);

document.getElementById("añadirPartida").addEventListener("click", function () {
    agregarFilaPartidas();
});
document.getElementById("tablaProductos").addEventListener("keydown", function (event) {
    if (event.key === "Tab") {  // Verifica si la tecla presionada es el tabulador
        agregarFilaPartidas();
    }
});

$(document).ready(function () {
    $('#guardarPedido').click(function () {
    });
});
