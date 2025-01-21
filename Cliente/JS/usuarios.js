function datosUsuarios(tipoUsuario, usuario) {
    $.ajax({
        url: '../Servidor/PHP/usuarios.php', 
        type: 'POST',
        data: { usuarioLogueado: tipoUsuario, usuario: usuario, numFuncion: '3' },
        success: function(response) {
            if (response.success) {
                mostrarUsuarios(response.data); // Llama a otra función para mostrar los usuarios en la página
            } else {
                console.log(response.message);
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Error en la solicitud AJAX.');
        }
    });
}
function mostrarUsuarios(usuarios) {
    var tablaClientes = $('#tablaUsuarios'); // Seleccionamos el cuerpo de la tabla donde se insertarán los usuarios
    tablaClientes.empty(); // Limpiamos cualquier dato anterior en la tabla
    // Ordenamos los usuarios por nombreCompleto de forma alfabética
    usuarios.sort(function(a, b) {
        var nombreA = a.nombreCompleto.toUpperCase(); // Convertimos a mayúsculas para evitar problemas con el orden
        var nombreB = b.nombreCompleto.toUpperCase();
        if (nombreA < nombreB) {
            return -1; // Si nombreA es menor, se coloca primero
        }
        if (nombreA > nombreB) {
            return 1; // Si nombreA es mayor, se coloca después
        }
        return 0; // Si son iguales, no cambia el orden
    });
    // Recorremos la lista de usuarios y generamos las filas de la tabla
    usuarios.forEach(function(usuario) {
        var fila = '<tr>';
        fila += '<td>' + usuario.nombreCompleto + '</td>';
        fila += '<td>' + usuario.correo + '</td>';
        fila += '<td>' + usuario.estatus + '</td>';
        fila += '<td>' + usuario.rol + '</td>';
        fila += '<td><button class="btn btn-info btn-sm" onclick="editarUsuario(\'' + usuario.id + '\')">Editar</button></td>';
        fila += '</tr>';
        // Insertamos la fila en el cuerpo de la tabla
        tablaClientes.append(fila);
    });
}
function editarUsuario(idUsuario) {
    $.ajax({
        url: '../Servidor/PHP/usuarios.php', // Cambia esta ruta
        method: 'GET',
        data: { numFuncion: '5', id: idUsuario },
        success: function(response) {
            try {
                const data = JSON.parse(response);

                if (data.success) {
                    // Si la respuesta es exitosa, mostramos los datos en el modal
                    $('#usuario').val(data.data.usuario);
                    $('#nombreUsuario').val(data.data.nombre);
                    $('#apellidosUsuario').val(data.data.apellido);
                    $('#correoUsuario').val(data.data.correo);
                    $('#contrasenaUsuario').val(data.data.password);
                    $('#rolUsuario').val(data.data.tipoUsuario);
                    $('#telefonoUsuario').val(data.data.telefono);
                    $('#estatusUsuario').val(data.data.estatus);

                    // Mostrar el modal
                    $('#usuarioModal').modal('show');
                } else {
                    alert(data.message); // En caso de error
                }
            } catch (e) {
                alert("Error al parsear la respuesta: " + e.message);
            }
        },
        error: function() {
            alert("Hubo un problema al obtener los datos del usuario.");
        }
    });
}
function cargarEmpresas() {
    // Realiza una solicitud GET al servidor para obtener todas las empresas
    $.get('../Servidor/PHP/usuarios.php', { action: 'get', numFuncion: '4' }, function (response) {
        // Verificar si la respuesta es exitosa y contiene datos
        if (response.success && response.data) {
            const empresas = response.data;
            const empresaSelect = document.getElementById('selectEmpresa'); // Select donde se llenarán las empresas
            empresaSelect.innerHTML = '<option selected disabled>Selecciona una empresa</option>'; // Opción inicial

            // Iterar sobre las empresas y agregarlas al select
            empresas.forEach((empresa) => {
                const option = document.createElement('option');
                option.value = empresa.id; // Valor de la opción
                option.textContent = `${empresa.noEmpresa} - ${empresa.razonSocial}`; // Texto visible en el select

                // Agregar atributos data-* para detalles adicionales
                option.setAttribute('data-razon-social', empresa.razonSocial);
                option.setAttribute('data-id', empresa.id);
                option.setAttribute('data-numero', empresa.noEmpresa);

                empresaSelect.appendChild(option); // Agregar la opción al select
            });
        } else {
            // Mostrar mensaje de error si no se obtuvieron empresas
            alert(response.message || 'Error al obtener las empresas.');
        }
    }, 'json');
}

function limpiarFormulario() {
    $('#idUsuario').val('');
    $('#usuario').val('');
    $('#nombreUsuario').val('');
    $('#apellidosUsuario').val('');
    $('#correoUsuario').val('');
    $('#contrasenaUsuario').val('');
    $('#telefonoUsuario').val('');
    $('#rolUsuario').val('');  // Si es un select, también se debe resetear
    $('#selectEmpresa').val('');  // Si es un select, también se debe resetear
    $('#detallesEmpresa').val('');  // Limpiar el textarea
}
// Función para abrir el modal
document.getElementById('btnAgregar').addEventListener('click', () => {
    limpiarFormulario();
    // Usar las funciones de Bootstrap para abrir el modal
    $('#usuarioModal').modal('show');
    // Eliminar el aria-hidden cuando se muestra el modal
    $('#usuarioModal').removeAttr('aria-hidden');
    // Añadir el atributo inert al fondo para evitar que los elementos detrás sean interactivos
    $('.modal-backdrop').attr('inert', true);
    cargarEmpresas();
});

// Función para cerrar el modal cuando se haga clic en el botón de cerrar
document.getElementById('cerrarModal').addEventListener('click', () => {
    limpiarFormulario();
    // Usar las funciones de Bootstrap para cerrar el modal
    $('#usuarioModal').modal('hide');
    // Restaurar el aria-hidden al cerrar el modal
    $('#usuarioModal').attr('aria-hidden', 'true');
    // Eliminar el atributo inert del fondo al cerrar
    $('.modal-backdrop').removeAttr('inert');
});

// Función para agregar una empresa seleccionada
document.getElementById('agregarEmpresaBtn').addEventListener('click', () => {
    const selectedEmpresa = document.getElementById('selectEmpresa').value;
    // Lógica para agregar la empresa al usuario o realizar alguna acción
    // Por ejemplo, puedes mostrar un mensaje o hacer una solicitud al servidor.
    console.log('Empresa seleccionada:', selectedEmpresa);
});

$(document).ready(function () {
    /*$('#agregarEmpresaBtn').on('click', function () {
        const selectedOption = $('#selectEmpresa').find('option:selected'); // Obtener la opción seleccionada
        // Verificar que se haya seleccionado una empresa válida
        if (selectedOption.val() === null || selectedOption.val() === "Selecciona una empresa") {
            alert('Por favor, selecciona una empresa antes de agregar.');
            return;
        }

        // Extraer los atributos de la opción seleccionada
        const razonSocial = selectedOption.data('razon-social');
        const idEmpresa = selectedOption.data('id');
        const numeroEmpresa = selectedOption.data('numero');

        // Verificar si la empresa ya fue agregada
        const existe = $(`#tablaEmpresas tbody tr[data-id="${idEmpresa}"]`).length > 0;
        if (existe) {
            alert('Esta empresa ya ha sido agregada.');
            return;
        }

        // Crear una nueva fila con los datos de la empresa
        const nuevaFila = `
            <tr data-id="${idEmpresa}">
                <td>${idEmpresa}</td>
                <td>${razonSocial}</td>
                <td>${numeroEmpresa}</td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm eliminarEmpresaBtn">Eliminar</button>
                </td>
            </tr>
        `;
        // Agregar la fila a la tabla
        $('#tablaEmpresas tbody').append(nuevaFila);
    });*/

    // Detectar clic en los botones "Eliminar" dentro de la tabla
    $('#tablaEmpresas').on('click', '.eliminarEmpresaBtn', function () {
        // Eliminar la fila correspondiente
        $(this).closest('tr').remove();
    });

    /*$('#guardarDatosBtn').on('click', function () {
        const empresasSeleccionadas = [];
        // Obtener todas las empresas seleccionadas de la tabla
        $('#tablaEmpresas tbody tr').each(function () {
            const idEmpresa = $(this).find('td:nth-child(1)').text();
            const razonSocial = $(this).find('td:nth-child(2)').text();
            const numeroEmpresa = $(this).find('td:nth-child(3)').text();

            empresasSeleccionadas.push({
                id: idEmpresa,
                empresa: razonSocial,
                noEmpresa: numeroEmpresa,
            });
        });
        // Validar que haya datos para guardar
        if (empresasSeleccionadas.length === 0) {
            alert('No hay empresas seleccionadas para guardar.');
            return;
        }
        // Enviar los datos al servidor mediante AJAX
        $.ajax({
            url: '../Servidor/PHP/usuarios.php',
            type: 'POST',
            data: {
                numFuncion: 1, // Función para guardar
                empresas: JSON.stringify(empresasSeleccionadas),
                datosUsuario: JSON.stringify(empresasSeleccionadas),
            },
            success: function (response) {
                const res = JSON.parse(response);
                if (res.success) {
                    alert('Datos guardados exitosamente.');
                    // Opcional: Limpiar tabla después de guardar
                    $('#tablaEmpresas tbody').empty();
                } else {
                    alert(res.message || 'Error al guardar los datos.');
                }
            },
            error: function () {
                alert('Error al realizar la solicitud.');
            },
        });
    });*/
});