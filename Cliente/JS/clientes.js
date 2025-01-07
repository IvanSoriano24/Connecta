$.post('../Servidor/PHP/clientes.php', { numFuncion: '1' }, function (response) {
    try {
        // Verifica si response es una cadena (string) que necesita ser parseada
        if (typeof response === 'string') {
            response = JSON.parse(response);
        }
        // Verifica si response es un objeto antes de intentar procesarlo
        if (typeof response === 'object' && response !== null) {
            if (response.success && response.data) {
                const clientes = response.data;
                const clientesTable = document.getElementById('datosClientes');
                clientesTable.innerHTML = ''; // Limpiar la tabla antes de agregar nuevos datos
                clientes.forEach(cliente => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${cliente.CLAVE || 'Sin clave'}</td>
                        <td>${cliente.NOMBRE || 'Sin nombre'}</td>
                        <td>${cliente.CALLE || 'Sin calle'}</td>
                        <td>${cliente.TELEFONO || 'Sin teléfono'}</td>
                        <td>${cliente.SALDO || '0'}</td>
                        <td>${cliente.EstadoDatosTimbrado || 'Sin estado'}</td>
                        <td>${cliente.NOMBRECOMERCIAL || 'Sin nombre comercial'}</td>
                        <td>
                            <button class="btnVisualizarCliente" name="btnVisualizarCliente" data-id="${cliente.CLAVE}">Visualizar</button>
                        </td>
                    `;
                    clientesTable.appendChild(row);
                });
                agregarEventosBotones();
            } else {
                console.error('Error en la respuesta del servidor:', response);
            }
        } else {
            console.error('La respuesta no es un objeto válido:', response);
        }
    } catch (error) {
        console.error('Error al procesar la respuesta JSON:', error);
        console.error('Detalles de la respuesta:', response);  // Mostrar respuesta completa
    }
}, 'json').fail(function(jqXHR, textStatus, errorThrown) {
    console.error('Error en la solicitud:', textStatus, errorThrown);
    console.log('Detalles de la respuesta JSON:', jqXHR.responseText);
});

// Función para agregar eventos a los botones dinámicos
function agregarEventosBotones() {
    // Asumimos que cada botón "Visualizar" tiene una clase llamada "btnVisualizarCliente"
    const botonesVisualizar = document.querySelectorAll('.btnVisualizarCliente');
    
    botonesVisualizar.forEach(boton => {
        boton.addEventListener('click', function () {
            const clienteId = this.getAttribute('data-id');
            // Aquí puedes manejar la visualización del cliente, por ejemplo, abriendo una ventana modal
            console.log(`Visualizando cliente con ID: ${clienteId}`);
        });
    });
}
