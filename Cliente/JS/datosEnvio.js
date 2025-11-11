// Variables globales para paginación
let todosLosDatos = [];
let todosLosDatosOriginales = [];
let paginaActual = 1;
const registrosPorPagina = 20; // Ajustado a 20 registros por página

function obtenerDatosTabla() {
  $.ajax({
    url: "../Servidor/PHP/clientes.php",
    method: "GET",
    data: { numFuncion: "15" },
    success: function (response) {
      try {
        if (response.success) {
          todosLosDatos = response.data || [];
          todosLosDatosOriginales = [...todosLosDatos]; // Guardar copia para búsqueda
          paginaActual = 1;
          mostrarDatosPagina();
          actualizarControlesPaginacion();
        } else {
          console.error("Error al obtener los datos");
          const tablaBody = document.querySelector("#tablaDatos tbody");
          tablaBody.innerHTML = "<tr><td colspan='4' class='text-center'>No se encontraron datos</td></tr>";
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

function mostrarDatosPagina() {
  const tablaBody = document.querySelector("#tablaDatos tbody");
  tablaBody.innerHTML = "";

  if (todosLosDatos.length === 0) {
    tablaBody.innerHTML = "<tr><td colspan='4' class='text-center'>No se encontraron datos</td></tr>";
    return;
  }

  const inicio = (paginaActual - 1) * registrosPorPagina;
  const fin = inicio + registrosPorPagina;
  const datosPagina = todosLosDatos.slice(inicio, fin);

  datosPagina.forEach((correo) => {
    const row = document.createElement("tr");

    const cellCorreo = document.createElement("td");
    cellCorreo.textContent = correo.clienteNombre || "(sin nombre)";
    row.appendChild(cellCorreo);

    const cellTitulo = document.createElement("td");
    cellTitulo.textContent = correo.tituloEnvio || "";
    row.appendChild(cellTitulo);

    const cellVisualizar = document.createElement("td");
    const btnVisualizar = document.createElement("button");
    btnVisualizar.textContent = "Visualizar";
    btnVisualizar.classList.add("btn", "btn-info", "btn-sm");
    btnVisualizar.onclick = () => visualizarDatos(correo.idDocumento);
    cellVisualizar.appendChild(btnVisualizar);
    row.appendChild(cellVisualizar);

    const cellEditar = document.createElement("td");
    const btnEditar = document.createElement("button");
    btnEditar.textContent = "Editar";
    btnEditar.classList.add("btn", "btn-info", "btn-sm");
    btnEditar.onclick = () => editarDatos(correo.idDocumento);
    cellEditar.appendChild(btnEditar);
    row.appendChild(cellEditar);

    tablaBody.appendChild(row);
  });
}

function actualizarControlesPaginacion() {
  const totalPaginas = Math.ceil(todosLosDatos.length / registrosPorPagina);
  let controlesPaginacion = document.getElementById("controlesPaginacion");
  
  if (!controlesPaginacion) {
    // Crear contenedor de paginación si no existe
    const tablaContainer = document.querySelector(".table-data .order");
    controlesPaginacion = document.createElement("div");
    controlesPaginacion.id = "controlesPaginacion";
    controlesPaginacion.className = "d-flex justify-content-between align-items-center mt-3";
    tablaContainer.appendChild(controlesPaginacion);
  }

  controlesPaginacion.innerHTML = `
    <div class="d-flex align-items-center">
      <span class="me-3">Mostrando ${((paginaActual - 1) * registrosPorPagina) + 1} - ${Math.min(paginaActual * registrosPorPagina, todosLosDatos.length)} de ${todosLosDatos.length} registros</span>
    </div>
    <nav>
      <ul class="pagination mb-0">
        <li class="page-item ${paginaActual === 1 ? 'disabled' : ''}">
          <a class="page-link" href="#" onclick="cambiarPagina(${paginaActual - 1}); return false;">Anterior</a>
        </li>
        ${Array.from({ length: totalPaginas }, (_, i) => i + 1)
          .map(num => `
            <li class="page-item ${num === paginaActual ? 'active' : ''}">
              <a class="page-link" href="#" onclick="cambiarPagina(${num}); return false;">${num}</a>
            </li>
          `).join('')}
        <li class="page-item ${paginaActual === totalPaginas ? 'disabled' : ''}">
          <a class="page-link" href="#" onclick="cambiarPagina(${paginaActual + 1}); return false;">Siguiente</a>
        </li>
      </ul>
    </nav>
  `;
}

function cambiarPagina(nuevaPagina) {
  const totalPaginas = Math.ceil(todosLosDatos.length / registrosPorPagina);
  if (nuevaPagina < 1 || nuevaPagina > totalPaginas) {
    return;
  }
  paginaActual = nuevaPagina;
  mostrarDatosPagina();
  actualizarControlesPaginacion();
  // Scroll hacia arriba de la tabla
  document.querySelector("#tablaDatos").scrollIntoView({ behavior: 'smooth', block: 'start' });
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
  const clienteId = document.getElementById("cliente").value;

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
          obtenerDatosTabla(); // Recargar la lista de envíos
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
function actualizarDatos() {
  // 1. Obtener el ID del cliente seleccionado
  const idDatosEditar = document.getElementById("idDatosEditar").value;

  // 2. Leer todos los campos del formulario de nuevo envío
  const tituloEnvio = document.getElementById("titutoDatosEditar").value;
  const nombreContacto = document.getElementById("nombreContactoEditar").value;
  const compañia = document.getElementById("compañiaContactoEditar").value;
  const telefonoContacto = document.getElementById(
    "telefonoContactoEditar"
  ).value;
  const correoContacto = document.getElementById("correoContactoEditar").value;
  const linea1Contacto = document.getElementById(
    "direccion1ContactoEditar"
  ).value;
  const linea2Contacto = document.getElementById(
    "direccion2ContactoEditar"
  ).value;
  const codigoContacto = document.getElementById("codigoContactoEditar").value;
  const estadoContacto = document.getElementById("estadoContactoEditar").value;
  const municipioContacto = document.getElementById(
    "municipioContactoEditar"
  ).value;

  // 3. Validar que no falte ninguno de los campos requeridos
  if (
    !idDatosEditar ||
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
      numFuncion: "17", // Identificador de la función en el servidor
      idDocumento: idDatosEditar, // ID del cliente al que pertenece esta dirección
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
          $("#modalEnvioEditar").modal("hide");
          obtenerDatosTabla(); // Recargar la lista de envíos
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
  obtenerDatosEnvioVisualizar(id);
  modalEnvio.show();
}
function editarDatos(id) {
  // Abrir el modal
  const modalEnvio = new bootstrap.Modal(
    document.getElementById("modalEnvioEditar")
  );
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
        .prop("disabled", true)
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
function obtenerEstadosEditar() {
  return $.ajax({
    url: "../Servidor/PHP/ventas.php",
    method: "POST",
    data: { numFuncion: "22" },
    dataType: "json",
  })
    .done(function (resEstado) {
      const $sel = $("#estadoContactoEditar")
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
function obtenerMunicipiosEditar(edo, municipio) {
  // Habilitamos el select
  //$("#estadoContacto").prop("disabled", false);
  $.ajax({
    url: "../Servidor/PHP/ventas.php",
    method: "POST",
    data: { numFuncion: "23", estado: edo },
    dataType: "json",
    success: function (resMunicipio) {
      if (resMunicipio.success && Array.isArray(resMunicipio.data)) {
        const $municipioNuevoContacto = $("#municipioContactoEditar");
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
        $("#municipioContactoEditar").val(municipio);
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
function obtenerDatosEnvioVisualizar(id) {
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
        Swal.fire(
          "Error",
          response.message || "No se pudo obtener datos.",
          "error"
        );
      }
    },
    "json"
  ).fail(() => {
    Swal.fire("Error", "Fallo la petición al servidor.", "error");
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
        //$("#estadoContactoEditar").prop("disabled", true);
        //$("#nombreContactoEditar").prop("disabled", true);

        // 2) Cargo la lista de estados, y en su callback selecciono el que venga
        obtenerEstadosEditar().then(() => {
          // aquí el select ya está lleno de <option value="XXX">Estado XXX</option>
          $("#estadoContactoEditar").val(edo);

          // Ya que el estado está seleccionado, cargo municipios pasándole el valor
          obtenerMunicipiosEditar(edo, municipio);
        });

        // resto de campos
        $("#idDatosEditar").val(id);
        $("#folioDatosEditar").val(data.id.integerValue);
        $("#nombreContactoEditar").val(data.nombreContacto.stringValue);
        $("#titutoDatosEditar").val(data.tituloEnvio.stringValue);
        $("#compañiaContactoEditar").val(data.compania.stringValue);
        $("#telefonoContactoEditar").val(data.telefonoContacto.stringValue);
        $("#correoContactoEditar").val(data.correoContacto.stringValue);
        $("#direccion1ContactoEditar").val(data.linea1.stringValue);
        $("#direccion2ContactoEditar").val(data.linea2.stringValue);
        $("#codigoContactoEditar").val(data.codigoPostal.stringValue);
      } else {
        Swal.fire(
          "Error",
          response.message || "No se pudo obtener datos.",
          "error"
        );
      }
    },
    "json"
  ).fail(() => {
    Swal.fire("Error", "Fallo la petición al servidor.", "error");
  });
}

const inputCliente = $("#cliente");
const clearButton = $("#clearInput");
const suggestionsList = $("#clientesSugeridos");
// Mostrar/ocultar el botón "x"
function toggleClearButton() {
  if (inputCliente.val().trim() !== "") {
    clearButton.show();
  } else {
    clearButton.hide();
  }
}
function showCustomerSuggestions() {
  const clienteInput = document.getElementById("cliente");
  const clienteInputValue = clienteInput.value.trim();
  const sugerencias = document.getElementById("clientesSugeridos");

  sugerencias.classList.remove("d-none"); // Mostrar las sugerencias

  // Generar las sugerencias en base al texto ingresado
  const clientesFiltrados = clientesData.filter((cliente) =>
    cliente.NOMBRE.toLowerCase().includes(clienteInputValue.toLowerCase())
  );

  sugerencias.innerHTML = ""; // Limpiar las sugerencias anteriores

  if (clientesFiltrados.length === 0) {
    sugerencias.innerHTML = "<li>No se encontraron coincidencias</li>";
  } else {
    clientesFiltrados.forEach((cliente) => {
      const sugerencia = document.createElement("li");
      sugerencia.textContent = `${cliente.CLAVE} - ${cliente.NOMBRE}`;
      sugerencia.classList.add("suggestion-item");

      // Evento para seleccionar cliente desde las sugerencias
      sugerencia.addEventListener("click", (e) => {
        e.stopPropagation(); // Evitar que el evento de clic global oculte las sugerencias
        seleccionarClienteDesdeSugerencia(cliente);
      });

      sugerencias.appendChild(sugerencia);
    });
  }
}
// Función para seleccionar un cliente desde las sugerencias
function seleccionarClienteDesdeSugerencia(cliente) {
  const clienteInput = document.getElementById("cliente");
  clienteInput.value = cliente.CLAVE; // Solo guarda la clave, sin el nombre
  clienteId = cliente.CLAVE; // Guardar el ID del cliente

  // Limpiar y ocultar sugerencias
  const sugerencias = document.getElementById("clientesSugeridos");
  sugerencias.innerHTML = ""; // Limpiar las sugerencias
  sugerencias.classList.add("d-none"); // Ocultar las sugerencias
}
// Limpiar todos los campos
function clearAllFields() {
  // Limpiar valores de los inputs
  $("#cliente").val("");

  // Limpiar la lista de sugerencias
  suggestionsList.empty().hide();

  // Ocultar el botón "x"
  clearButton.hide();
}
// Ocultar sugerencias al hacer clic fuera del input
document.addEventListener("click", function (e) {
  const sugerencias = document.getElementById("clientesSugeridos");
  const clienteInput = document.getElementById("cliente");

  if (!sugerencias.contains(e.target) && !clienteInput.contains(e.target)) {
    sugerencias.classList.add("d-none");
  }
});
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
