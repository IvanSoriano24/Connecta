const noEmpresa = sessionStorage.getItem('noEmpresaSeleccionada');
let partidasData = []; // Este contiene las partidas actuales del formulario

function agregarEventosBotones() {
    // Botones de editar
    const botonesEditar = document.querySelectorAll('.btnEditarPedido');
    botonesEditar.forEach(boton => {
        boton.addEventListener('click', function () {
            const pedidoID = this.dataset.id; // Obtener el ID del pedido
            console.log('Redirigiendo con pedidoID:', pedidoID); // Log en consola
            // Redirigir a altaPedido.php con el ID del pedido como parámetro
            window.location.href = 'altaPedido.php?pedidoID=' + pedidoID;
        });
    });

    // Botones de eliminar
    const botonesEliminar = document.querySelectorAll('.btnCancelarPedido');
    botonesEliminar.forEach(boton => {
        boton.addEventListener('click', function () {
            const pedidoID = this.dataset.id; // Obtener el ID del pedido
            Swal.fire({
                title: '¿Estás seguro?',
                text: "Esta acción no se puede deshacer",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    eliminarPedido(pedidoID); // Llama a la función para eliminar el pedido
                }
            });
        });
    });
}

function eliminarPedido(pedidoID) {
    $.post('../Servidor/PHP/ventas.php', { numFuncion: '10', pedidoID: pedidoID }, function (response) {
        try {
            if (typeof response === 'string') {
                response = JSON.parse(response);
            }
            if (response.success) {
                Swal.fire({
                    title: 'Eliminado',
                    text: 'El pedido ha sido eliminado correctamente',
                    icon: 'success',
                    confirmButtonText: 'Entendido'
                }).then(() => {
                    datosPedidos(); // Actualizar la tabla después de eliminar
                });
            } else {
                Swal.fire({
                    title: 'Error',
                    text: response.message || 'No se pudo eliminar el pedido',
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
            }
        } catch (error) {
            console.error('Error al procesar la respuesta JSON:', error);
        }
    }).fail(function (jqXHR, textStatus, errorThrown) {
        Swal.fire({
            title: 'Error',
            text: 'Hubo un problema al intentar eliminar el pedido',
            icon: 'error',
            confirmButtonText: 'Entendido'
        });
        console.log('Detalles del error:', jqXHR.responseText);
    });
}

function cargarPedidos(filtroFecha) {
    $.post('../Servidor/PHP/ventas.php', {
        numFuncion: '1',
        noEmpresa: noEmpresa,
        filtroFecha: filtroFecha // Pasamos el valor del filtro de fecha al servidor
    }, function (response) {
        try {
            if (typeof response === 'string') {
                response = JSON.parse(response);
            }

            if (typeof response === 'object' && response !== null) {
                if (response.success && response.data) {
                    let pedidos = response.data;

                    // Ordenar pedidos por clave (de más reciente a más antigua)
                    pedidos = pedidos.sort((a, b) => {
                        const claveA = parseInt(a.Clave, 10) || 0;
                        const claveB = parseInt(b.Clave, 10) || 0;
                        return claveB - claveA;
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
                            <td>${pedido.FechaElaboracion?.date || 'Sin fecha'}</td>
                            <td style="text-align: right;">${pedido.Subtotal ? Math.floor(pedido.Subtotal) : 'Sin subtotal'}</td>
                            <td style="text-align: right;">${pedido.TotalComisiones ? `$${parseFloat(pedido.TotalComisiones).toFixed(2)}` : 'Sin Comisiones'}</td>
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
                                    transition: background-color 0.3s ease;">
                                    <i class="fas fa-eye" style="margin-right: 0.5rem;"></i> Editar
                                </button>
                                <br>
                            </td>
                            <td>
                                <button class="btnCancelarPedido" name="btnCancelarPedido" data-id="${pedido.Clave}" style="
                                    display: inline-flex;
                                    align-items: center;
                                    padding: 0.5rem 1rem;
                                    font-size: 1rem;
                                    font-family: Lato;
                                    color: #fff;
                                    background-color: #dc3545;
                                    border: none;
                                    border-radius: 0.25rem;
                                    cursor: pointer;
                                    transition: background-color 0.3s ease;">
                                    <i class="fas fa-trash" style="margin-right: 0.5rem;"></i> Cancelar
                                </button>
                            </td>
                        `;
                        pedidosTable.appendChild(row);
                    });
                    agregarEventosBotones();
                } else {
                    const row = document.createElement('tr');
                    const numColumns = pedidosTable.querySelector('thead')
                        ? pedidosTable.querySelector('thead').rows[0].cells.length
                        : 13;
                    row.innerHTML = `<td colspan="${numColumns}" style="text-align: center;">No hay datos disponibles</td>`;
                    pedidosTable.appendChild(row);
                }
            }
        } catch (error) {
            console.error('Error al procesar la respuesta JSON:', error);
        }
    }, 'json').fail(function (jqXHR, textStatus, errorThrown) {
        console.error('Error en la solicitud:', textStatus, errorThrown);
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
/* <td style="text-align: right;">${pedido.Subtotal ? `$${parseFloat(pedido.Subtotal).toFixed(2)}` : 'Sin subtotal'}</td> */
                    pedidos.forEach(pedido => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                        <td>${pedido.Tipo || 'Sin tipo'}</td>
                        <td>${pedido.Clave || 'Sin nombre'}</td>
                        <td>${pedido.Cliente || 'Sin cliente'}</td>
                        <td>${pedido.Nombre || 'Sin nombre'}</td>
                        <td>${pedido.Estatus || '0'}</td>
                        <td>${pedido.FechaElaboracion?.date || 'Sin fecha'}</td> <!-- Maneja el objeto anidado -->
                        <td style="text-align: right;">${pedido.Subtotal ? Math.floor(pedido.Subtotal) : 'Sin subtotal'}</td>
                        <td style="text-align: right;">${pedido.TotalComisiones ? `$${parseFloat(pedido.TotalComisiones).toFixed(2)}` : 'Sin Comisiones'}</td>
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
                                transition: background-color 0.3s ease;">
                                <i class="fas fa-eye" style="margin-right: 0.5rem;"></i> Editar
                            </button>
                            <br>
                            </td>
                            <td>
                            <button class="btnCancelarPedido" name="btnCancelarPedido" data-id="${pedido.Clave}" style="
                                display: inline-flex;
                                align-items: center;
                                padding: 0.5rem 1rem;
                                font-size: 1rem;
                                font-family: Lato;
                                color: #fff;
                                background-color: #dc3545;
                                border: none;
                                border-radius: 0.25rem;
                                cursor: pointer;
                                transition: background-color 0.3s ease;">
                                <i class="fas fa-trash" style="margin-right: 0.5rem;"></i> Cancelar
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
// ORIGINAL function obtenerDatosPedido(pedidoID) {
//     // Obtener el número de empresa desde el sessionStorage
//     const noEmpresa = sessionStorage.getItem('noEmpresaSeleccionada');
//     if (!noEmpresa) {
//         alert('No se ha seleccionado ninguna empresa.');
//         return;
//     }

//     // Realizar la solicitud al servidor
//     $.post('../Servidor/PHP/ventas.php', {
//         numFuncion: '2',  // Indica al servidor que esta es la función correspondiente
//         pedidoID: pedidoID,
//         noEmpresa: noEmpresa // Incluye el número de empresa en la solicitud
//     }, function (response) {
//         // Depuración: Imprimir la respuesta del servidor
//         console.log('Respuesta del servidor:', response);
//         alert('Respuesta del servidor: ' + JSON.stringify(response));

//         // Verificar si la respuesta indica éxito
//         if (response.success) {
//             const pedido = response.pedido; // Los datos del pedido vienen aquí
//             console.log('Datos del pedido:', pedido);

//             // Llenar los campos del formulario con los datos recibidos
//             document.getElementById('nombre').value = pedido.NOMBRE_CLIENTE || '';
//             document.getElementById('rfc').value = pedido.RFC || '';
//             document.getElementById('calle').value = pedido.CALLE || '';
//             document.getElementById('numE').value = pedido.NUMEXT || '';
//             document.getElementById('colonia').value = pedido.COLONIA || '';
//             document.getElementById('codigoPostal').value = pedido.CODIGO || '';
//             document.getElementById('pais').value = pedido.PAIS || '';
//             document.getElementById('condicion').value = pedido.CONDICION || '';
//             document.getElementById('almacen').value = pedido.NUM_ALMA || '';
//             document.getElementById('comision').value = pedido.COM_TOT || '';
//             document.getElementById('diaAlta').value = pedido.FECHA_DOC || '';
//             document.getElementById('entrega').value = pedido.FECHA_ENT || '';

//             document.getElementById('enviar').value = pedido.DAT_ENVIO || '';
//             document.getElementById('descuento').value = pedido.DES_TOT || '';
//             document.getElementById('numero').value = pedido.CVE_DOC || '';
//             document.getElementById('descuentofin').value = pedido.DES_FIN || '';
//             document.getElementById('cliente').value = pedido.CVE_CLPV || '';
//             document.getElementById('supedido').value = pedido.CONDICION || '';
//             document.getElementById('esquema').value = pedido.CONDICION || '';

//             alert('Datos del pedido cargados correctamente.');
//         } else {
//             // Mostrar un mensaje de error si no se encuentra el pedido
//             console.error('Error al obtener datos del pedido:', response.message);
//             alert('Error al obtener datos del pedido: ' + response.message);
//         }
//     }, 'json').fail(function (jqXHR, textStatus, errorThrown) {
//         // Manejo de errores en la solicitud
//         console.error('Error en la solicitud:', textStatus, errorThrown);
//         alert('Error en la solicitud: ' + textStatus + ' ' + errorThrown);
//     });
// }


// Función para obtener el siguiente folio
function obtenerDatosPedido(pedidoID) {
    $.post('../Servidor/PHP/ventas.php', {
        numFuncion: '2', // Función para obtener el pedido por ID
        pedidoID: pedidoID
    }, function (response) {
        if (response.success) {
            const pedido = response.pedido;
            console.log('Datos del pedido:', pedido);

            // Cargar datos del cliente

            document.getElementById('nombre').value = pedido.NOMBRE_CLIENTE || '';
            document.getElementById('rfc').value = pedido.RFC || '';
            document.getElementById('calle').value = pedido.CALLE || '';
            document.getElementById('numE').value = pedido.NUMEXT || '';
            document.getElementById('colonia').value = pedido.COLONIA || '';
            document.getElementById('codigoPostal').value = pedido.CODIGO || '';
            document.getElementById('pais').value = pedido.PAIS || '';
            document.getElementById('condicion').value = pedido.CONDICION || '';
            document.getElementById('almacen').value = pedido.NUM_ALMA || '';
            document.getElementById('comision').value = pedido.COM_TOT || '';
            document.getElementById('diaAlta').value = pedido.FECHA_DOC || '';
            document.getElementById('entrega').value = pedido.FECHA_ENT || '';

            document.getElementById('enviar').value = pedido.DAT_ENVIO || '';
            document.getElementById('descuento').value = pedido.DES_TOT || '';
            document.getElementById('numero').value = pedido.CVE_DOC || '';
            document.getElementById('descuentofin').value = pedido.DES_FIN || '';
            document.getElementById('cliente').value = pedido.CVE_CLPV || '';
            document.getElementById('supedido').value = pedido.CONDICION || '';
            document.getElementById('esquema').value = pedido.CONDICION || '';

            // Actualizar estado de cliente seleccionado en sessionStorage
            sessionStorage.setItem('clienteSeleccionado', true);

            // Cargar las partidas existentes
            //cargarPartidas(pedido.partidas);
            //alert("Datos del pedido cargados con éxito");


            console.log('Datos del pedido cargados correctamente.');
        } else {
            alert('No se pudo cargar el pedido: ' + response.message);
        }
    }, 'json').fail(function (jqXHR, textStatus, errorThrown) {
        alert('Error al cargar el pedido: ' + textStatus + ' ' + errorThrown);
    });
}
// function cargarPartidasPedido(pedidoID) {
//     console.log("Cargando partidas para el pedido:", pedidoID);

//     $.post('../Servidor/PHP/ventas.php', {
//         numFuncion: '3',
//         accion: 'obtenerPartidas',
//         clavePedido: pedidoID
//     }, function (response) {
//         console.log("Respuesta del servidor para partidas:", response);

//         if (response.success) {
//             const partidas = response.partidas;

//             const tablaProductos = document.querySelector("#tablaProductos tbody");
//             tablaProductos.innerHTML = ''; // Limpia la tabla antes de agregar las partidas

//             partidas.forEach(partida,index => {
//                 const nuevaFila = document.createElement("tr");
//                 nuevaFila.innerHTML = `
//                     <td>
//                         <button type="button" class="btn btn-danger btn-sm eliminarPartida" data-index="${index}">
//                             <i class="bx bx-trash"></i>
//                         </button>
//                     </td>
//                     <td><input type="number" class="cantidad" value="${partida.CANT}"  /></td>
//                     <td>
//                         <div class="d-flex flex-column position-relative">
//                             <div class="d-flex align-items-center">
//                                 <input type="text" class="producto " placeholder=""  value="${partida.DESCR_ART}" oninput="mostrarSugerencias(this)" />
//                                 <button type="button" class="btn ms-2" onclick="mostrarProductos(this.closest('tr').querySelector('.producto'))"><i class="bx bx-search"></i></button>
//                             </div>
//                             <ul class="lista-sugerencias position-absolute bg-white list-unstyled border border-secondary mt-1 p-2 d-none"></ul>
//                         </div>
//                     </td>
//                     <td><input type="text" class="unidad" value="Unidad" readonly /></td>
//                     <td><input type="number" class="descuento1" value="${partida.DESC1}" readonly /></td>
//                     <td><input type="number" class="descuento2" value="${partida.DESC2}" readonly /></td>
//                     <td><input type="number" class="ieps" value="${partida.IMPU1}" readonly /></td>
//                     <td><input type="number" class="iva" value="${partida.IMPU4}" readonly /></td>
//                     <td><input type="number" class="comision" value="${partida.COMI}" readonly /></td>
//                     <td><input type="number" class="precioUnidad" value="${partida.PREC}" readonly /></td>
//                     <td><input type="number" class="subtotalPartida" value="${partida.TOT_PARTIDA}" readonly /></td>
//                 `;
//                 tablaProductos.appendChild(nuevaFila);
//             });


//             // Agregar evento de eliminación a los botones
//             document.querySelectorAll(".eliminarPartida").forEach(button => {
//                 button.addEventListener("click", function () {
//                     const index = this.getAttribute("data-index");
//                     eliminarPartida(index);
//                 });
//             });

//             console.log("Partidas cargadas correctamente.");
//         } else {
//             console.error("Error al obtener partidas:", response.message);
//             alert('No se pudieron cargar las partidas: ' + response.message);
//         }
//     }, 'json').fail(function (jqXHR, textStatus, errorThrown) {
//         console.error("Error al cargar las partidas:", textStatus, errorThrown);
//         alert('Error al cargar las partidas: ' + textStatus + ' ' + errorThrown);
//     });
// }

function cargarPartidasPedido(pedidoID) {
    console.log("Cargando partidas para el pedido:", pedidoID);

    $.post('../Servidor/PHP/ventas.php', {
        numFuncion: '3',
        accion: 'obtenerPartidas',
        clavePedido: pedidoID
    }, function (response) {

        if (response.success) {
            const partidas = response.partidas;
            partidasData = [...partidas]; // Almacena las partidas en el array global
            actualizarTablaPartidas(); // Actualiza la tabla visualmente
            console.log("Partidas cargadas correctamente.");
        } else {
            console.error("Error al obtener partidas:", response.message);
            alert('No se pudieron cargar las partidas: ' + response.message);
        }
    }, 'json').fail(function (jqXHR, textStatus, errorThrown) {
        console.error("Error al cargar las partidas:", textStatus, errorThrown);
        alert('Error al cargar las partidas: ' + textStatus + ' ' + errorThrown);
    });
}
function actualizarTablaPartidas() {
    const tablaProductos = document.querySelector("#tablaProductos tbody");
    tablaProductos.innerHTML = ''; // Limpia la tabla

    partidasData.forEach((partida) => {
        const nuevaFila = document.createElement("tr");
        nuevaFila.setAttribute("data-num-par", partida.NUM_PAR); // Identifica cada fila por NUM_PAR

        nuevaFila.innerHTML = `
    <td>
        <button type="button" class="btn btn-danger btn-sm eliminarPartida" onclick="eliminarPartidaFormulario(${partida.NUM_PAR})">
            <i class="bx bx-trash"></i>
        </button>
    </td>
    <td><input type="number" class="cantidad" value="${partida.CANT}" /></td>
    <td>
        <div class="d-flex flex-column position-relative">
            <div class="d-flex align-items-center">
                <input type="text" class="producto" placeholder="" value="${partida.CVE_ART}" oninput="mostrarSugerencias(this)" />
                <button type="button" class="btn ms-2" onclick="mostrarProductos(this.closest('tr').querySelector('.producto'))"><i class="bx bx-search"></i></button>
            </div>
            <ul class="lista-sugerencias position-absolute bg-white list-unstyled border border-secondary mt-1 p-2 d-none"></ul>
        </div>
    </td>
    <td><input type="text" class="unidad" value="Unidad" readonly /></td>
    <td><input type="number" class="descuento1" value="${partida.DESC1}" readonly /></td>
    <td><input type="number" class="descuento2" value="${partida.DESC2}" readonly /></td>
    <td><input type="number" class="ieps" value="${partida.IMPU1}" readonly /></td>
    <td><input type="number" class="iva" value="${partida.IMPU4}" readonly /></td>
    <td><input type="number" class="comision" value="${partida.COMI}" readonly /></td>
    <td><input type="number" class="precioUnidad" value="${partida.PREC}" readonly /></td>
    <td><input type="number" class="subtotalPartida" value="${partida.TOT_PARTIDA}" readonly /></td>
    <td><input type="number" class="impuesto2" value="0" readonly hidden /></td>
    <td><input type="number" class="impuesto3" value="0" readonly hidden /></td>
`;
        tablaProductos.appendChild(nuevaFila);
    });
}

function eliminarPartidaFormulario(numPar) {
    // Filtrar las partidas para excluir la eliminada
    partidasData = partidasData.filter((partida) => partida.NUM_PAR !== numPar);

    // Actualizar la tabla visualmente
    actualizarTablaPartidas();

    console.log("Partidas actuales después de eliminar:", partidasData);
}


function limpiarTablaPartidas() {
    const tablaProductos = document.querySelector("#tablaProductos tbody");
    tablaProductos.innerHTML = ''; // Limpia todas las filas de la tabla
}


function guardarPedido() {
    const urlParams = new URLSearchParams(window.location.search);
    const pedidoID = urlParams.get('pedidoID'); // Si existe, estamos en modo edición

    const datosPedido = {
        pedidoID: pedidoID || null, // Si es edición, incluye el ID del pedido
        cliente: document.getElementById('cliente').value,
        rfc: document.getElementById('rfc').value,
        direccion: document.getElementById('direccion').value,
        partidas: []
    };

    // Recolectar las partidas de la tabla
    const tablaProductos = document.querySelector("#tablaProductos tbody");
    const filas = tablaProductos.querySelectorAll("tr");
    filas.forEach(fila => {
        const partida = {
            cantidad: fila.querySelector(".cantidad").value,
            producto: fila.querySelector(".producto").value,
            unidad: fila.querySelector(".unidad").value,
            descuento1: fila.querySelector(".descuento1").value,
            descuento2: fila.querySelector(".descuento2").value,
            ieps: fila.querySelector(".ieps").value,
            iva: fila.querySelector(".iva").value,
            comision: fila.querySelector(".comision").value,
            precioUnidad: fila.querySelector(".precioUnidad").value,
            subtotal: fila.querySelector(".subtotalPartida").value
        };
        datosPedido.partidas.push(partida);
    });

    // Enviar datos al servidor
    $.post('../Servidor/PHP/guardarPedido.php', datosPedido, function (response) {
        if (response.success) {
            alert('Pedido guardado correctamente');
            window.location.href = 'listaPedidos.php'; // Redirigir a la lista de pedidos
        } else {
            alert('Error al guardar el pedido: ' + response.message);
        }
    }, 'json').fail(function (jqXHR, textStatus, errorThrown) {
        alert('Error en la solicitud: ' + textStatus + ' ' + errorThrown);
    });
}
function obtenerFolioSiguiente() {
    return new Promise((resolve, reject) => {
        $.post('../Servidor/PHP/ventas.php', {
            numFuncion: '3', // Caso 3: Obtener siguiente folio
            accion: 'obtenerFolioSiguiente',
        }, function (response) {
            try {
                const data = JSON.parse(response);
                if (data.success) {
                    console.log("El siguiente folio es: " + data.folioSiguiente);
                    document.getElementById('numero').value = data.folioSiguiente;
                    resolve(data.folioSiguiente); // Resuelve la promesa con el folio
                } else {
                    console.log("Error: " + data.message);
                    reject(data.message); // Rechaza la promesa con el mensaje de error
                }
            } catch (error) {
                reject("Error al procesar la respuesta: " + error.message);
            }
        }).fail(function (xhr, status, error) {
            reject("Error de AJAX: " + error);
        });
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
    window.location.href = "Ventas.php";
});
document.addEventListener('DOMContentLoaded', function () {
    let clienteSeleccionado = sessionStorage.getItem('clienteSeleccionado') === 'true'; 
    // Detectar el clic en el enlace para "Crear Pedido"
    var altaPedidoBtn = document.getElementById('altaPedido');
    if (altaPedidoBtn) {
        altaPedidoBtn.addEventListener('click', function (e) {
            // Prevenir el comportamiento por defecto (redirigir)
            e.preventDefault();
            console.log('Redirigiendo a altaPedido.php...');
            // Redirigir a la página 'altaPedido.php' sin parámetro
            window.location.href = 'altaPedido.php';
        });
    }

    // Verificar si estamos en la página de creación o edición de pedidos
    if (window.location.pathname.includes('altaPedido.php')) {
        // Obtener parámetros de la URL
        const urlParams = new URLSearchParams(window.location.search);
        const pedidoID = urlParams.get('pedidoID'); // Puede ser null si no está definido

        console.log('ID del pedido recibido:', pedidoID); // Log en consola para depuración

        if (pedidoID) {
            // Si es un pedido existente (pedidoID no es null)
            console.log('Cargando datos del pedido existente...');
            obtenerDatosPedido(pedidoID); // Función para cargar datos del pedido
            cargarPartidasPedido(pedidoID); // Función para cargar partidas del pedido
        } else {
            sessionStorage.setItem('clienteSeleccionado', false);
            clienteSeleccionado = false;
            // Si es un nuevo pedido (pedidoID es null)
            console.log('Preparando formulario para un nuevo pedido...');
            obtenerFecha(); // Establecer la fecha inicial del pedido
            limpiarTablaPartidas(); // Limpiar la tabla de partidas para el nuevo pedido
            obtenerFolioSiguiente(); // Generar el siguiente folio para el pedido
        }
    }
});

