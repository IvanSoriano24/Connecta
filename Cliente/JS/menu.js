let idEmpresarial;

function cerrarModal(){
    const modal = bootstrap.Modal.getInstance(document.getElementById('infoEmpresa'));
        modal.hide();
}
function cerrarModalSae(){
    const modal = bootstrap.Modal.getInstance(document.getElementById('infoConexion'));
        modal.hide();
}
function informaEmpresa() {
    /*$.get('../Servidor/PHP/e.php', { action: 'obtenerEmpresa' }, function (response) {
        if (response.success && response.data) {
            const empresa = response.data;
            $('#id').val(empresa.id);
            $('#noEmpresa').val(empresa.noEmpresa);
            $('#razonSocial').val(empresa.razonSocial);
        } else {
            alert(response.message || 'Error al obtener los datos de la empresa.');
        }
    }, 'json');*/
        $('#id').val(idEmpresarial.id);
        $('#noEmpresa').val(idEmpresarial.noEmpresa);
        $('#razonSocial').val(idEmpresarial.razonSocial);
/*
    $.get('../Servidor/PHP/empresas.php', { action: 'obtenerEmpresa' }, function (response) {
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
    }, 'json');*/
}
function mostrarMenu(){
    document.getElementById("divContenedor").style.display = "block";
}

// Función para cargar los datos de la empresa
function cargarEmpresa(usuario) {
    // Realiza una solicitud GET al servidor para obtener las empresas
    $.get('../Servidor/PHP/empresas.php', { action: 'get', usuario: usuario }, function (response) {
        if (response.success && response.data) {
            const empresas = response.data;
            console.log(empresas);
            const empresaSelect = document.getElementById('empresaSelect');
            empresaSelect.innerHTML = '<option selected disabled>Selecciona una empresa</option>';
            empresas.forEach((empresa) => {
                const option = document.createElement('option');
                option.value = empresa.id;
                option.textContent = `${empresa.noEmpresa} - ${empresa.razonSocial}`;

                option.setAttribute('data-no-empresa', empresa.noEmpresa);
                option.setAttribute('data-razon-social', empresa.razonSocial);

                empresaSelect.appendChild(option);
            });

        } else {
            alert(response.message || 'Error al obtener las empresas.');
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

function probarConexionSAE() {
    const data = {
        action: 'probar',
        host: $('#host').val(),
        usuarioSae: $('#usuarioSae').val(),
        password: $('#password').val(),
        nombreBase: $('#nombreBase').val()
    };
    fetch('../Servidor/PHP/sae.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        return response.json();
    })
    .then(responseData => {
        console.log("Respuesta del servidor:", responseData);
        if (responseData.success) {
            alert('Conexión exitosa.');
        } else {
            alert('Error: ' + responseData.message);
        }
    })
    .catch(error => {
        console.error("Error de la solicitud:", error);
        alert('Error en la solicitud: ' + error.message);
    });
}
function guardarConexionSAE() {
    const data = {
        action: 'guardar',
        host: $('#host').val(),
        usuarioSae: $('#usuarioSae').val(),
        password: $('#password').val(),
        nombreBase: $('#nombreBase').val(),
        noEmpresa: $(idEmpresarial.noEmpresa) // Este campo debe ser incluido en el formulario
    };
    fetch('../Servidor/PHP/sae.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        return response.json();
    })
    .then(responseData => {
        console.log("Respuesta del servidor:", responseData);
        if (responseData.success) {
            alert('Datos guardados exitosamente en Firebase.');
        } else {
            alert('Error al guardar: ' + responseData.message);
        }
    })
    .catch(error => {
        console.error("Error de la solicitud:", error);
        alert('Error en la solicitud: ' + error.message);
    });
}


$(document).ready(function () {
/*
    document.getElementById('SAE').addEventListener('click', function() {
        const conexionModal = new bootstrap.Modal(document.getElementById('infoConexion'));
        conexionModal.show();
      });
    document.getElementById('Empresa').addEventListener('click', function() {
        const empresaModal = new bootstrap.Modal(document.getElementById('infoEmpresa'));
        empresaModal.show();
    });*/
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

    $('#confirmarConexion').click(function () {
        guardarConexionSAE();
    });
    $('#probarConexion').click(function () {
        probarConexionSAE();
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
