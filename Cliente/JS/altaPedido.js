let clienteSeleccionado = false;
let clienteId = null; // Variable para almacenar el ID del cliente

// Función para manejar la selección de cliente
function showCustomerSuggestions() {
    const clienteInput = document.getElementById("cliente"); // Obtienes el elemento de input
    const clienteInputValue = clienteInput.value; // Obtienes el valor del campo de texto
    const sugerencias = document.getElementById("clientesSugeridos");
    console.log("Cliente: " + clienteInputValue);

    // Aquí ya no necesitas hacer ninguna búsqueda ni consulta, ya que tienes el cliente seleccionado
    // Usas directamente el cliente que escribiste en el input
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
        const productoInput = fila.querySelector("td input[type='text']"); // Campo de producto
        const cantidadInput = fila.querySelector("td input[type='number']"); // Campo de cantidad

        if (productoInput.value.trim() === "" || cantidadInput.value <= 0) {
            alert("Debe seleccionar un producto y asegurar que la cantidad sea mayor a 0.");
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
        <td><input type="number" class="blanco1" value="0" /></td>
        <td><input type="number" class="blanco2" value="0" /></td>
        <td><input type="number" class="iva" value="0" /></td>
        <td><input type="number" class="comision" value="0" /></td>
        <td><input type="number" class="precioUnidad" value="0" /></td>
        <td><input type="number" class="subtotalPartida" value="0" /></td>
    `;

    tablaProductos.appendChild(nuevaFila);
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

/*function mostrarListaProductos(productos, input) {
    const tablaProductos = document.querySelector("#tablalistaProductos tbody");
    tablaProductos.innerHTML = ""; // Limpiar la tabla antes de agregar nuevos productos

    productos.forEach(function (producto) {
        const listItem = document.createElement("li");
        listItem.textContent = `${producto.DESCR}`;
        listItem.onclick = async function () {
            input.value = producto.DESCR; // Asignar el nombre del producto al campo de entrada
            // Asignar el valor de la unidad al campo de Unidad correspondiente en la tabla
            const filaTabla = input.closest("tr"); // Encuentra la fila de la tabla
            const campoUnidad = filaTabla.querySelector(".unidad"); // Busca el campo con la clase 'unidad'
            if (campoUnidad) {
                campoUnidad.value = producto.UNI_MED; // Asigna el valor de la unidad
            }

            // Cerrar el modal
            document.getElementById("modalProductos").style.display = "none";

            // Desbloquear el campo de cantidad de la fila correspondiente
            const campoCantidad = filaTabla.querySelector("input.cantidad"); // Encuentra el campo de cantidad
            if (campoCantidad) {
                campoCantidad.readOnly = false; // Desbloquea el campo de cantidad
                campoCantidad.value = 0; // Opcional: asignar un valor inicial
            }

            
            
        };

        // Agregar la fila a la tabla
        tablaProductos.appendChild(fila);
    });
}*/
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
        } else {
            console.error("No se encontró el campo 'precioUnidad' en la fila.");
            console.log(fila.outerHTML);
        }
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

/*function mostrarListaProductos(productos, input) {
    const tablaProductos = document.querySelector("#tablalistaProductos tbody");
    tablaProductos.innerHTML = ""; // Limpiar la tabla antes de agregar nuevos productos

    productos.forEach(function (producto) {
        // Crear una nueva fila para cada producto
        const fila = document.createElement("tr");

        // Crear y agregar celdas para cada columna
        const celdaClave = document.createElement("td");
        celdaClave.textContent = producto.CVE_ART;

        const celdaDescripcion = document.createElement("td");
        celdaDescripcion.textContent = producto.DESCR;

        const celdaExist = document.createElement("td");
        celdaExists.textContent = producto.EXIST;

        // Agregar celdas a la fila
        fila.appendChild(celdaClave);
        fila.appendChild(celdaDescripcion);
        fila.appendChild(celdaExist);

        // Agregar evento de selección de producto
        fila.onclick = function () {
            input.value = producto.DESCR; // Asignar descripción al campo de entrada

            // Asignar el valor de la unidad al campo de Unidad correspondiente en la tabla
            const filaTabla = input.closest("tr"); // Encuentra la fila de la tabla
            const campoUnidad = filaTabla.querySelector(".unidad"); // Busca el campo con la clase 'unidad'
            if (campoUnidad) {
                campoUnidad.value = producto.UNI_MED; // Asigna el valor de la unidad
            }

            // Cerrar el modal
            document.getElementById("modalProductos").style.display = "none";

            // Desbloquear el campo de cantidad de la fila correspondiente
            const campoCantidad = filaTabla.querySelector("input.cantidad"); // Encuentra el campo de cantidad
            if (campoCantidad) {
                campoCantidad.readOnly = false; // Desbloquea el campo de cantidad
                campoCantidad.value = 0; // Opcional: asignar un valor inicial
            }
        };

        // Agregar la fila a la tabla
        tablaProductos.appendChild(fila);
    });
}*/

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
                input.value = producto.DESCR;

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

// Cierra el modal usando la API de Bootstrap
function cerrarModal() {
    const modal = bootstrap.Modal.getInstance(document.getElementById("modalProductos"));
    if (modal) {
        modal.hide();
    }
}

// Agrega la fila de partidas al hacer clic en la sección de partidas o tabulando hacia ella
document.getElementById("clientesSugeridos").addEventListener("click", showCustomerSuggestions);
// Añadir el evento a la tabla de partidas para agregar una fila cuando el usuario haga clic o tabule hacia ella
// Agrega la fila de partidas al hacer clic o al tabular hacia la zona
// Agrega la fila de partidas al hacer clic o al tabular hacia la zona
document.getElementById("divProductos").addEventListener("click", function () {
    agregarFilaPartidas();
}, { once: true });
document.getElementById("tablaProductos").addEventListener("keydown", function (event) {
    if (event.key === "Tab") {  // Verifica si la tecla presionada es el tabulador
        agregarFilaPartidas();
    }
});

$(document).ready(function () {
    $('#guardarPedido').click(function () {
        validateFormulario();
    });
});
