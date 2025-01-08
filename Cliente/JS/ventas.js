const noEmpresa = sessionStorage.getItem('noEmpresaSeleccionada');

function agregarEventosBotones() {
    // Seleccionar todos los botones con la clase btnVisualizarCliente
    const botones = document.querySelectorAll('.btnVisualizarCliente');

    // Asignar un evento de clic a cada botón
    botones.forEach(boton => {
        boton.addEventListener('click', function () {
            const clienteId = this.dataset.id; // Obtener el ID del cliente
            console.log('Cliente seleccionado:', clienteId);

            // Aquí puedes agregar la lógica para visualizar al cliente
            alert(`Visualizar cliente con ID: ${clienteId}`);
        });
    });
}


$.post('../Servidor/PHP/ventas.php', { numFuncion: '1',noEmpresa: noEmpresa }, function (response) {
    try {
        // Verifica si response es una cadena (string) que necesita ser parseada
        if (typeof response === 'string') {
            response = JSON.parse(response);
        }
        // Verifica si response es un objeto antes de intentar procesarlo
        if (typeof response === 'object' && response !== null) {
            if (response.success && response.data) {
                const pedidos = response.data;
                const pedidosTable = document.getElementById('datosPedidos');
                pedidosTable.innerHTML = ''; // Limpiar la tabla antes de agregar nuevos datos
                pedidos.forEach(pedido => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${pedido.Tipo || 'Sin tipo'}</td>
                        <td>${pedido.Clave || 'Sin nombre'}</td>
                        <td>${pedido.Cliente || 'Sin cliente'}</td>
                        <td>${pedido.Nombre || 'Sin nombre'}</td>
                        <td>${pedido.Estatus || '0'}</td>
                        <td>${pedido.SuPedido || 'Sin pedido'}</td>
                        <td>${pedido.FechaElaboracion?.date || 'Sin fecha'}</td> <!-- Maneja el objeto anidado -->
                        <td>${pedido.Subtotal || 'Sin subtotal'}</td>
                        <td>${pedido.TotalComisiones || 'Sin comisiones'}</td>
                        <td>${pedido.NumeroAlmacen || 'Sin almacén'}</td>
                        <td>${pedido.FormaEnvio || 'Sin forma de envío'}</td>
                        <td>${pedido.ImporteTotal || 'Sin importe'}</td>
                        <td>${pedido.NombreVendedor || 'Sin vendedor'}</td>
                        <td>
                            <button class="btnVisualizarCliente" name="btnVisualizarCliente" data-id="${pedido.Clave}">Visualizar</button>
                        </td>
                    `;
                    pedidosTable.appendChild(row);
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