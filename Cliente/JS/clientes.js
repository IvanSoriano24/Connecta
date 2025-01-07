// Función para agregar eventos a los botones dinámicos
function agregarEventosBotones() {
    const botonesVisualizar = document.querySelectorAll('.btnVisualizarCliente');
    
    botonesVisualizar.forEach(boton => {
        boton.addEventListener('click', function () {
            const clienteId = this.getAttribute('data-id');
            
            // Realizamos la petición AJAX para obtener los datos del cliente
            fetch(`../Servidor/PHP/clientes.php?clave=${clienteId}&numFuncion=2`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const cliente = data.cliente;

                        // Asignamos los valores de los campos del cliente
                        document.getElementById('clave').value = cliente.CLAVE;
                        document.getElementById('nombre').value = cliente.NOMBRE;
                        document.getElementById('estatus').value = cliente.ESTATUS;
                        document.getElementById('saldo').value = cliente.SALDO;
                        document.getElementById('rfc').value = cliente.RFC;
                        document.getElementById('calle').value = cliente.CALLE;
                        document.getElementById('numE').value = cliente.NUMEXT;
                        document.getElementById('regimenFiscal').value = cliente.REGIMENFISCAL;
                        document.getElementById('numI').value = cliente.NUMINT;
                        document.getElementById('curp').value = cliente.CURP;
                        document.getElementById('entreCalle').value = cliente.ENTRECALLE;
                        document.getElementById('yCalle').value = cliente.YCALLE;
                        document.getElementById('nacionalidad').value = cliente.NACIONALIDAD;
                        document.getElementById('estado').value = cliente.ESTADO;
                        document.getElementById('poblacion').value = cliente.POBLACION;
                        document.getElementById('pais').value = cliente.PAIS;
                        document.getElementById('codigoPostal').value = cliente.CODIGOPOSTAL;
                        document.getElementById('municipio').value = cliente.MUNICIPIO;
                        document.getElementById('colonia').value = cliente.COLONIA;
                        document.getElementById('referencia').value = cliente.REFERENCIA;
                        document.getElementById('clasificacion').value = cliente.CLASIFICACION;
                        document.getElementById('telefono').value = cliente.TELEFONO;
                        document.getElementById('zona').value = cliente.ZONA;
                        document.getElementById('fax').value = cliente.FAX;
                        document.getElementById('paginaWeb').value = cliente.PAGINAWEB;

                        // Mostrar el modal si se está utilizando
                        $('#usuarioModal').modal('show'); // Asegúrate de que estás usando jQuery y Bootstrap para este modal
                    } else {
                        alert('Error al obtener la información del cliente');
                    }
                })
                .catch(error => {
                    console.error('Error al obtener los datos del cliente:', error);
                    alert('Hubo un error al intentar cargar la información del cliente.');
                });
        });
    });
}

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