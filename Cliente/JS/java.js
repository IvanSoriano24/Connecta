src="https://cdn.jsdelivr.net/npm/sweetalert2@11"

    // Configuración de Firebase
    const firebaseConfig = {
        apiKey: "AIzaSyCh8BFeIi4JcAAe-aW8Z2odIqdytw-wnDA",
        authDomain: "mdconnecta-4aeb4.firebaseapp.com",
        databaseURL: "https://mdconnecta-4aeb4-default-rtdb.firebaseio.com",
        projectId: "mdconnecta-4aeb4",
        storageBucket: "mdconnecta-4aeb4.appspot.com",
        messagingSenderId: "134553407299",
        appId: "1:134553407299:web:1b1b3fc6294a3695e3a9f6",
        measurementId: "G-8X256NJ6J6"
    };

    // Inicializar Firebase
    firebase.initializeApp(firebaseConfig);
    const database = firebase.database();

// Función para verificar la conexión
document.getElementById('checkConnection')?.addEventListener('click', () => {
    database.ref('.info/connected').on('value', (snapshot) => {
        if (snapshot.val() === true) {
            alert('Conexión exitosa a Firebase');
        } else {
            alert('No conectado a Firebase');
        }
    });
});
document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("form");
    const usuario = document.getElementById("usuario");
    const password = document.getElementById("password");

    // Verifica si hay un parámetro 'error' en la URL
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    
    if (error) {
        // Mostrar el mensaje de error como un alert
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "Correo o contraseña incorrectos.",
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

