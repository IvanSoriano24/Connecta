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

// Llamada AJAX para obtener las partidas del cliente seleccionado
function obtenerPartidas(clienteId) {
    // Simula la llamada AJAX para obtener las partidas del cliente
    // Aquí deberías reemplazarlo con tu propia lógica para hacer la consulta a tu servidor
    const partidas = [
        { cantidad: 2, producto: "Producto A", unidad: "kg", desc1: "10%", desc2: "5%", ieps: "0.5", iva: "16%", comision: "5%", precioUnitario: "100", subtotal: "200" },
        { cantidad: 1, producto: "Producto B", unidad: "pz", desc1: "15%", desc2: "10%", ieps: "1", iva: "16%", comision: "4%", precioUnitario: "150", subtotal: "150" }
    ];

    // Limpiar la tabla de partidas antes de agregar nuevas (esto ahora no se hace automáticamente)
     const tablaProductos = document.querySelector("#tablaProductos tbody");
    tablaProductos.innerHTML = ""; // Limpiar tabla antes de agregar nuevas filas

    // No agregar las partidas automáticamente, solo cuando se hace clic o tabula hacia la tabla
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
        <td><input type="number" value="1" readonly /></td>
        <td><input type="text" placeholder="Seleccionar producto..." onfocus="mostrarProductos(this)" /></td>
        <td><input type="text" readonly /></td>
        <td><input type="number" value="0" /></td>
        <td><input type="number" value="0" /></td>
        <td><input type="number" value="0" readonly /></td>
        <td><input type="number" value="0" /></td>
        <td><input type="number" value="0" /></td>
        <td><input type="number" value="0" /></td>
        <td><input type="number" value="0" /></td>
        <td><input type="number" value="0" /></td>
        <td><input type="number" value="0" /></td>
    `;

    tablaProductos.appendChild(nuevaFila);
}

// Muestra la lista de productos cuando el campo de producto está enfocado
function mostrarProductos(input) {
    const modal = document.getElementById("modalProductos");
    modal.style.display = "block";

    // Simula productos disponibles
    const productos = ["Producto A", "Producto B", "Producto C"];
    const listaProductos = document.getElementById("listaProductos");

    listaProductos.innerHTML = "";
    productos.forEach((producto) => {
        const listItem = document.createElement("li");
        listItem.textContent = producto;
        listItem.onclick = () => {
            input.value = producto;
            modal.style.display = "none";

            // Desbloquear el campo de cantidad de la fila correspondiente
            const fila = input.closest("tr"); // Encuentra la fila de la tabla
            const campoCantidad = fila.querySelector("td input[type='number']"); // Encuentra el campo de cantidad
            if (campoCantidad) {
                campoCantidad.readOnly = false; // Desbloquea el campo de cantidad
                campoCantidad.value = 1; // Opcional: asignar un valor inicial si es necesario
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
//document.getElementById("tablaProductos").addEventListener("click", agregarFilaPartidas, true);
// Agrega la fila de partidas al hacer clic o al tabular hacia la zona
document.getElementById("tablaProductos").addEventListener("click", function() {
    agregarFilaPartidas();
}, { once: true });

document.getElementById("tablaProductos").addEventListener("keydown", function(event) {
    if (event.key === "Tab") {  // Verifica si la tecla presionada es el tabulador
        agregarFilaPartidas();
    }
});


