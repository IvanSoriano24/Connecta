const noEmpresa = sessionStorage.getItem('noEmpresaSeleccionada');

function agregarEventosBotones() {
    // Seleccionar todos los botones con la clase btnEditarPedido
    const botones = document.querySelectorAll('.btnEditarPedido');

    // Asignar un evento de clic a cada botón
    botones.forEach(boton => {
        boton.addEventListener('click', function () {
            const pedidoID = this.dataset.id; // Obtener el ID del pedido
            // Redirigir a altaPedido.php con el ID del pedido como parámetro
            window.location.href = 'altaPedido.php?pedidoID=' + pedidoID;
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
                                <button class="btnEditarPedido" name="btnEditarPedido" data-id="${pedido.Clave}" style="
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
            <i class="fas fa-eye" style="margin-right: 0.5rem;"></i> Editar
    </button>  
                            </td>
                        `;
                        pedidosTable.appendChild(row);
                    });
                    agregarEventosBotones();
                } else {
                    // Si no hay pedidos, mostrar una fila con el mensaje "No hay datos"
                    const row = document.createElement('tr');
                    
                    // Obtener el número de columnas del encabezado
                    const numColumns = pedidosTable.querySelector('thead') 
                        ? pedidosTable.querySelector('thead').rows[0].cells.length 
                        : 13; // Valor predeterminado si no hay encabezado

                    row.innerHTML = `
                        <td colspan="${numColumns}" style="text-align: center;">No hay datos disponibles</td>
                        <td colspan="${numColumns}" style="text-align: center;">No hay datos disponibles</td>
                        <td colspan="${numColumns}" style="text-align: center;">No hay datos disponibles</td>
                    `;
                    pedidosTable.appendChild(row);
                    console.error('Error en la respuesta del servidor:', response);
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
                const pedidosTable = document.getElementById('datosPedidos');
                pedidosTable.innerHTML = ''; // Limpiar la tabla antes de agregar nuevos datos
                if (response.success && response.data) {
                    const pedidos = response.data;
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
                            <button class="btnEditarPedido" name="btnEditarPedido" data-id="${pedido.Clave}" style="
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
                                    <i class="fas fa-eye" style="margin-right: 0.5rem;"></i> Editar
                            </button>  
                        </td>
                    `;
                        pedidosTable.appendChild(row);
                    });
                    agregarEventosBotones();
                } else {
                    // Si no hay pedidos, mostrar una fila con el mensaje "No hay datos"
                    const row = document.createElement('tr');
                    
                    // Obtener el número de columnas del encabezado
                    const numColumns = pedidosTable.querySelector('thead') 
                        ? pedidosTable.querySelector('thead').rows[0].cells.length 
                        : 13; // Valor predeterminado si no hay encabezado

                    row.innerHTML = `
                        <td colspan="${numColumns}" style="text-align: center;">No hay datos disponibles</td>
                        <td colspan="${numColumns}" style="text-align: center;">No hay datos disponibles</td>
                        <td colspan="${numColumns}" style="text-align: center;">No hay datos disponibles</td>
                    `;
                    pedidosTable.appendChild(row);
                    console.error('Error en la respuesta del servidor:', response);
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
            // Redirigir a la página 'altaPedido.php' sin parámetro
            window.location.href = 'altaPedido.php'; // CORRECCIÓN AQUÍ
        });
    }

    // Verificar si estamos en la página de creación o edición de pedidos
    if (window.location.pathname.includes('altaPedido.php')) {
        // Si es edición, obtén el pedidoID y carga los datos del pedido si existe
        const urlParams = new URLSearchParams(window.location.search);
        const pedidoID = urlParams.get('pedidoID');

        if (pedidoID) {
            // Aquí iría la lógica para obtener los datos del pedido y prellenar el formulario
            obtenerDatosPedido(pedidoID);
        } else {
            // Aquí puedes manejar la lógica para la creación de un nuevo pedido
            obtenerFolioSiguiente();
        }
    }
});
