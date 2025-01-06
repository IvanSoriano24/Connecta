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
                    $('#usuario').text(data.data.usuario);
                    $('#nombreUsuario').text(data.data.nombre);
                    $('#apellidosUsuario').text(data.data.apellido);
                    $('#correoUsuario').text(data.data.correo);
                    $('#contrasenaUsuario').text(data.data.password);
                    $('#rolUsuario').text(data.data.tipoUsuario);
                    $('#telefonoUsuario').text(data.data.telefono);
                    $('#estatusUsuario').text(data.data.estatus);

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
                empresaSelect.appendChild(option); // Agregar la opción al select
            });

        } else {
            // Mostrar mensaje de error si no se obtuvieron empresas
            alert(response.message || 'Error al obtener las empresas.');
        }
    }, 'json');
}
// Función para abrir el modal
document.getElementById('btnAgregar').addEventListener('click', () => {
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

