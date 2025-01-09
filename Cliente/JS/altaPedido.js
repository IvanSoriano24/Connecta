let clienteSeleccionado = false;

        // Función para manejar la selección de cliente
        function showCustomerSuggestions() {
            const clienteInput = document.getElementById("cliente");
            const sugerencias = document.getElementById("clientesSugeridos");

            // Simula sugerencias de clientes (puedes reemplazar con datos reales)
            const clientes = ["Cliente 1", "Cliente 2", "Cliente 3"];
            const searchText = clienteInput.value.toLowerCase();

            sugerencias.innerHTML = "";
            if (searchText.length > 0) {
                clientes.forEach((cliente) => {
                    if (cliente.toLowerCase().includes(searchText)) {
                        const listItem = document.createElement("li");
                        listItem.textContent = cliente;
                        listItem.onclick = () => {
                            clienteInput.value = cliente;
                            sugerencias.innerHTML = "";
                            clienteSeleccionado = true; // Cliente seleccionado
                        };
                        sugerencias.appendChild(listItem);
                    }
                });
            }
        }

        // Maneja la creación de la fila de partidas
        function agregarFilaPartidas() {
            if (!clienteSeleccionado) {
                alert("Debe seleccionar un cliente primero.");
                return;
            }

            const tablaProductos = document.querySelector("#tablaProductos tbody");
            const nuevaFila = document.createElement("tr");

            nuevaFila.innerHTML = `
                <td><input type="number" value="1" readonly /></td>
                <td><input type="text" placeholder="Seleccionar producto..." onfocus="mostrarProductos(this)" /></td>
                <td><input type="text" readonly /></td>
                <td><input type="number" value="0" /></td>
                <td><input type="number" value="0" /></td>
                <td><input type="number" value="0" readonly /></td>
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
                };
                listaProductos.appendChild(listItem);
            });
        }

        // Cierra el modal
        function cerrarModal() {
            const modal = document.getElementById("modalProductos");
            modal.style.display = "none";
        }

        // Agrega la fila de partidas al hacer clic
        document.getElementById("productos").addEventListener("click", agregarFilaPartidas);