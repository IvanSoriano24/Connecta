let idEmpresarial;
 
function cerrarModal(){
    const modal = bootstrap.Modal.getInstance(document.getElementById('infoEmpresa'));
    modal.hide();
}
function cerrarModalSae() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('infoConexion'));
    modal.hide();
}
function informaEmpresa() {
    const noEmpresa = sessionStorage.getItem('noEmpresaSeleccionada');
    $.post('../Servidor/PHP/empresas.php', {
        action: 'sesion',
        ed: '2',
        noEmpresa: noEmpresa
    }, function(response) {
        if (response.success && response.data) {
            const data = response.data;
            
            // Verifica la estructura de los datos en el console.log
            //console.log(data);  // Esto te mostrará el objeto completo
            $('#noEmpresa').val(data.noEmpresa);
            $('#razonSocial').val(data.razonSocial);
            $('#rfc').val(data.rfc);
            $('#regimenFiscal').val(data.regimenFiscal);
            $('#calle').val(data.calle);
            $('#numExterior').val(data.numExterior);
            $('#numInterior').val(data.numInterior);
            $('#entreCalle').val(data.entreCalle);
            $('#colonia').val(data.colonia);
            $('#referencia').val(data.referencia);
            $('#pais').val(data.pais);
            $('#estado').val(data.estado);
            $('#municipio').val(data.municipio);
            $('#codigoPostal').val(data.codigoPostal);
            $('#poblacion').val(data.poblacion);
        } else {
            console.warn('Error:', response.message || 'Error al obtener las empresas.');
            alert(response.message || 'Error al obtener las empresas.');
        }
    }, 'json').fail(function (jqXHR, textStatus, errorThrown) {
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

        if (response.success && response.data) {
            const empresas = response.data;
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
function seleccionarEmpresa(noEmpresa) {
    // Guarda el número de empresa en sessionStorage
    sessionStorage.setItem('noEmpresaSeleccionada', noEmpresa);

    // Opcionalmente, puedes mostrar una alerta de confirmación
    console.log('Empresa seleccionada:', noEmpresa);
}
 
// Función para guardar o actualizar la empresa
function guardarEmpresa() {
    if (!validateForm()) {
        return; // Si la validación falla, no se envía el formulario
    }
    const data = {
        action: 'save',
        id:  $('#id').val(),
        noEmpresa: $('#noEmpresa').val(), // Aquí se manda el noEmpresa
        razonSocial: $('#razonSocial').val(),
        rfc: $('#rfc').val(),
        regimenFiscal: $('#regimenFiscal').val(),
        calle: $('#calle').val(),
        numExterior: $('#numExterior').val(),
        numInterior: $('#numInterior').val() || '*',
        entreCalle: $('#entreCalle').val() || '*',
        colonia: $('#colonia').val(),
        referencia: $('#referencia').val(),
        pais: $('#pais').val(),
        estado: $('#estado').val(),
        municipio: $('#municipio').val(),
        codigoPostal: $('#codigoPostal').val(),
        poblacion: $('#poblacion').val()
    };
    $.ajax({
        url: '../Servidor/PHP/empresas.php',
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                alert('Documento actualizado correctamente.');
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function (xhr, status, error) {
            console.error('Error:', error);
            alert('Error al conectar con el servidor.');
        }
    });
}

 // Función para validar campos vacíos
 function validateForm() {
    const fields = [
        'rfc', 'calle', 'numExterior', 
        'colonia', 'referencia', 'pais', 
        'estado', 'municipio', 'codigoPostal', 'poblacion'
    ];
    
    let isValid = true;
    
    for (let field of fields) {
        const input = document.getElementById(field);
        if (input && input.value.trim() === '') {
            input.classList.add('input-error');
            input.placeholder = `Campo Obligatorio`;
            isValid = false;
        } else {
            input.classList.remove('input-error');
            input.placeholder = '';
        }
    }
    
    return isValid;
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
    if (!validateForm2()) {
        return; // Si la validación falla, no se envía el formulario
    }
    const data = {
        action: 'probar',
        host: $('#host').val(),
        puerto: $('#usuarioSae').val(),
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
    const noEmpresa = sessionStorage.getItem('noEmpresaSeleccionada');
    const data = {
        action: 'guardar',
        idDocumento: $('#idDocumento').val(),
        host: $('#host').val(),
        puerto: $('#puerto').val(),
        usuarioSae: $('#usuarioSae').val(),
        password: $('#password').val(),
        nombreBase: $('#nombreBase').val(),
        noEmpresa: noEmpresa
    };
    $.ajax({
        url: '../Servidor/PHP/sae.php',
        type: 'POST',
        data: JSON.stringify(data),
        contentType: 'application/json',
        dataType: 'json',
        success: function (response) {
            console.log('Respuesta del servidor:', response); // Verifica lo que devuelve el servidor
            if (response.success) {
                alert('Conexión actualizada correctamente.');
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function (xhr, status, error) {
            console.error('Error:', xhr.responseText); // Mostrar respuesta completa para debug
            alert('Error al conectar con el servidor: ' + error);
        }
    });
    alert("Realizando Cambios");
}
function informaSae(){
    const noEmpresa = sessionStorage.getItem('noEmpresaSeleccionada');
    const data = {
        action: 'mostrar',
        noEmpresa: noEmpresa
    };
    $.ajax({
        url: '../Servidor/PHP/sae.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                const data = response.data;
                console.log(data.id);
                $('#idDocumento').val(data.id);
                $('#host').val(data.host);
                $('#puerto').val(data.puerto);
                $('#usuarioSae').val(data.usuarioSae);
                $('#nombreBase').val(data.nombreBase);
            } else {
                console.warn('Error:', response.message || 'Error al obtener la conexión.');
                alert(response.message || 'Error al obtener la conexión.');
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('Error en la petición:', textStatus, errorThrown);
        }
    });
}

function sesionEmpresa(idEmpresarial) {
    var id = idEmpresarial.id;
    var noEmpresa = idEmpresarial.noEmpresa;
    var razonSocial = idEmpresarial.razonSocial;
$.post('../Servidor/PHP/empresas.php', {
    action: 'sesion',
    id: id,  
    noEmpresa: noEmpresa,
    razonSocial: razonSocial
}, function(response) {
    if (response.success) {
        if (response.data && response.data.id && response.data.noEmpresa && response.data.razonSocial) {
            console.log(response.data);
        } else {
            //alert(response.message || 'Error al guardar la sesión de empresa.');
        }
    } else {
        //alert(response.message || 'Error al guardar la sesión de empresa.');
    }
   
}).fail(function(jqXHR, textStatus, errorThrown) {
    console.log("Error en la solicitud: " + textStatus + ", " + errorThrown);
    alert('Error al comunicar con el servidor.');
});
}
 
function validateForm2() {
    const fields = [
        'host', 'puerto', 'usuarioSae', 
        'password', 'nombreBase'
    ];
    
    let isValid = true;
    
    for (let field of fields) {
        const input = document.getElementById(field);
        if (input && input.value.trim() === '') {
            input.classList.add('input-error');
            input.placeholder = `Campo Obligatorio`;
            isValid = false;
        } else {
            input.classList.remove('input-error');
            input.placeholder = '';
        }
    }
    
    return isValid;
}
/*function infoSae() {
    const noEmpresa = sessionStorage.getItem('noEmpresaSeleccionada');
    const data = {
        action: 'mostrar',
        noEmpresa: noEmpresa
    };
    $.ajax({
        url: '../Servidor/PHP/sae.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                const data = response.data;
                console.log(data);  // Esto te mostrará el objeto completo
                $('#host').val(data.host);
                $('#puerto').val(data.puerto);
                $('#usuarioSae').val(data.usuarioSae);
                $('#nombreBase').val(data.nombreBase);
            } else {
                console.warn('Error:', response.message || 'Error al obtener la conexión.');
                alert(response.message || 'Error al obtener la conexión.');
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('Error en la petición:', textStatus, errorThrown);
        }
    });
}*/

function limpiarCacheEmpresa() {
    sessionStorage.removeItem('noEmpresaSeleccionada');
    console.log('Cache de la empresa limpiado.');
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
    /*$('#informaEmpresa').click(function () {
        informaEmpresa(); // Llamar a la función que obtiene los datos
    });*/
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
    /*$('#infoSae').click(function () {
        infoSae();
    });*/
    $('#probarConexion').click(function () {
        probarConexionSAE();
    });
    $('#confirmarConexion').click(function () {
        guardarConexionSAE();
    });
    $("#cerrarSesion").click(function () {
        if (confirm("¿Estás seguro de que quieres cerrar sesión?")) {
            $.post("../Servidor/PHP/conexion.php", { numFuncion: 2 }, function (data) {
                limpiarCacheEmpresa();
                window.location.href = "index.php"; // Redirigir al login
            }).fail(function () {
                alert("Error al intentar cerrar sesión.");
            });
        }
      });
});
