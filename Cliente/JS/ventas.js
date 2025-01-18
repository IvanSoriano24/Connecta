const noEmpresa = sessionStorage.getItem('noEmpresaSeleccionada');

function agregarEventosBotones() {
    // Seleccionar todos los botones con la clase btnEditarPedido
    const botones = document.querySelectorAll('.btnEditarPedido');

    // Asignar un evento de clic a cada botón
    botones.forEach(boton => {
        boton.addEventListener('click', function () {
            const pedidoID = this.dataset.id; // Obtener el ID del pedido

            console.log('Redirigiendo con pedidoID:', pedidoID); // Log en consola
            alert('Redirigiendo con pedidoID: ' + pedidoID); // Alerta para verificar

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
                    let pedidos = response.data;

                    // Ordenar pedidos por clave (de más reciente a más antigua)
                    pedidos = pedidos.sort((a, b) => {
                        const claveA = parseInt(a.Clave, 10) || 0;
                        const claveB = parseInt(b.Clave, 10) || 0;
                        return claveB - claveA; // Orden descendente
                    });

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
                            <td style="text-align: right;">${pedido.Subtotal ? `$${parseFloat(pedido.Subtotal).toFixed(2)}` : 'Sin subtotal'}</td>
                            <td style="text-align: right;">${pedido.TotalComisiones ? `$${parseFloat(pedido.TotalComisiones).toFixed(2)}` : 'Sin Comisiones'}</td>

                            <td>${pedido.NumeroAlmacen || 'Sin almacén'}</td>
                            <td>${pedido.FormaEnvio || 'Sin forma de envío'}</td>
                            <td style="text-align: right;">${pedido.ImporteTotal ? `$${parseFloat(pedido.ImporteTotal).toFixed(2)}` : 'Sin importe'}</td>
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
                    let pedidos = response.data;

                    // Ordenar pedidos por clave en orden descendente
                    pedidos = pedidos.sort((a, b) => {
                        const claveA = parseInt(a.Clave, 10) || 0;
                        const claveB = parseInt(b.Clave, 10) || 0;
                        return claveB - claveA; // Orden descendente
                    });

                    pedidos.forEach(pedido => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                        <td>${pedido.Tipo || 'Sin tipo'}</td>
                        <td>${pedido.Clave || 'Sin nombre'}</td>
                        <td>${pedido.Cliente || 'Sin cliente'}</td>
                        <td>${pedido.Nombre || 'Sin nombre'}</td>
                        <td>${pedido.Estatus || '0'}</td>
                        <td>${pedido.FechaElaboracion?.date || 'Sin fecha'}</td> <!-- Maneja el objeto anidado -->
                        <td style="text-align: right;">${pedido.Subtotal ? `$${parseFloat(pedido.Subtotal).toFixed(2)}` : 'Sin subtotal'}</td>
                        <td style="text-align: right;">${pedido.TotalComisiones ? `$${parseFloat(pedido.TotalComisiones).toFixed(2)}` : 'Sin Comisiones'}</td>

                        <td>${pedido.NumeroAlmacen || 'Sin almacén'}</td>
                        <td>${pedido.FormaEnvio || 'Sin forma de envío'}</td>
                        <td style="text-align: right;">${pedido.ImporteTotal ? `$${parseFloat(pedido.ImporteTotal).toFixed(2)}` : 'Sin importe'}</td>
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
function obtenerDatosPedido(pedidoID) {
    $.post('../Servidor/PHP/ventas.php', {
        numFuncion: '2',  // Suponiendo que '2' es la función en el servidor que obtiene los datos del pedido
        pedidoID: pedidoID
    }, function (response) {

        console.log('Respuesta del servidor:', response); // Log en consola
        alert('Respuesta del servidor: ' + JSON.stringify(response)); // Alerta con datos

        if (response.success) {
            console.log('Datos del pedido:', response.cliente);
            alert('Datos del pedido: ' + JSON.stringify(response.cliente));
            // Prellenar los campos con los datos obtenidos
            document.getElementById('nombre').value = response.cliente.NOMBRE || '';  // Asignar el valor del campo NOMBRE
            document.getElementById('rfc').value = response.cliente.RFC || '';        // Asignar el valor del campo RFC
            document.getElementById('calle').value = response.cliente.CALLE || '';    // Asignar el valor del campo CALLE
            document.getElementById('numE').value = response.cliente.NUMEXT || '';    // Asignar el valor del campo NUMEXT
            document.getElementById('colonia').value = response.cliente.COLONIA || ''; // Asignar el valor del campo COLONIA
            document.getElementById('codigoPostal').value = response.cliente.CODIGO || '';  // Asignar el valor del campo CODIGO (CP)
            document.getElementById('pais').value = response.cliente.PAIS || '';      // Asignar el valor del campo PAIS
            document.getElementById('regimenFiscal').value = response.cliente.CVE_ZONA || ''; // Asignar el valor del campo CVE_ZONA
            document.getElementById('vendedor').value = response.cliente.FAX || '';    // Asignar el valor del campo FAX (suponiendo que se refiere al vendedor)

            // Continúa prellenando los demás campos según sea necesario
        } else {
            console.error('Error al obtener datos del pedido:', response.message);
            alert('Error al obtener datos del pedido: ' + response.message);
        }
    }).fail(function (jqXHR, textStatus, errorThrown) {
        console.error('Error en la solicitud:', textStatus, errorThrown);
        alert('Error en la solicitud: ' + textStatus + ' ' + errorThrown);
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
function obtenerFecha() {
    const today = new Date();
    const formattedDate = today.toISOString().split('T')[0];
    document.getElementById('diaAlta').value = formattedDate;
}

$('#filtroFecha').change(function () {
    var filtroSeleccionado = $(this).val(); // Obtener el valor seleccionado del filtro
    cargarPedidos(filtroSeleccionado); // Llamar la función para cargar los pedidos con el filtro
});
$('#cancelarPedido').click(function () {
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
            console.log('Redirigiendo a altaPedido.php...');
            // Redirigir a la página 'altaPedido.php' sin parámetro
            window.location.href = 'altaPedido.php'; // CORRECCIÓN AQUÍ
        });
    }

    // Verificar si estamos en la página de creación o edición de pedidos
    if (window.location.pathname.includes('altaPedido.php')) {
        // Si es edición, obtén el pedidoID y carga los datos del pedido si existe
        const urlParams = new URLSearchParams(window.location.search);
        const pedidoID = urlParams.get('pedidoID');

        console.log('ID del pedido recibido:', pedidoID); // Log en consola
        alert('ID del pedido recibido: ' + pedidoID); // Alerta para verificar

        if (pedidoID) {
            // Aquí iría la lógica para obtener los datos del pedido y prellenar el formulario
            obtenerDatosPedido(pedidoID);
        } else {
            obtenerFecha();
            console.log('Creando un nuevo pedido...');
            // Aquí puedes manejar la lógica para la creación de un nuevo pedido
            obtenerFolioSiguiente();
        }
    }
});

