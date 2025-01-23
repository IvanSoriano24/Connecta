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
    var tablaClientes = $('#tablaUsuarios'); // Selección del cuerpo de la tabla
    tablaClientes.empty(); // Limpieza de los datos previos en la tabla

    // Ordenar los usuarios alfabéticamente por nombreCompleto
    usuarios.sort(function(a, b) {
        var nombreA = (a.nombreCompleto || '').toUpperCase(); // Manejar valores nulos o indefinidos
        var nombreB = (b.nombreCompleto || '').toUpperCase();
        return nombreA.localeCompare(nombreB); // Comparación estándar de cadenas
    });

    // Crear filas para cada usuario
    usuarios.forEach(function(usuario) {
        // Generar fila asegurando que todas las celdas tengan contenido
        var fila = $('<tr>'); // Crear el elemento <tr>
        fila.append($('<td>').text(usuario.nombreCompleto || '-')); // Agregar columna de nombre
        fila.append($('<td>').text(usuario.correo || '-'));         // Agregar columna de correo
        fila.append($('<td>').text(usuario.estatus || '-'));        // Agregar columna de estatus
        fila.append($('<td>').text(usuario.rol || '-'));            // Agregar columna de rol

        // Botón Editar con seguridad en el manejo del ID
        var botonEditar = $('<button>')
            .addClass('btn btn-info btn-sm') // Añadir clases CSS
            .text('Editar')                  // Texto del botón
            .attr('onclick', 'editarUsuario("' + usuario.id + '")'); // Atributo onclick

        // Añadir botón a la última celda
        fila.append($('<td>').append(botonEditar));

        // Añadir la fila completa a la tabla
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
                    $('#idUsuario').val(idUsuario);
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

    $('#guardarDatosBtn').on('click', function (e) {
        e.preventDefault(); // Evitar que el formulario recargue la página
        // Obtener los valores del formulario
        const idUsuario = $('#idUsuario').val();
        const usuario = $('#usuario').val();
        const nombreUsuario = $('#nombreUsuario').val();
        const apellidosUsuario = $('#apellidosUsuario').val();
        const correoUsuario = $('#correoUsuario').val();
        const contrasenaUsuario = $('#contrasenaUsuario').val();
        const telefonoUsuario = $('#telefonoUsuario').val();
        const rolUsuario = $('#rolUsuario').val();
    
        // Validar que todos los campos requeridos estén completos
        if (
            !usuario ||
            !nombreUsuario ||
            !apellidosUsuario ||
            !correoUsuario ||
            !contrasenaUsuario ||
            !telefonoUsuario ||
            !rolUsuario
        ) {
            alert('Por favor, complete todos los campos.');
            return;
        }
        // Enviar los datos al servidor mediante AJAX
        $.ajax({
            url: '../Servidor/PHP/usuarios.php',
            type: 'POST',
            data: {
                numFuncion: 1, // Identificador para la función en el servidor
                idUsuario: idUsuario,
                usuario: usuario,
                nombreUsuario: nombreUsuario,
                apellidosUsuario: apellidosUsuario,
                correoUsuario: correoUsuario,
                contrasenaUsuario: contrasenaUsuario,
                telefonoUsuario: telefonoUsuario,
                rolUsuario: rolUsuario,
            },
            success: function (response) {
                const res = JSON.parse(response);
                if (res.success) {
                    alert('Usuario guardado exitosamente.');
    
                    // Cerrar el modal y limpiar el formulario
                    $('#usuarioModal').modal('hide');
                    $('#agregarUsuarioForm')[0].reset();

                    // Recargar la tabla de usuarios (llama a tu función para mostrar usuarios)
                    location.reload();
                } else {
                    alert(res.message || 'Error al guardar el usuario.');
                }
            },
            error: function () {
                alert('Error al realizar la solicitud.');
            },
        });
    });    
});