// Referencias a los elementos
const btnDatosGenerales = document.getElementById('btnDatosGenerales');
const btnDatosVentas = document.getElementById('btnDatosVentas');
const formDatosGenerales = document.getElementById('formDatosGenerales');
const formDatosVentas = document.getElementById('formDatosVentas');

// Mostrar Datos Generales
btnDatosGenerales.addEventListener('click', () => {
    formDatosGenerales.style.display = 'block'; // Mostrar Datos Generales
    formDatosVentas.style.display = 'none';    // Ocultar Datos Ventas
    btnDatosGenerales.classList.add('btn-primary');
    btnDatosGenerales.classList.remove('btn-secondary');
    btnDatosVentas.classList.add('btn-secondary');
    btnDatosVentas.classList.remove('btn-primary');
});

// Mostrar Datos Ventas
btnDatosVentas.addEventListener('click', () => {
    formDatosGenerales.style.display = 'none'; // Ocultar Datos Generales
    formDatosVentas.style.display = 'block';  // Mostrar Datos Ventas
    btnDatosVentas.classList.add('btn-primary');
    btnDatosVentas.classList.remove('btn-secondary');
    btnDatosGenerales.classList.add('btn-secondary');
    btnDatosGenerales.classList.remove('btn-primary');
});


const noEmpresa = sessionStorage.getItem('noEmpresaSeleccionada');

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
                        const saldoFormateado = `$${parseFloat(cliente.SALDO || 0).toFixed(2).toLocaleString('es-MX')}`;
                        // Asignamos los valores de los campos del cliente
                        // Asignar los valores de los campos del cliente, con valores por defecto si están indefinidos
                        document.getElementById('clave').value = cliente.CLAVE || 'Sin clave';
                        document.getElementById('nombre').value = cliente.NOMBRE || 'Sin nombre';
                        document.getElementById('estatus').value = cliente.STATUS || 'Sin clasificación';
                        document.getElementById('saldo').value = saldoFormateado || '0';
                        document.getElementById('rfc').value = cliente.RFC || 'Sin RFC';
                        document.getElementById('calle').value = cliente.CALLE || 'Sin calle';
                        document.getElementById('numE').value = cliente.NUMEXT || 'Sin número exterior';
                        document.getElementById('regimenFiscal').value = cliente.REGIMENFISCAL || 'Sin régimen fiscal';
                        document.getElementById('numI').value = cliente.NUMINT || 'Sin número interior';
                        document.getElementById('curp').value = cliente.CURP || 'Sin CURP';
                        document.getElementById('entreCalle').value = cliente.ENTRECALLE || 'Sin entre calle';
                        document.getElementById('yCalle').value = cliente.YCALLE || 'Sin calle';
                        document.getElementById('nacionalidad').value = cliente.NACIONALIDAD || 'Sin nacionalidad';
                        document.getElementById('estado').value = cliente.ESTADO || 'Sin estado';
                        document.getElementById('poblacion').value = cliente.POBLACION || 'Sin población';
                        document.getElementById('pais').value = cliente.PAIS || 'Sin país';
                        document.getElementById('codigoPostal').value = cliente.CODIGOPOSTAL || 'Sin código postal';
                        document.getElementById('municipio').value = cliente.MUNICIPIO || 'Sin municipio';
                        document.getElementById('colonia').value = cliente.COLONIA || 'Sin colonia';
                        document.getElementById('referencia').value = cliente.REFERENCIA || 'Sin referencia';
                        document.getElementById('clasificacion').value = cliente.CLASIFICACION || 'Sin clasificación';
                        document.getElementById('telefono').value = cliente.TELEFONO || 'Sin teléfono';
                        document.getElementById('zona').value = cliente.ZONA || 'Sin zona';
                        document.getElementById('fax').value = cliente.FAX || 'Sin fax';
                        document.getElementById('paginaWeb').value = cliente.PAGINAWEB || 'Sin página web';

                        //document.getElementById('manejoCredito').value = cliente.CON_CREDITO;
                        document.getElementById('manejoCredito').checked = cliente.CON_CREDITO === "S";
                        document.getElementById('diaRevision').value = cliente.DIAREV || 'Sin dias de revicion';
                        document.getElementById('diasCredito').value = cliente.DIASCRED || 'Sin dias de credito';
                        document.getElementById('diaPago').value = cliente.DIAPAGO || '';
                        //document.getElementById('limiteCredito').value = cliente.PAGINAWEB || 'Sin página web';
                        document.getElementById('saldoVentas').value = saldoFormateado || 'Sin saldo';
                        document.getElementById('metodoPago').value = cliente.METODODEPAGO || 'Sin metodo de pago';

                        document.getElementById('listaPrecios').value = cliente.LISTA_PREC || '1';
                        document.getElementById('descuento').value = cliente.DESCUENTO || 'Sin descuento';
                        document.getElementById('vendedor').value = cliente.CVE_VEND || 'Sin Vendedor';

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
function cerrarModal() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('usuarioModal'));
    modal.hide();
}
function obtenerClientes(){
    $.post('../Servidor/PHP/clientes.php', { numFuncion: '1', noEmpresa: noEmpresa }, function (response) {
        try {
            // Verifica si response es una cadena (string) que necesita ser parseada
            if (typeof response === 'string') {
                response = JSON.parse(response);
            }
            // Verifica si response es un objeto antes de intentar procesarlo
            if (typeof response === 'object' && response !== null) {
                if (response.success && response.data) {
                    let clientes = response.data;

                    // Eliminar duplicados basados en la 'CLAVE' (suponiendo que 'CLAVE' es única)
                    clientes = clientes.filter((value, index, self) =>
                        index === self.findIndex((t) => (
                            t.CLAVE === value.CLAVE
                        ))
                    );

                    // Ordenar los clientes por la clave (cliente.CLAVE)
                    clientes.sort((a, b) => {
                        const claveA = a.CLAVE ? parseInt(a.CLAVE) : 0;
                        const claveB = b.CLAVE ? parseInt(b.CLAVE) : 0;
                        return claveA - claveB; // Orden ascendente
                    });

                    const clientesTable = document.getElementById('datosClientes');
                    clientesTable.innerHTML = ''; // Limpiar la tabla antes de agregar nuevos datos

                    // Recorrer los clientes ordenados y agregar las filas a la tabla
                    clientes.forEach(cliente => {
                        const saldoFormateado = `$${parseFloat(cliente.SALDO || 0).toFixed(2).toLocaleString('es-MX')}`;
                        const estadoTimbrado = cliente.EstadoDatosTimbrado ?
                            "<i class='bx bx-check-square' style='color: green; display: block; margin: 0 auto;'></i>" :
                            ''; // Centrado de la palomita con display: block y margin: 0 auto
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${cliente.CLAVE || 'Sin clave'}</td>
                            <td>${cliente.NOMBRE || 'Sin nombre'}</td>
                            <td>${cliente.CALLE || 'Sin calle'}</td>
                            <td style="text-align: right;">${saldoFormateado}</td>
                            <td style="text-align: center;">${estadoTimbrado}</td> <!-- Centrar la palomita -->
                            <td>${cliente.NOMBRECOMERCIAL || 'Sin nombre comercial'}</td>
                            <td>
                                <button class="btnVisualizarCliente" name="btnVisualizarCliente" data-id="${cliente.CLAVE}" style="
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
    }, 'json').fail(function (jqXHR, textStatus, errorThrown) {
        console.error('Error en la solicitud:', textStatus, errorThrown, jqXHR);
    });
}
$(document).ready(function () {
    obtenerClientes();
    $('.cerrar-modal').click(function () {
        cerrarModal();
    });
});

function cerrarModal() {
    $('#usuarioModal').modal('hide');
}
