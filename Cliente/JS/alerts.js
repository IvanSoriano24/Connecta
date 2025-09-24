// Alerta solo con botón de "Aceptar"
async function mostrarAlerta(titulo, mensaje, icono = 'info') {
    return await Swal.fire({
        title: titulo,
        text: mensaje,
        icon: icono,
        confirmButtonColor: '#6A58DD',
        confirmButtonText: 'Aceptar'
    });
}

// Alerta con botón de "Aceptar" y "Cancelar"
async function mostrarAceptarCancelar(titulo, mensaje, icono = 'info', botonConfirmar = 'Aceptar', colorConfirmar = '#6A58DD') {
    return await Swal.fire({
        title: titulo,
        text: mensaje,
        icon: icono,
        reverseButtons: true,
        showCancelButton: true,
        cancelButtonColor: '#888',
        confirmButtonColor: colorConfirmar,
        cancelButtonText: 'Cancelar',
        confirmButtonText: botonConfirmar
    });
}

// Mostrar loader
function mostrarLoader(titulo = "Cargando...", mensaje = "Por favor espera") {
    Swal.fire({
        title: titulo,
        text: mensaje,
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

// Cerrar loader
function cerrarLoader() {
    Swal.close();
}