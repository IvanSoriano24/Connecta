function cargarComandas() {
    $.get('../Servidor/PHP/mensajes.php', { numFuncion: '1' }, function (response) {
        if (response.success) {
            const comandas = response.data;
            const tbody = $('#tablaComandas tbody');
            tbody.empty();

            comandas.forEach(comanda => {
                const row = `
                    <tr>
                        <td>${comanda.noPedido}</td>
                        <td>${comanda.nombreCliente}</td>
                        <td>${comanda.status}</td>
                        <td>${comanda.fecha}</td>
                        <td>${comanda.hora}</td>
                        <td>
                            <button class="btn btn-probarco" onclick="mostrarModal('${comanda.id}')" title="Ver Detalles">
                                <i class="bi bi-eye"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });
        } else {
            console.error('Error en la solicitud:', response.message);
        }
    }, 'json').fail(function (jqXHR, textStatus, errorThrown) {
        console.error('Error en la solicitud:', textStatus, errorThrown);
        console.log('Detalles:', jqXHR.responseText);
    });
}

function mostrarModal(comandaId) {
    $.get('../Servidor/PHP/mensajes.php', { numFuncion: '2', comandaId }, function (response) {
        if (response.success) {
            const comanda = response.data;

            // Cargar los datos en los inputs
            $('#detalleNoPedido').val(comanda.noPedido);
            $('#detalleNombreCliente').val(comanda.nombreCliente);
            $('#detalleStatus').val(comanda.status);
            $('#detalleFecha').val(comanda.fecha);
            $('#detalleHora').val(comanda.hora);

            // Cargar los productos en la lista
            const productosList = $('#detalleProductos');
            productosList.empty();
            comanda.productos.forEach(producto => {
                const item = `<li class="list-group-item">
                                <strong>${producto.clave}</strong> - ${producto.descripcion}
                                <span class="badge bg-primary float-end">Cantidad: ${producto.cantidad}</span>
                              </li>`;
                productosList.append(item);
            });

            // Mostrar el modal
            $('#modalDetalles').modal('show');
        } else {
            alert('Error al obtener los detalles del pedido.');
        }
    }, 'json');
}

// Manejar el botón de "Terminar"
$('#btnTerminar').click(function () {
    const comandaId = $('#detalleComanda').text();

    $.post('../Servidor/PHP/mensajes.php', { numFuncion: '3', comandaId }, function (response) {
        if (response.success) {
            alert('La comanda se ha marcado como TERMINADA.');
            $('#modalDetalles').modal('hide');
            cargarComandas(); // Recargar la tabla
        } else {
            alert('Error al marcar la comanda como TERMINADA.');
        }
    }, 'json');
});

$(document).ready(function () {
    cargarComandas();
});
