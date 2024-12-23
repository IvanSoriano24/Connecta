
function cerrarModal(){
    const modal = bootstrap.Modal.getInstance(document.getElementById('infoEmpresa'));
        modal.hide();
}
function cerrarModalSae(){
    const modal = bootstrap.Modal.getInstance(document.getElementById('infoConexion'));
        modal.hide();
}

function informaEmpresa(){
    $.get('../Servidor/PHP/e.php', { action: 'get' }, function (response) {
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
function mostrarMenu(){
    document.getElementById("divContenedor").style.display = "block";
}

// Función para cargar los datos de la empresa
function cargarEmpresa() {
    // Realiza una solicitud GET al servidor para obtener las empresas
    $.get('../Servidor/PHP/empresas.php', { action: 'get' }, function (response) {
        if (response.success && response.data) {
            const empresas = response.data;
            const empresaSelect = document.getElementById('empresaSelect');
            empresaSelect.innerHTML = '<option selected disabled>Selecciona una empresa</option>';
            empresas.forEach((empresa) => {
                const option = document.createElement('option');
                option.value = empresa.id;
                option.textContent = `${empresa.noEmpresa} - ${empresa.razonSocial}`;
                empresaSelect.appendChild(option);
            });
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
    $.post('../Servidor/PHP/empresas.php', data, function (response) {
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
        $.post('../../Servidor/PHP/empresas.php', { action: 'delete' }, function (response) {
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
    document.getElementById('SAE').addEventListener('click', function() {
        const conexionModal = new bootstrap.Modal(document.getElementById('infoConexion'));
        conexionModal.show();
      });
    document.getElementById('Empresa').addEventListener('click', function() {
        const empresaModal = new bootstrap.Modal(document.getElementById('infoEmpresa'));
        empresaModal.show();
    });
    $('#Empresa').click(function () {
        informaEmpresa(); // Llamar a la función que obtiene los datos
    });
    $('#cancelarModal').click(function () {
        cerrarModal();
    });
    $('#cancelarModalSae').click(function () {
        cerrarModalSae();
    });
    
    // Guardar o actualizar empresa
    $('#confirmarDatos').click(function () {
        guardarEmpresa();
    });

    // Eliminar empresa
    $('#eliminarEmpresa').click(function () {
        eliminarEmpresa();
    });

    $("#cerrarSesion").click(function () {
        if (confirm("¿Estás seguro de que quieres cerrar sesión?")) {
          $.post("../Servidor/PHP/conexion.php", { numFuncion: 2 }, function (data) {
            window.location.href = "index.php"; // Redirigir al login
          }).fail(function () {
            alert("Error al intentar cerrar sesión.");
          });
        }
      });
});
