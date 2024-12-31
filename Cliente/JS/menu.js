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
    console.log('Enviando datos al servidor...');
    $.post('../Servidor/PHP/empresas.php', { 
        action: 'sesion',
        ed: '2' // Asegúrate de que este valor sea '2'
    }, function(response) {
        console.log('Respuesta del servidor:', response); // Verifica la respuesta en la consola
        if (response.success && response.data) {
            console.log('Datos de la empresa:', response.data);
            $('#noEmpresa').val(response.data.noEmpresa);
            $('#razonSocial').val(response.data.razonSocial);
            $('#rfc').val(response.data.rfc);
            $('#regimenFiscal').val(response.data.regimenFiscal);
            $('#numExterior').val(response.data.numExterior);
            $('#numInterior').val(response.data.numInterior);
            $('#entreCalle').val(response.data.entreCalle);
            $('#yCalle').val(response.data.yCalle);
            $('#colonia').val(response.data.colonia);
            $('#referencia').val(response.data.referencia);
            $('#pais').val(response.data.pais);
            $('#estado').val(response.data.estado);
            $('#municipio').val(response.data.municipio);
            $('#cp').val(response.data.cp);
            $('#poblacion').val(response.data.poblacion);
            $('#claveCiec').val(response.data.claveCiec);
        } else {
            console.warn('Error:', response.message || 'Error al obtener las empresas.');
            alert(response.message || 'Error al obtener las empresas.');
        }
    }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
        console.error('Error en la petición:', textStatus, errorThrown);
    });
}

function mostrarMenu(){
    document.getElementById("divContenedor").style.display = "block";
}

// Función para cargar los datos de la empresa
function cargarEmpresa(usuario) {
    // Realiza una solicitud GET al servidor para obtener las empresas
    $.get('../Servidor/PHP/empresas.php', { action: 'get', usuario: usuario }, function (response) {
        ///
        if (response.success && response.data) {
            const empresas = response.data;
            const empresaSelect = document.getElementById('empresaSelect');
            empresaSelect.innerHTML = '<option selected disabled>Selecciona una empresa</option>';
            empresas.forEach((empresa) => {
                console.log(empresas);
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
    ///
}




// Función para guardar o actualizar la empresa
function guardarEmpresa() {
    const data = {
        action: 'save',
        noEmpresa: $('#noEmpresa').val(),
        razonSocial: $('#razonSocial').val(),
        rfc: $('#rfc').val(),
        regimenFiscal: $('#regimenFiscal').val(),
        calle: $('#calle').val(),
        numExterior: $('#numExterior').val(),
        numInterior: $('#numInterior').val(),
        entreCalle: $('#entreCalle').val(),
        colonia: $('#colonia').val(),
        referencia: $('#referencia').val(),
        pais: $('#pais').val(),
        estado: $('#estado').val(),
        municipio: $('#municipio').val(),
        codigoPostal: $('#codigoPostal').val(),
        poblacion: $('#poblacion').val()
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

function sesionEmpresa(idEmpresarial) {
    var id = idEmpresarial.id;
    var noEmpresa = idEmpresarial.noEmpresa;
    var razonSocial = idEmpresarial.razonSocial;
    
  /*  console.log('Datos que se enviarán:', { 
        action: 'sesion', 
        id: id, 
        noEmpresa: noEmpresa,
        razonSocial: razonSocial
    });  // Verificar los datos que se envían
*/
$.post('../Servidor/PHP/empresas.php', { 
    action: 'sesion', 
    id: id,  
    noEmpresa: noEmpresa,
    razonSocial: razonSocial
}, function(response) {
    console.log('Respuesta del servidor:', response); // Ver respuesta del servidor

    if (response.success) {
        if (response.data && response.data.id && response.data.noEmpresa && response.data.razonSocial) {
            console.log('Datos recibidos correctamente:', response.data);
        } else {
            alert('La respuesta no contiene datos de la empresa esperados.');
        }
    } else {
        //alert(response.message || 'Error al guardar la sesión de empresa.');
    }
    
}).fail(function(jqXHR, textStatus, errorThrown) {
    console.log("Error en la solicitud: " + textStatus + ", " + errorThrown);
    alert('Error al comunicar con el servidor.');
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
    $('#informaEmpresa').click(function () {
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
