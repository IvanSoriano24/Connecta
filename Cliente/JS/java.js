

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
document.getElementById('checkConnection').addEventListener('click', () => {
    database.ref('.info/connected').on('value', (snapshot) => {
        if (snapshot.val() === true) {
            alert('Conexión exitosa a Firebase');
        } else {
            alert('No conectado a Firebase');
        }
    });
});
/*
document.addEventListener("DOMContentLoaded", () => {
const recuperarModal = createModal("Recuperar Contraseña", `
    <form id="recuperarForm">
        <label for="emailRecuperar">Correo:</label>
        <input type="email" id="emailRecuperar" name="emailRecuperar">
        <span class="error" id="recuperarError"></span>

        <button type="submit" id="recuperarSubmit">Recuperar</button>
    </form>
`);


document.getElementById("recuperarContrasena").addEventListener("click", () => {
    document.body.appendChild(recuperarModal);
});

document.addEventListener("click", (e) => {
    if (e.target.classList.contains("close-modal")) {
        e.target.closest(".modal").remove();
    }
});

document.addEventListener("submit", (e) => {
    e.preventDefault();
    const form = e.target;
    let isValid = true;

    if (form.id === "recuperarForm") {
        isValid = validateFields([
            { id: "emailRecuperar", errorId: "recuperarError", message: "El correo es obligatorio" },
        ]);
    }

    if (isValid) {
        alert("Formulario enviado correctamente");
        form.closest(".modal").remove();
    }
});

function createModal(title, content) {
    const modal = document.createElement("div");
    modal.className = "modal";
    modal.innerHTML = `
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>${title}</h2>
            ${content}
        </div>
    `;
    return modal;
}

function validateFields(fields) {
    let isValid = true;
    fields.forEach(({ id, errorId, message }) => {
        const field = document.getElementById(id);
        const error = document.getElementById(errorId);
        if (field.value.trim() === "") {
            error.textContent = message;
            isValid = false;
        } else {
            error.textContent = "";
        }
    });
    return isValid;
}
});
*/

document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("form");
    const usuario = document.getElementById("usuario");
    const password = document.getElementById("password");

    // Verifica si hay un parámetro 'error' en la URL
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    
    if (error) {
        // Mostrar el mensaje de error como un alert
        alert("Correo o contraseña incorrectos.");
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
            alert("Por favor, llena todos los campos.");
        }
    });

    function mostrarError(input, mensaje) {
        const error = document.createElement("div");
        error.className = "error";
        error.textContent = mensaje;
        input.parentElement.appendChild(error);
    }
});

