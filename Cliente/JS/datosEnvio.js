function obtenerDatosTabla() {
  $.ajax({
    url: "../Servidor/PHP/clientes.php",
    method: "GET",
    data: { numFuncion: "15" }, // Llamar la función para obtener vendedores
    success: function (response) {
      try {
        if (response.success) {
          const tablaBody = document.querySelector("#tablaDatos tbody");
          tablaBody.innerHTML = ""; // Limpiamos la tabla antes de agregar los correos

          response.data.forEach((correo) => {
            const row = document.createElement("tr");

            // Celda para mostrar el correo
            const cellCorreo = document.createElement("td");
            cellCorreo.textContent = correo.clienteNombre;
            row.appendChild(cellCorreo);

            const cellTitulo = document.createElement("td");
            cellTitulo.textContent = correo.tituloEnvio;
            row.appendChild(cellTitulo);

            // Celda para el botón de Visualizar
            const cellVisualizar = document.createElement("td");
            const btnVisualizar = document.createElement("button");
            btnVisualizar.textContent = "Visualizar";
            btnVisualizar.classList.add("btn", "btn-info");
            btnVisualizar.onclick = () => visualizarDatos(correo.idDocumento); // Implementa la función visualizarCorreo
            cellVisualizar.appendChild(btnVisualizar);
            row.appendChild(cellVisualizar);

            tablaBody.appendChild(row);
          });
        } else {
          console.error("Error al obtener los correos");
        }
      } catch (error) {
        console.error("Error al Procesar la Respuesta:", error);
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Error al Cargar Datos de Envio.",
        });
      }
    },
    error: function () {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Error al Obtener la Lista de Datos.",
      });
    },
  });
}
function obtenerClientes() {
  $.ajax({
    url: "../Servidor/PHP/clientes.php",
    method: "GET",
    data: { numFuncion: "16" }, // Función para obtener usuarios
    success: function (response) {
      try {
        const res =
          typeof response === "string" ? JSON.parse(response) : response;

        if (res.success && Array.isArray(res.data)) {
          const selectUsuario = $("#clienteId");
          selectUsuario.empty();
          selectUsuario.append(
            "<option selected disabled>Seleccione un usuario</option>"
          );
          res.data.forEach((usuario) => {
            // Incluimos `data-usuario` para usarlo posteriormente
            selectUsuario.append(
              `<option value="${usuario.CLAVE}" data-usuario="${usuario.NOMBRE}" data-id="${usuario.CLAVE}">${usuario.NOMBRE}</option>`
            );
          });
        } else if (res.token) {
          Swal.fire({
            title: "Error",
            text: res.message,
            icon: "error",
            confirmButtonText: "Entendido",
          });
        } else {
          alert(res.message || "No se pudieron cargar los usuarios.");
        }
      } catch (error) {
        console.error("Error al procesar la respuesta:", error);
        alert("Error al cargar usuarios.");
      }
    },
    error: function () {
      alert("Error al cargar usuarios.");
    },
  });
}
function obtenerEstadosNuevos() {
  // Habilitamos el select
  $("#estadoNuevoContacto").prop("disabled", false);

  $.ajax({
    url: "../Servidor/PHP/ventas.php",
    method: "POST",
    data: { numFuncion: "22" },
    dataType: "json",
    success: function (resEstado) {
      if (resEstado.success && Array.isArray(resEstado.data)) {
        const $estadoNuevoContacto = $("#estadoNuevoContacto");
        $estadoNuevoContacto.empty();
        $estadoNuevoContacto.append(
          "<option selected disabled>Selecciona un Estado</option>"
        );
        // Filtrar según el largo del RFC
        resEstado.data.forEach((estado) => {
          $estadoNuevoContacto.append(
            `<option value="${estado.Clave}" 
                data-Pais="${estado.Pais}">
                ${estado.Descripcion}
              </option>`
          );
        });
      } else {
        Swal.fire({
          icon: "warning",
          title: "Aviso",
          text: resEstado.message || "No se encontraron estados.",
        });
        //$("#estadoNuevoContacto").prop("disabled", true);
      }
    },
    error: function () {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Error al obtener la lista de estados.",
      });
    },
  });
}
function obtenerMunicipiosNuevos() {
  // Habilitamos el select
  $("#municipioNuevoContacto").prop("disabled", false);
  const estado = document.getElementById("estadoNuevoContacto").value;
  $.ajax({
    url: "../Servidor/PHP/ventas.php",
    method: "POST",
    data: { numFuncion: "23", estado: estado },
    dataType: "json",
    success: function (resMunicipio) {
      if (resMunicipio.success && Array.isArray(resMunicipio.data)) {
        const $municipioNuevoContacto = $("#municipioNuevoContacto");
        $municipioNuevoContacto.empty();
        $municipioNuevoContacto.append(
          "<option selected disabled>Selecciona un Municipio</option>"
        );
        // Filtrar según el largo del RFC
        resMunicipio.data.forEach((municipio) => {
          $municipioNuevoContacto.append(
            `<option value="${municipio.Clave}" 
                data-estado="${municipio.Estado}"
                data-descripcion="${municipio.Descripcion || ""}">
                ${municipio.Descripcion}
              </option>`
          );
        });
      } else {
        Swal.fire({
          icon: "warning",
          title: "Aviso",
          text: resMunicipio.message || "No se encontraron municipios.",
        });
        //$("#municipioNuevoContacto").prop("disabled", true);
      }
    },
    error: function () {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Error al obtener la lista de estados.",
      });
    },
  });
}
function guardarDatosEnvio() {
  // 1. Obtener el ID del cliente seleccionado
  const clienteId = document.getElementById("clienteId").value;

  // 2. Leer todos los campos del formulario de nuevo envío
  const tituloEnvio = document.getElementById("titutoContacto").value;
  const nombreContacto = document.getElementById("nombreNuevoContacto").value;
  const compañia = document.getElementById("compañiaNuevoContacto").value;
  const telefonoContacto = document.getElementById(
    "telefonoNuevoContacto"
  ).value;
  const correoContacto = document.getElementById("correoNuevoContacto").value;
  const linea1Contacto = document.getElementById(
    "direccion1NuevoContacto"
  ).value;
  const linea2Contacto = document.getElementById(
    "direccion2NuevoContacto"
  ).value;
  const codigoContacto = document.getElementById("codigoNuevoContacto").value;
  const estadoContacto = document.getElementById("estadoNuevoContacto").value;
  const municipioContacto = document.getElementById(
    "municipioNuevoContacto"
  ).value;

  // 3. Validar que no falte ninguno de los campos requeridos
  if (
    !clienteId ||
    !nombreContacto ||
    !tituloEnvio ||
    !compañia ||
    !correoContacto ||
    !telefonoContacto ||
    !linea1Contacto ||
    !linea2Contacto ||
    !codigoContacto ||
    !estadoContacto ||
    !municipioContacto
  ) {
    Swal.fire({
      icon: "warning",
      title: "Aviso",
      text: "Faltan datos.",
    });
    return; // Abortamos si falta algún campo
  }

  // 4. Enviar los datos al servidor vía AJAX
  $.ajax({
    url: "../Servidor/PHP/clientes.php", // Punto final en PHP que procesará el guardado
    method: "POST",
    data: {
      numFuncion: "6", // Identificador de la función en el servidor
      clienteId: clienteId, // ID del cliente al que pertenece esta dirección
      tituloEnvio: tituloEnvio, // Título o alias de la dirección
      nombreContacto: nombreContacto,
      compañia: compañia,
      telefonoContacto: telefonoContacto,
      correoContacto: correoContacto,
      linea1Contacto: linea1Contacto,
      linea2Contacto: linea2Contacto,
      codigoContacto: codigoContacto,
      estadoContacto: estadoContacto,
      municipioContacto: municipioContacto,
    },
    dataType: "json",
    success: function (envios) {
      // Esta función se ejecuta si la petición AJAX devuelve HTTP 200
      if (envios.success) {
        Swal.fire({
          icon: "success",
          title: "Éxito",
          text: "Se guardaron los nuevos datos de envío.",
        }).then(() => {
          // Cuando cierren el cuadro de alerta, ocultamos el modal y recargamos listados
          $("#modalNuevoEnvio").modal("hide");
          mostrarMoldal(); // Suponemos que esta función recarga la lista de envíos
        });
      } else {
        // Si success = false, mostramos advertencia con el mensaje del servidor
        Swal.fire({
          icon: "warning",
          title: "Aviso",
          text: envios.message || "No se pudieron guardar los datos de envío.",
        });
      }
    },
    error: function () {
      // Si la petición AJAX falla (500, 404, etc.)
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Error al guardar los datos de envío.",
      });
    },
  });
}
function visualizarDatos(id) {
  // Abrir el modal
  const modalEnvio = new bootstrap.Modal(document.getElementById("modalEnvio"));
  obtenerEstados();
  obtenerDatosEnvioEditar(id);
  modalEnvio.show();
}
function obtenerEstados() {
  return $.ajax({
    url: "../Servidor/PHP/ventas.php",
    method: "POST",
    data: { numFuncion: "22" },
    dataType: "json",
  })
    .done(function (resEstado) {
      const $sel = $("#estadoContacto")
        .prop("disabled", false)
        .empty()
        .append("<option selected disabled>Selecciona un Estado</option>");
      if (resEstado.success && Array.isArray(resEstado.data)) {
        resEstado.data.forEach((e) => {
          $sel.append(`<option value="${e.Clave}">${e.Descripcion}</option>`);
        });
      } else {
        Swal.fire(
          "Aviso",
          resEstado.message || "No se encontraron estados.",
          "warning"
        );
      }
    })
    .fail(function () {
      Swal.fire("Error", "No pude cargar la lista de estados.", "error");
    });
}
function obtenerMunicipios(edo, municipio) {
  // Habilitamos el select
  //$("#estadoContacto").prop("disabled", false);
  $.ajax({
    url: "../Servidor/PHP/ventas.php",
    method: "POST",
    data: { numFuncion: "23", estado: edo },
    dataType: "json",
    success: function (resMunicipio) {
      if (resMunicipio.success && Array.isArray(resMunicipio.data)) {
        const $municipioNuevoContacto = $("#municipioContacto");
        $municipioNuevoContacto.empty();
        $municipioNuevoContacto.append(
          "<option selected disabled>Selecciona un Estado</option>"
        );
       
        resMunicipio.data.forEach((municipio) => {
          $municipioNuevoContacto.append(
            `<option value="${municipio.Clave}" 
                data-estado="${municipio.Estado}"
                data-Descripcion="${municipio.Descripcion || ""}">
                ${municipio.Descripcion}
              </option>`
          );
        });
        $("#municipioContacto").val(municipio);
      } else {
        Swal.fire({
          icon: "warning",
          title: "Aviso",
          text: resMunicipio.message || "No se encontraron municipios.",
        });
        //$("#municipioNuevoContacto").prop("disabled", true);
      }
    },
    error: function () {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Error al obtener la lista de estados.",
      });
    },
  });
}
function obtenerDatosEnvioEditar(id) {
  $.post(
    "../Servidor/PHP/clientes.php",
    { numFuncion: "7", idDocumento: id },
    function (response) {
      if (response.success && response.data) {
        const data = response.data.fields;
        const edo = data.estado.stringValue;
        const municipio = data.municipio.stringValue;

        // 1) Habilito el select (si no debe ser sólo lectura)
        $("#estadoContacto").prop("disabled", true);
        $("#nombreContacto").prop("disabled", true);

        // 2) Cargo la lista de estados, y en su callback selecciono el que venga
        obtenerEstados().then(() => {
          // aquí el select ya está lleno de <option value="XXX">Estado XXX</option>
          $("#estadoContacto").val(edo);

          // Ya que el estado está seleccionado, cargo municipios pasándole el valor
          obtenerMunicipios(edo, municipio);
        });

        // resto de campos
        $("#idDatos").val(id);
        $("#folioDatos").val(data.id.integerValue);
        $("#nombreContacto").val(data.nombreContacto.stringValue);
        $("#titutoDatos").val(data.tituloEnvio.stringValue);
        $("#compañiaContacto").val(data.compania.stringValue);
        $("#telefonoContacto").val(data.telefonoContacto.stringValue);
        $("#correoContacto").val(data.correoContacto.stringValue);
        $("#direccion1Contacto").val(data.linea1.stringValue);
        $("#direccion2Contacto").val(data.linea2.stringValue);
        $("#codigoContacto").val(data.codigoPostal.stringValue);
      } else {
        Swal.fire("Error", response.message || "No se pudo obtener datos.", "error");
      }
    },
    "json"
  ).fail(() => {
    Swal.fire("Error", "Fallo la petición al servidor.", "error");
  });
}
document.getElementById("btnAgregar").addEventListener("click", function () {
  // Abrir el modal
  const modalNuevoEnvio = new bootstrap.Modal(
    document.getElementById("modalNuevoEnvio")
  );
  obtenerClientes();
  obtenerEstadosNuevos();
  modalNuevoEnvio.show();
});
document.addEventListener("DOMContentLoaded", obtenerDatosTabla);
$(document).ready(function () {
  $("#guardarDatosEnvio").click(function () {
    guardarDatosEnvio();
  });
  $("#actualizarDatos").click(function () {
    actualizarDatos();
  });
  $("#estadoNuevoContacto").on("change", function () {
    obtenerMunicipiosNuevos();
  });
});
