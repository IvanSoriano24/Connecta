const noEmpresa = sessionStorage.getItem('noEmpresaSeleccionada');

function agregarEventosBotones() {
    // Seleccionar todos los botones con la clase btnVisualizarCliente
    const botones = document.querySelectorAll('.btnVisualizarPedido');

    // Asignar un evento de clic a cada botón
    botones.forEach(boton => {
        boton.addEventListener('click', function () {
            const pedidoID = this.dataset.id; // Obtener el ID del cliente
            window.location('altaPedido.php');
        });
    });
}

function cargarPedidos(filtroFecha) {
    $.post('../Servidor/PHP/ventas.php', {
        numFuncion: '1',
        noEmpresa: noEmpresa,
        filtroFecha: filtroFecha // Pasamos el valor del filtro de fecha al servidor
    }, function (response) {
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
                            <td>${pedido.FechaElaboracion?.date || 'Sin fecha'}</td> <!-- Maneja el objeto anidado -->
                            <td>${pedido.Subtotal || 'Sin subtotal'}</td>
                            <td>${pedido.TotalComisiones || 'Sin comisiones'}</td>
                            <td>${pedido.NumeroAlmacen || 'Sin almacén'}</td>
                            <td>${pedido.FormaEnvio || 'Sin forma de envío'}</td>
                            <td>${pedido.ImporteTotal || 'Sin importe'}</td>
                            <td>${pedido.NombreVendedor || 'Sin vendedor'}</td>
                            <td>
                                <button class="btnVisualizarPedido" name="btnVisualizarPedido" data-id="${pedido.Clave}" style="
                                display: inline-flex;
        align-items: center;
        padding: 0.5rem 1rem;
        font-size: 1rem;
        font-family: Lato;
        color: #fff;
        background-color: #007bff;
        border: none;
        border-radius: 0.25rem;
        cursor: pointer;
        transition: background-color 0.3s ease;
        ">
            <i class="fas fa-eye" style="margin-right: 0.5rem;"></i> Visualizar
    </button>  
                            </td>
                        `;
                        pedidosTable.appendChild(row);
                    });
                    agregarEventosBotones();
                } else {
                    console.error('Error en la respuesta del servidor:', response);
                    alert(response.message);
                }
            } else {
                console.error('La respuesta no es un objeto válido:', response);
            }
        } catch (error) {
            console.error('Error al procesar la respuesta JSON:', error);
            console.error('Detalles de la respuesta:', response);  // Mostrar respuesta completa
        }
    }, 'json').fail(function (jqXHR, textStatus, errorThrown) {
        console.error('Error en la solicitud:', textStatus, errorThrown);
        console.log('Detalles de la respuesta JSON:', jqXHR.responseText);
    });
}
function datosPedidos() {
    $.post('../Servidor/PHP/ventas.php', { numFuncion: '1', noEmpresa: noEmpresa, filtroFecha: 'Hoy' }, function (response) {
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
                        <td>${pedido.FechaElaboracion?.date || 'Sin fecha'}</td> <!-- Maneja el objeto anidado -->
                        <td>${pedido.Subtotal || 'Sin subtotal'}</td>
                        <td>${pedido.TotalComisiones || 'Sin comisiones'}</td>
                        <td>${pedido.NumeroAlmacen || 'Sin almacén'}</td>
                        <td>${pedido.FormaEnvio || 'Sin forma de envío'}</td>
                        <td>${pedido.ImporteTotal || 'Sin importe'}</td>
                        <td>${pedido.NombreVendedor || 'Sin vendedor'}</td>
                        <td>
                            <button class="btnVisualizarPedido" name="btnVisualizarPedido" data-id="${pedido.Clave}">Editar</button>
                        </td>
                    `;
                        pedidosTable.appendChild(row);
                    });
                    agregarEventosBotones();
                } else {
                    console.error('Error en la respuesta del servidor:', response);
                    alert(response.message);
                }
            } else {
                console.error('La respuesta no es un objeto válido:', response);
            }
        } catch (error) {
            console.error('Error al procesar la respuesta JSON:', error);
            console.error('Detalles de la respuesta:', response);  // Mostrar respuesta completa
        }
    }, 'json').fail(function (jqXHR, textStatus, errorThrown) {
        console.error('Error en la solicitud:', textStatus, errorThrown);
        console.log('Detalles de la respuesta JSON:', jqXHR.responseText);
    });
}

// Función para obtener el siguiente folio
function obtenerFolioSiguiente() {
    $.post('../Servidor/PHP/ventas.php', {
        numFuncion: '3',  // Llamamos al caso 3 para obtener el siguiente folio
    }, function (response) {
        // Parseamos la respuesta del servidor
        var data = JSON.parse(response);
        if (data.success) {
            // Si la respuesta fue exitosa, mostramos el siguiente folio
            console.log("El siguiente folio es: " + data.folioSiguiente);
            // Aquí puedes llenar un campo de tu formulario con el siguiente folio
            document.getElementById('numero').value = data.folioSiguiente;
        } else {
            // Si hubo un error, mostramos el mensaje de error
            console.log("Error: " + data.message);
        }
    }).fail(function (xhr, status, error) {
        // En caso de que ocurra un error en la solicitud AJAX
        console.log("Error de AJAX: " + error);
    });
}

$('#filtroFecha').change(function () {
    var filtroSeleccionado = $(this).val(); // Obtener el valor seleccionado del filtro
    cargarPedidos(filtroSeleccionado); // Llamar la función para cargar los pedidos con el filtro
});
$('#cancelarPedido').click(function() {
    window.location.href = "ventas.php";
});
// Asegurarse de que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function () {
    // Detectar el clic en el enlace para "Crear Pedido"
    var altaPedidoBtn = document.getElementById('altaPedido');

    if (altaPedidoBtn) {
        altaPedidoBtn.addEventListener('click', function (e) {
            // Prevenir el comportamiento por defecto (redirigir)
            e.preventDefault();

            // Redirigir a la página 'altaPedido.php'
            window.location.href = 'altaPedido.php';
        });
    }
    // Verificar si estamos en la página de creación de pedidos
    if (window.location.pathname.includes('altaPedido.php')) {

        obtenerFolioSiguiente();

        // Si necesitas hacer otras acciones, puedes hacerlo aquí
    }
});
