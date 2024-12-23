function cerrarModal(){
    document.getElementById("modalEmpresas").style.display = "none";
    mostrarMenu();

}

function mostrarMenu(){
    document.getElementById("divContenedor").style.display = "block";
}


// Función para cargar los datos de la empresa
function cargarEmpresa() {
    $.get('../../Servidor/PHP/e.php', { action: 'get' }, function (response) {
        if (response.success && response.data) {
            const empresas = response.data;
            if (empresas.length > 0) {
                
                const empresa = empresas[0];
                $('#id').val(empresa.id);
                $('#noEmpresa').val(empresa.noEmpresa);
                $('#razonSocial').val(empresa.razonSocial);
            } else {
                alert('No hay datos de la empresa, puedes crear una nueva.');
            }
        } else {
            alert(response.message || 'Error al obtener los datos de la empresa.');
        }
    }, 'json');
}

// Función para guardar o actualizar la empresa
function guardarEmpresa() {
    const data = {
        action: 'save',
        id: $('#id').val(),
        noEmpresa: $('#noEmpresa').val(),
        razonSocial: $('#razonSocial').val()
    };
    $.post('../../Servidor/PHP/e.php', data, function (response) {
        if (response.success) {
            alert('Empresa guardada correctamente.');
        } else {
            alert('Error al guardar la empresa.');
        }
    }, 'json');
}

// Función para eliminar la empresa
function eliminarEmpresa() {
    if (confirm('¿Estás seguro de que deseas eliminar la empresa?')) {
        $.post('../../Servidor/PHP/e.php', { action: 'delete' }, function (response) {
            if (response.success) {
                alert('Empresa eliminada correctamente.');
                $('#empresaForm')[0].reset();
            } else {
                alert('Error al eliminar la empresa.');
            }
        }, 'json');
    }
}

$(document).ready(function () {
    // Mostrar el formulario y cargar los datos de la empresa
    $('#empresaModal').click(function () {
        $('#formularioEmpresa').show();
        cargarEmpresa(); // Llamar a la función que obtiene los datos
    });

    // Guardar o actualizar empresa
    $('#guardarEmpresa').click(function () {
        guardarEmpresa();
    });

    // Eliminar empresa
    $('#eliminarEmpresa').click(function () {
        eliminarEmpresa();
    });
});
