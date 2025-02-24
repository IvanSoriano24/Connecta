// Función para obtener los correos y agregarlos a la tabla
function obtenerCorreosTabla() {
  fetch("../Servidor/PHP/correo.php?numFuncion=1") // Ruta a tu archivo PHP que consulta los correos
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const tablaBody = document.querySelector("#tablaCorreos tbody");
        tablaBody.innerHTML = ""; // Limpiamos la tabla antes de agregar los correos

        data.data.forEach((correo) => {
          const row = document.createElement("tr");

          // Celda para mostrar el correo
          const cellCorreo = document.createElement("td");
          cellCorreo.textContent = correo.correo;
          row.appendChild(cellCorreo);

          // Celda para el botón de Visualizar
          const cellVisualizar = document.createElement("td");
          const btnVisualizar = document.createElement("button");
          btnVisualizar.textContent = "Visualizar";
          btnVisualizar.classList.add("btn", "btn-info");
          btnVisualizar.onclick = () => visualizarCorreo(correo); // Implementa la función visualizarCorreo
          cellVisualizar.appendChild(btnVisualizar);
          row.appendChild(cellVisualizar);

          // Celda para el botón de Editar
          const cellEditar = document.createElement("td");
          const btnEditar = document.createElement("button");
          btnEditar.textContent = "Editar";
          btnEditar.classList.add("btn", "btn-warning");
          btnEditar.onclick = () => editarCorreo(correo); // Implementa la función editarCorreo
          cellEditar.appendChild(btnEditar);
          row.appendChild(cellEditar);

          // Celda para el botón de Eliminar
          const cellEliminar = document.createElement("td");
          const btnEliminar = document.createElement("button");
          btnEliminar.textContent = "Eliminar";
          btnEliminar.classList.add("btn", "btn-danger");
          btnEliminar.onclick = () => eliminarCorreo(correo.id); // Implementa la función eliminarCorreo
          cellEliminar.appendChild(btnEliminar);
          row.appendChild(cellEliminar);

          tablaBody.appendChild(row);
        });
      } else {
        console.error("Error al obtener los correos");
      }
    })
    .catch((error) => console.error("Error de fetch:", error));
}
function visualizarCorreo(correo) {
    // Obtenemos el ID del correo desde el objeto pasado
    const documentId = correo.id;
  
    // Limpiar y reactivar temporalmente los campos del modal (por si se usó antes en edición)
    document.getElementById("correoEditar").value = "";
    document.getElementById("contraseñaEditar").value = "";
    document.getElementById("documentId").value = "";
    document.getElementById("claveUsuarioEditar").value = "";
    document.getElementById("usuarioEditar").value = "";
    document.getElementById("correoEditar").disabled = false;
    document.getElementById("contraseñaEditar").disabled = false;
    document.getElementById("btnGuardarEdicion").style.display = "block";
  
    // Llamada a la función PHP para obtener los datos del correo específico
    fetch(`../Servidor/PHP/correo.php?numFuncion=2&documentId=${documentId}`)
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const correoData = data.data;
          // Precargamos los datos en el formulario de visualización
          document.getElementById("correoEditar").value = correoData.correo;
          document.getElementById("contraseñaEditar").value = correoData.contrasena || "";
          document.getElementById("documentId").value = correoData.id;
          document.getElementById("claveUsuarioEditar").value = correoData.claveUsuario || "";
          document.getElementById("usuarioEditar").value = correoData.usuario || "";
          
          // Deshabilitar los campos para que no sean editables
          document.getElementById("correoEditar").disabled = true;
          document.getElementById("contraseñaEditar").disabled = true;
          
          // Ocultar el botón de "Guardar Cambios"
          document.getElementById("btnGuardarEdicion").style.display = "none";
          
          // Cambiar el título del modal a "Visualizar Correo"
          document.querySelector("#editarModal .modal-title").textContent = "Visualizar Correo";
          
          // Mostrar el modal
          const visualizarModal = new bootstrap.Modal(document.getElementById("editarModal"));
          visualizarModal.show();
        } else {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: data.message || "No se pudo obtener el correo."
          });
        }
      })
      .catch(error => {
        console.error("Error en la solicitud:", error);
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Error en la solicitud al obtener el correo."
        });
      });
  }  
function editarCorreo(correo) {
  const documentId = correo.id;
  // Limpiar los campos del formulario de edición
  document.getElementById("correoEditar").value = "";
  document.getElementById("contraseñaEditar").value = "";
  document.getElementById("documentId").value = "";
  document.getElementById("claveUsuarioEditar").value = "";
  document.getElementById("usuarioEditar").value = "";
  
  // Reactivar los campos y mostrar el botón de guardar
  document.getElementById("contraseñaEditar").disabled = false;
  document.getElementById("btnGuardarEdicion").style.display = "block";
  
  // Cambiar el título del modal a "Editar Correo"
  document.querySelector("#editarModal .modal-title").textContent = "Editar Correo";

  // Llamamos a la función 1 para obtener todos los correos
  // Realizamos la solicitud al endpoint de PHP pasando numFuncion=2 y el documentId como parámetros GET
  fetch(`../Servidor/PHP/correo.php?numFuncion=2&documentId=${documentId}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        // Si se obtiene correctamente el correo, lo mostramos en consola o lo asignamos a los campos que necesites
        const correo = data.data;
        console.log("Correo obtenido:", correo);
        // Por ejemplo, podrías asignar los valores a un formulario de edición:
        document.getElementById("correoEditar").value = correo.correo;
        document.getElementById("contraseñaEditar").value = correo.contrasena;
        document.getElementById("documentId").value = correo.id;
        document.getElementById("claveUsuarioEditar").value =
          correo.claveUsuario;
        document.getElementById("usuarioEditar").value = correo.usuario;
        const editarModal = new bootstrap.Modal(
          document.getElementById("editarModal")
        );
        editarModal.show();
      } else {
        // Si hay un error, se muestra con SweetAlert o en consola
        Swal.fire({
          icon: "error",
          title: "Error",
          text: data.message || "No se pudo obtener el correo.",
        });
      }
    })
    .catch((error) => {
      console.error("Error en la solicitud:", error);
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Error en la solicitud al obtener el correo.",
      });
    });
}
function eliminarCorreo(documentId) {
  Swal.fire({
    title: "¿Estás seguro?",
    text: "Esta acción no se puede revertir.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      // Creamos los parámetros URL-encoded
      const params = new URLSearchParams();
      params.append("numFuncion", "4");
      params.append("documentId", documentId);

      fetch("../Servidor/PHP/correo.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: params.toString(),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            Swal.fire({
              icon: "success",
              title: "Eliminado",
              text: "El correo ha sido eliminado.",
              timer: 2000,
              showConfirmButton: false,
            });
            obtenerCorreosTabla();
          } else {
            Swal.fire({
              icon: "error",
              title: "Error",
              text: "Hubo un error al eliminar el correo.",
            });
          }
        })
        .catch((error) => {
          console.error("Error al eliminar:", error);
          Swal.fire({
            icon: "error",
            title: "Error",
            text: "Error al eliminar el correo.",
          });
        });
    }
  });
}
// Llamamos a la función cuando cargue la página
document.addEventListener("DOMContentLoaded", obtenerCorreosTabla);
// Escuchar el clic en el botón de agregar
document.getElementById("btnAgregar").addEventListener("click", function () {
  // Abrir el modal
  const modalCorreo = new bootstrap.Modal(
    document.getElementById("modalCorreo")
  );
  modalCorreo.show();
});
document
  .getElementById("btnGuardarCorreo")
  .addEventListener("click", function () {
    // Obtener los valores del formulario
    const correo = document.getElementById("correo").value;
    const contraseña = document.getElementById("contraseña").value;
    const claveUsuario = document.getElementById("claveUsuario").value;
    const usuario = document.getElementById("usuario").value;

    // Verificar si los campos no están vacíos
    if (!correo || !contraseña) {
      Swal.fire({
        icon: "warning",
        title: "Campos incompletos",
        text: "Por favor, completa todos los campos.",
      });
      return;
    }

    // Crear parámetros URL-encoded y agregar numFuncion para que el servidor lo reconozca
    const params = new URLSearchParams();
    params.append("numFuncion", "5");
    params.append("correo", correo);
    params.append("contraseña", contraseña);
    params.append("claveUsuario", claveUsuario);
    params.append("usuario", usuario);

    // Enviar los datos al servidor (archivo PHP)
    fetch("../Servidor/PHP/correo.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: params.toString(),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Cerrar el modal
          const modalCorreo = new bootstrap.Modal(
            document.getElementById("modalCorreo")
          );
          modalCorreo.hide();

          // Mostrar mensaje de éxito con SweetAlert
          Swal.fire({
            icon: "success",
            title: "Guardado",
            text: "El correo se guardó exitosamente.",
          });
          modalCorreo.hide();
          // Actualizar la tabla para reflejar el nuevo correo
          obtenerCorreosTabla(); // Llama la función que actualiza la tabla
        } else {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: "Hubo un error al guardar el correo.",
          });
        }
      })
      .catch((error) => {
        console.error("Error al guardar el correo:", error);
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Error al guardar el correo.",
        });
      });
  });
document
  .getElementById("btnGuardarEdicion")
  .addEventListener("click", function () {
    // Obtener los valores del formulario de edición y limpiar espacios en blanco
    const correoEditar = document.getElementById("correoEditar").value.trim();
    const contraseñaEditar = document
      .getElementById("contraseñaEditar")
      .value.trim();
    const documentId = document.getElementById("documentId").value;
    const claveUsuario = document.getElementById("claveUsuarioEditar").value;
    const usuario = document.getElementById("usuarioEditar").value;

    // Verificar que los campos obligatorios no estén vacíos
    if (!correoEditar || !contraseñaEditar || !documentId) {
      Swal.fire({
        icon: "warning",
        title: "Campos incompletos",
        text: "Por favor, completa todos los campos.",
      });
      return;
    }

    // Crear parámetros URL-encoded, incluyendo numFuncion=3 para la edición
    const params = new URLSearchParams();
    params.append("numFuncion", "3");
    params.append("correo", correoEditar);
    params.append("contrasena", contraseñaEditar);
    params.append("documentId", documentId);
    params.append("claveUsuario", claveUsuario);
    params.append("usuario", usuario);

    // Enviar los datos al servidor (archivo PHP)
    fetch("../Servidor/PHP/correo.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: params.toString(),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          Swal.fire({
            icon: "success",
            title: "Actualizado",
            text: "El correo se actualizó correctamente.",
            timer: 2000,
            showConfirmButton: false,
          });
          // Cerrar el modal de edición
          const editarModalEl = document.getElementById("editarModal");
          const editarModal = bootstrap.Modal.getInstance(editarModalEl);
          editarModal.hide();

          // Actualizar la tabla para reflejar los cambios
          obtenerCorreosTabla();
        } else {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: data.message || "Hubo un error al actualizar el correo.",
          });
        }
      })
      .catch((error) => {
        console.error("Error al guardar la edición:", error);
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Error al guardar la edición del correo.",
        });
      });
  });
// Cerrar modal al hacer clic en el botón del header
document.getElementById('cerrarModalCorreoHeader').addEventListener('click', function() {
  var modalElement = document.getElementById('modalCorreo');
  var modalInstance = bootstrap.Modal.getInstance(modalElement);
  if (!modalInstance) {
    modalInstance = new bootstrap.Modal(modalElement);
  }
  modalInstance.hide();
});

// Cerrar modal al hacer clic en el botón del footer (Cancelar)
document.getElementById('cerrarModalCorreoFooter').addEventListener('click', function() {
  var modalElement = document.getElementById('modalCorreo');
  var modalInstance = bootstrap.Modal.getInstance(modalElement);
  if (!modalInstance) {
    modalInstance = new bootstrap.Modal(modalElement);
  }
  modalInstance.hide();
});

// Cerrar modal al hacer clic en el botón del header
document.getElementById('cerrarModalCorreoEditarHeader').addEventListener('click', function() {
  var modalElement = document.getElementById('editarModal');
  var modalInstance = bootstrap.Modal.getInstance(modalElement);
  if (!modalInstance) {
    modalInstance = new bootstrap.Modal(modalElement);
  }
  modalInstance.hide();
});

// Cerrar modal al hacer clic en el botón del footer (Cancelar)
document.getElementById('cerrarModalCorreoEditarFooter').addEventListener('click', function() {
  var modalElement = document.getElementById('editarModal');
  var modalInstance = bootstrap.Modal.getInstance(modalElement);
  if (!modalInstance) {
    modalInstance = new bootstrap.Modal(modalElement);
  }
  modalInstance.hide();
});

$(document).ready(function () {
  // Al cambiar el tipo de usuario se realiza la llamada para obtener los usuarios de Firebase
  $("#selectUsuario").on("change", function () {
    var tipo = $(this).val();
    console.log("Tipo seleccionado:", tipo);

    // Deshabilitamos el select de usuario mientras se carga la información
    $("#selectVendedor").prop("disabled", true);

    $.ajax({
      url: "../Servidor/PHP/correo.php?numFuncion=6", // Ajusta la URL si es necesario
      method: "GET",
      dataType: "json",
      success: function (response) {
        console.log("Respuesta del servidor:", response);
        if (response.success) {
          // Verifica que response.data exista y sea un array
          if (!Array.isArray(response.data)) {
            console.error("El campo data no es un array:", response.data);
            return;
          }

          // Filtramos los usuarios por el tipo seleccionado (aseguramos que se comparen en mayúsculas)
          var usuarios = response.data.filter(function (usuario) {
            return (
              (usuario.tipoUsuario || "").toUpperCase() === tipo.toUpperCase()
            );
          });
          console.log("Usuarios filtrados:", usuarios);

          // Limpiamos y llenamos el select de usuarios
          $("#selectVendedor").empty();
          $("#selectVendedor").append(
            "<option selected disabled>Selecciona un Usuario</option>"
          );

          $.each(usuarios, function (i, usuario) {
            $("#selectVendedor").append(
              `<option value="${usuario.id}" data-correo="${usuario.correo}" data-clave="${usuario.claveUsuario}" data-usuario="${usuario.usuario}">${usuario.nombre}</option>`
            );
          });

          // Habilitamos el select de usuario
          $("#selectVendedor").prop("disabled", false);
        } else {
          alert(response.message);
        }
      },
      error: function (xhr, status, error) {
        console.error("Error en la solicitud AJAX:", status, error);
        alert("Error al obtener los usuarios.");
      },
    });
  });

  // Al seleccionar un usuario se extraen el correo y la clave de usuario
  $("#selectVendedor").on("change", function () {
    var correo = $(this).find(":selected").data("correo");
    var clave = $(this).find(":selected").data("clave");
    var usuario = $(this).find(":selected").data("usuario");

    // Se rellena automáticamente el campo de correo
    $("#correo").val(correo);

    $("#claveUsuario").val(clave);
    $("#usuario").val(usuario);
  });
});
