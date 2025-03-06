document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("form");
    const usuario = document.getElementById("usuario");
    const password = document.getElementById("password");

    // Verifica si hay un parámetro 'error' en la URL
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    
    if (error) {
        let message = "Correo o contraseña incorrectos."; // Mensaje predeterminado
        if (error === "2") {
            message = "Tu cuenta está bloqueada. Por favor, contacta al administrador.";
        } else if (error === "3") {
            message = "Tu cuenta está dada de baja. Por favor, contacta al administrador.";
        } else if (error === "4") {
            message = "No hay conexion al sistema.";
        }

        // Mostrar el mensaje de error con SweetAlert2
        Swal.fire({
            icon: "error",
            title: "Error",
            text: message,
            confirmButtonColor: "#3085d6"
        });
    }

    form.addEventListener("submit", (event) => {
        event.preventDefault(); // Prevenir el envío del formulario
        let valid = true;

        // Limpiar mensajes de error previos
        document.querySelectorAll(".error").forEach(e => e.remove());

        // Validar campo usuario
        if (usuario.value.trim() === "") {
            valid = false;
            mostrarError(usuario, "El campo Usuario no puede estar vacío.");
        }

        // Validar campo contraseña
        if (password.value.trim() === "") {
            valid = false;
            mostrarError(password, "El campo Contraseña no puede estar vacío.");
        }

        if (valid) {
            form.submit(); // Enviar formulario si todo está válido
        } else {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: "Algo salió mal. Por favor, revisa los campos e inténtalo de nuevo.",
                confirmButtonColor: "#3085d6"
            });
        }
    });

    function mostrarError(input, mensaje) {
        const error = document.createElement("div");
        error.className = "error";
        error.textContent = mensaje;
        input.parentElement.appendChild(error);
    }
});

