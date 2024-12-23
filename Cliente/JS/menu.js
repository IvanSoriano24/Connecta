function cerrarModal(){
    document.getElementById("modalEmpresas").style.display = "none";
    mostrarMenu();

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
    $.post('../../Servidor/PHP/empresas.php', data, function (response) {
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
    // Mostrar el formulario y cargar los datos de la empresa


    // Guardar o actualizar empresa
    $('#guardarEmpresa').click(function () {
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
