

document.addEventListener("DOMContentLoaded", () => {
    const tablaProductos = document.getElementById("tabla-productos").querySelector("tbody");
    const modalProductos = document.getElementById("modal-productos");
    const cerrarModal = document.getElementById("cerrar-modal");
    let clienteSeleccionado = false; // Variable para controlar si hay cliente seleccionado

    // Simulación de cliente seleccionado (reemplazar con lógica real)
    const seleccionarCliente = () => {
        clienteSeleccionado = true; // Marcar que hay cliente seleccionado
        console.log("Cliente seleccionado. Ahora puedes agregar partidas.");
    };

    // Evento para simular selección de cliente
    document.getElementById("cliente").addEventListener("change", seleccionarCliente);

    // Función para agregar una nueva fila dinámicamente
    const agregarFila = () => {
        const nuevaFila = document.createElement("tr");
        nuevaFila.innerHTML = `
            <td><input type="number" value="1" readonly></td>
            <td>
                <input type="text" class="campo-producto" placeholder="Seleccionar producto">
            </td>
            <td><input type="text" readonly></td>
            <td><input type="number" value="0" readonly></td>
            <td><input type="number" value="0" readonly></td>
            <td><input type="number" value="0" readonly></td>
            <td></td>
            <td></td>
            <td><input type="number" value="0" readonly></td>
            <td><input type="number" value="0" readonly></td>
            <td><input type="number" value="0" readonly></td>
            <td><input type="number" value="0" readonly></td>
        `;

        // Agregar evento al campo de producto
        const campoProducto = nuevaFila.querySelector(".campo-producto");
        campoProducto.addEventListener("focus", mostrarModal);
        tablaProductos.appendChild(nuevaFila);
    };

    // Función para mostrar el modal
    const mostrarModal = (event) => {
        modalProductos.style.display = "block";

        // Guardar el input donde se seleccionará el producto
        const inputProducto = event.target;

        // Llenar dinámicamente la lista de productos
        const listaProductos = document.getElementById("lista-productos");
        listaProductos.innerHTML = `
            <li data-producto="Producto 1" data-unidad="Unidad 1">Producto 1</li>
            <li data-producto="Producto 2" data-unidad="Unidad 2">Producto 2</li>
            <li data-producto="Producto 3" data-unidad="Unidad 3">Producto 3</li>
        `;

        // Agregar evento a los elementos de la lista
        listaProductos.querySelectorAll("li").forEach((item) => {
            item.addEventListener("click", () => {
                seleccionarProducto(inputProducto, item.dataset.producto, item.dataset.unidad);
            });
        });
    };

    // Función para ocultar el modal
    const ocultarModal = () => {
        modalProductos.style.display = "none";
    };

    // Función para seleccionar un producto
    const seleccionarProducto = (inputProducto, producto, unidad) => {
        // Asignar el producto al input correspondiente
        inputProducto.value = producto;

        // Buscar la fila correspondiente y asignar la unidad
        const fila = inputProducto.closest("tr");
        const inputUnidad = fila.querySelector("td:nth-child(3) input");
        inputUnidad.value = unidad;

        // Cerrar el modal
        ocultarModal();

        // Si estamos en la última fila, agregar una nueva fila
        const ultimaFila = tablaProductos.lastElementChild;
        if (fila === ultimaFila) {
            agregarFila();
        }
    };

    // Agregar evento para cerrar el modal
    cerrarModal.addEventListener("click", ocultarModal);

    // Detectar clic o tab en la sección de partidas
    const areaPartidas = document.getElementById("tabla-productos");
    areaPartidas.addEventListener("click", () => {
        if (!clienteSeleccionado) {
            alert("Primero selecciona un cliente antes de agregar partidas.");
            return;
        }

        // Si no hay filas en la tabla, agregar la primera fila
        if (tablaProductos.children.length === 0) {
            agregarFila();
        }
    });

    areaPartidas.addEventListener("focusin", () => {
        if (!clienteSeleccionado) {
            alert("Primero selecciona un cliente antes de agregar partidas.");
            return;
        }

        // Si no hay filas en la tabla, agregar la primera fila
        if (tablaProductos.children.length === 0) {
            agregarFila();
        }
    });
});