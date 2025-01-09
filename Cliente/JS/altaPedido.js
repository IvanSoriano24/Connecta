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

        // Verifica si el producto está seleccionado y si la cantidad es mayor a 0
        if (productoInput.value.trim() === "" || cantidadInput.value <= 0) {
            alert("Debe seleccionar un producto y asegurar que la cantidad sea mayor a 0.");
            return;
        }
    }
    const nuevaFila = document.createElement("tr");
    nuevaFila.innerHTML = `
        <td><input type="number" class="cantidad" value="0" readonly /></td>
        <td><input type="text" class="producto" placeholder="Seleccionar producto..." onfocus="mostrarProductos(this)" /></td>
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

function mostrarProductos(input) {
    const modal = document.getElementById("modalProductos");
    modal.style.display = "block";

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

function mostrarListaProductos(productos, input) {
    const listaProductos = document.getElementById("listaProductos");
    listaProductos.innerHTML = ""; // Limpiar la lista antes de agregar nuevos productos

    productos.forEach(function (producto) {
        const listItem = document.createElement("li");
        listItem.textContent = `${producto.DESCR}`;
        listItem.onclick = function () {
            input.value = producto.DESCR; // Asignar el nombre del producto al campo de entrada

            // Asignar el valor de la unidad al campo de Unidad correspondiente en la tabla
            const fila = input.closest("tr"); // Encuentra la fila de la tabla
            const campoUnidad = fila.querySelector("td .unidad"); // Busca el campo con la clase 'unidad'
            if (campoUnidad) {
                campoUnidad.value = producto.UNI_MED; // Asigna el valor de la unidad
            }

            // Cerrar el modal
            document.getElementById("modalProductos").style.display = "none";

            // Desbloquear el campo de cantidad de la fila correspondiente
            const campoCantidad = fila.querySelector("td input[class='cantidad']"); // Encuentra el campo de cantidad
            if (campoCantidad) {
                campoCantidad.readOnly = false; // Desbloquea el campo de cantidad
                campoCantidad.value = 0; // Opcional: asignar un valor inicial si es necesario
            }
        };
        listaProductos.appendChild(listItem);
    });
}


// Cierra el modal
function cerrarModal() {
    const modal = document.getElementById("modalProductos");
    modal.style.display = "none";
}

// Agrega la fila de partidas al hacer clic en la sección de partidas o tabulando hacia ella
document.getElementById("clientesSugeridos").addEventListener("click", showCustomerSuggestions);
// Añadir el evento a la tabla de partidas para agregar una fila cuando el usuario haga clic o tabule hacia ella
// Agrega la fila de partidas al hacer clic o al tabular hacia la zona
// Agrega la fila de partidas al hacer clic o al tabular hacia la zona
document.getElementById("divProductos").addEventListener("click", function() {
    agregarFilaPartidas();
}, { once: true });
document.getElementById("tablaProductos").addEventListener("keydown", function(event) {
    if (event.key === "Tab") {  // Verifica si la tecla presionada es el tabulador
        agregarFilaPartidas();
    }
});
document.getElementById("cerrarModal").addEventListener("click", function() {
    cerrarModal();
});