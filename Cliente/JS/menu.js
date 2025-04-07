let idEmpresarial;

function cerrarModal() {
  const modal = bootstrap.Modal.getInstance(
    document.getElementById("infoEmpresa")
  );
  modal.hide();
}
function cerrarModalSae() {
  const modal = bootstrap.Modal.getInstance(
    document.getElementById("infoConexion")
  );
  modal.hide();
}
function informaEmpresa() {
  const noEmpresa = sessionStorage.getItem("noEmpresaSeleccionada");
  $.post(
    "../Servidor/PHP/empresas.php",
    {
      action: "sesion",
      ed: "2",
      noEmpresa: noEmpresa,
    },
    function (response) {
      if (response.success && response.data) {
        const data = response.data;

        // Verifica la estructura de los datos en el console.log
        //console.log(data);  // Esto te mostrará el objeto completo
        $("#idDocumento").val(data.idDocumento);
        $("#noEmpresa").val(data.noEmpresa);
        $("#razonSocial").val(data.razonSocial);
        $("#rfc").val(data.rfc);
        $("#calle").val(data.calle);
        $("#numExterior").val(data.numExterior);
        $("#numInterior").val(data.numInterior);
        $("#entreCalle").val(data.entreCalle);
        $("#colonia").val(data.colonia);
        $("#referencia").val(data.referencia);
        $("#pais").val(data.pais);
        $("#estado").val(data.estado);
        $("#municipio").val(data.municipio);
        $("#codigoPostal").val(data.codigoPostal);
        $("#poblacion").val(data.poblacion);
        $("#regimenFiscal").val(data.regimenFiscal);
        mostrarRegimen(data.regimenFiscal);
      } else {
        console.warn(
          "Error:",
          response.message || "Error al obtener las empresas."
        );
        alert(response.message || "Error al obtener las empresas.");
      }
    },
    "json"
  ).fail(function (jqXHR, textStatus, errorThrown) {
    console.error("Error en la petición:", textStatus, errorThrown);
  });
}

function mostrarRegimen(clave){
  $.ajax({
    url: "../Servidor/PHP/empresas.php",
    method: "POST",
    data: { action: "regimen"}, // Obtener todos los clientes disponibles
    success: function (responseRegimen) {
      try {
        const resRegimen =
          typeof responseRegimen === "string"
            ? JSON.parse(responseRegimen)
            : responseRegimen;

        if (resRegimen.success && Array.isArray(resRegimen.data)) {
          const regimenFiscal = $("#regimenFiscal");
          regimenFiscal.empty();
          regimenFiscal.append(
            "<option selected disabled>Selecciona un regimen</option>"
          );

          resRegimen.data.forEach((regimen) => {
            regimenFiscal.append(
              `<option value="${regimen.c_RegimenFiscal}" 
                data-descripcion="${regimen.Descripcion}" 
                data-correo="${regimen.correo || ""}" 
                data-fisica="${regimen.Fisica || ""}"
                data-moral="${regimen.Moral || ""}">
                ${regimen.c_RegimenFiscal} || ${regimen.Descripcion}
              </option>`
            );
          });
          regimenFiscal.val(clave);
        } else {
          Swal.fire({
            icon: "warning",
            title: "Aviso",
            text:
              resClientes.message || "No se encontraron clientes.",
          });
          $("#regimenFiscalModal").prop("disabled", true);
        }
      } catch (error) {
        console.error(
          "Error al procesar la respuesta de clientes:",
          error
        );
      }
    },
    error: function () {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Error al obtener la lista de clientes.",
      });
    },
  });
}

function mostrarMenu() {
  document.getElementById("divContenedor").style.display = "block";
}

// Función para cargar los datos de la empresa
function cargarEmpresa(usuario) {
  // Realiza una solicitud GET al servidor para obtener las empresas
  $.get(
    "../Servidor/PHP/empresas.php",
    { action: "get", usuario: usuario },
    function (response) {
      if (response.success && response.data) {
        const empresas = response.data;
        const empresaSelect = document.getElementById("empresaSelect");
        empresaSelect.innerHTML =
          "<option selected disabled>Selecciona una empresa</option>";
        empresas.forEach((empresa) => {
          const option = document.createElement("option");
          option.value = empresa.id;
          option.textContent = `${empresa.noEmpresa} - ${empresa.razonSocial}`;
          option.setAttribute("data-no-empresa", empresa.noEmpresa);
          option.setAttribute("data-razon-social", empresa.razonSocial);
          option.setAttribute("data-clave-vendedor", empresa.claveUsuario);
          option.setAttribute("data-clave-sae", empresa.claveSae);
          option.setAttribute("data-contrasena", empresa.contrasena);

          empresaSelect.appendChild(option);
        });
      } else {
        alert(response.message || "Error al obtener las empresas.");
      }
    },
    "json"
  );
}
function seleccionarEmpresa(noEmpresa) {
  // Guarda el número de empresa en sessionStorage
  sessionStorage.setItem("noEmpresaSeleccionada", noEmpresa);
}
function guardarEmpresa(){
  /*if (!validateForm()) {
    return; // Si la validación falla, no se envía el formulario
  }*/
  const data = {
    action: "save",
    id: $("#noEmpresaModal").val(),
    noEmpresa: $("#noEmpresaModal").val(), 
    razonSocial: $("#razonSocialModal").val(),
    rfc: $("#rfcModal").val(),
    regimenFiscal: $("#regimenFiscalModal").val(),
    calle: $("#calleModal").val(),
    numExterior: $("#numExteriorModal").val(),
    numInterior: $("#numInteriorModal").val() || "*",
    entreCalle: $("#entreCalleModal").val() || "*",
    colonia: $("#coloniaModal").val(),
    referencia: $("#referenciaModal").val(),
    pais: $("#paisModal").val(),
    estado: $("#estadoModal").val(),
    municipio: $("#municipioModal").val(),
    codigoPostal: $("#codigoPostalModal").val(),
    poblacion: $("#poblacionModal").val(),
    token: $("#csrf_tokenModal").val(),
  };
  $.ajax({
    url: "../Servidor/PHP/empresas.php",
    type: "POST",
    data: data,
    dataType: "json",
    success: function (response) {
      if (response.success) {
        Swal.fire({
          title: "¡Éxito!",
          text: "Documento guardado correctamente.",
          icon: "success",
        }).then(() => {
          cerrarModalEmpresa();
        });
      } else {
        Swal.fire({
          title: "Error",
          text: "Error: " + response.message,
          icon: "error",
        });
      }
    },
    error: function (xhr, status, error) {
      console.error("Error al enviar la solicitud", error);
      Swal.fire({
        title: "Error",
        text: "Ocurrió un error al guardar la empresa.",
        icon: "error",
      });
    },
  });
}
// Función para guardar o actualizar la empresa
function actualizarEmpresa() {
  if (!validateForm()) {
    return; // Si la validación falla, no se envía el formulario
  }
  const data = {
    action: "update",
    idDocumento: $("#idDocumento").val(),
    id: $("#id").val(),
    noEmpresa: $("#noEmpresa").val(), // Aquí se manda el noEmpresa
    razonSocial: $("#razonSocial").val(),
    rfc: $("#rfc").val(),
    regimenFiscal: $("#regimenFiscal").val(),
    calle: $("#calle").val(),
    numExterior: $("#numExterior").val(),
    numInterior: $("#numInterior").val() || "*",
    entreCalle: $("#entreCalle").val() || "*",
    colonia: $("#colonia").val(),
    referencia: $("#referencia").val(),
    pais: $("#pais").val(),
    estado: $("#estado").val(),
    municipio: $("#municipio").val(),
    codigoPostal: $("#codigoPostal").val(),
    poblacion: $("#poblacion").val(),
    token: $("#csrf_token").val(),
  };
  $.ajax({
    url: "../Servidor/PHP/empresas.php",
    type: "POST",
    data: data,
    dataType: "json",
    success: function (response) {
      if (response.success) {
        Swal.fire({
          title: "¡Éxito!",
          text: "Documento actualizado correctamente.",
          icon: "success",
        });
      } else {
        Swal.fire({
          title: "Error",
          text: "Error: " + response.message,
          icon: "error",
        });
      }
    },
    error: function (xhr, status, error) {
      console.error("Error al enviar la solicitud", error);
      Swal.fire({
        title: "Error",
        text: "Ocurrió un error al guardar la empresa.",
        icon: "error",
      });
    },
  });
}

// Función para validar campos vacíos
function validateForm() {
  const fields = ["razonSocial", "rfc", "regimenFiscal", "codigoPostal"];

  let isValid = true;

  for (let field of fields) {
    const input = document.getElementById(field);
    if (input && input.value.trim() === "") {
      input.classList.add("input-error");
      input.placeholder = `Campo Obligatorio`;
      isValid = false;
    } else {
      input.classList.remove("input-error");
      input.placeholder = "";
    }
  }

  return isValid;
}

// Función para eliminar la empresa
function eliminarEmpresa() {
  if (confirm("¿Estás seguro de que deseas eliminar la empresa?")) {
    $.post(
      "../../Servidor/PHP/empresas.php",
      { action: "delete" },
      function (response) {
        if (response.success) {
          alert("Empresa eliminada correctamente.");
          $("#empresaForm")[0].reset();
        } else {
          alert("Error al eliminar la empresa.");
        }
      },
      "json"
    );
  }
}

function probarConexionSAE() {
  if (!validateForm2()) {
    return; // Si la validación falla, no se envía el formulario
  }
  const data = {
    action: "probar",
    host: $("#host").val(),
    puerto: $("#usuarioSae").val(),
    usuarioSae: $("#usuarioSae").val(),
    password: $("#password").val(),
    nombreBase: $("#nombreBase").val(),
    claveSae: $("#claveSae").val(),
  };
  fetch("../Servidor/PHP/sae.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(data),
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error("Error en la respuesta del servidor");
      }
      return response.json();
    })
    .then((responseData) => {
      console.log("Respuesta del servidor:", responseData);
      if (responseData.success) {
        alert("Conexión exitosa.");
      } else {
        alert("Error: " + responseData.message);
      }
    })
    .catch((error) => {
      console.error("Error de la solicitud:", error);
      alert("Error en la solicitud: " + error.message);
    });
}
function guardarConexionSAE() {
  const noEmpresa = sessionStorage.getItem("noEmpresaSeleccionada");
  const data = {
    action: "guardar",
    idDocumento: $("#idDocumento").val(),
    host: $("#host").val(),
    puerto: $("#puerto").val(),
    usuarioSae: $("#usuarioSae").val(),
    password: $("#password").val(),
    nombreBase: $("#nombreBase").val(),
    claveSae: $("#claveSae").val(),
    noEmpresa: noEmpresa,
    token: $("#csrf_token").val(),
  };
  $.ajax({
    url: "../Servidor/PHP/sae.php",
    type: "POST",
    data: JSON.stringify(data),
    contentType: "application/json",
    dataType: "json",
    success: function (response) {
      console.log("Respuesta del servidor:", response); // Verifica lo que devuelve el servidor
      if (response.success) {
        Swal.fire({
          title: "¡Éxito!",
          text: "Conexion actualizada.",
          icon: "success",
        });
      } else {
        Swal.fire({
          icon: "warning",
          title: "Campos faltantes",
          text: "Error " + response.message,
        });
      }
    },
    error: function (xhr, status, error) {
      console.error("Error:", xhr.responseText); // Mostrar respuesta completa para debug
      Swal.fire({
        icon: "warning",
        title: "Campos faltantes",
        text: "Error " + error,
      });
    },
  });
}
function guardarConexionSAENew() {
  const noEmpresa = sessionStorage.getItem("noEmpresaSeleccionada");
  const data = {
    action: "guardarNew",
    host: $("#host").val(),
    puerto: $("#puerto").val(),
    usuarioSae: $("#usuarioSae").val(),
    password: $("#password").val(),
    nombreBase: $("#nombreBase").val(),
    claveSae: $("#claveSae").val(),
    noEmpresa: noEmpresa,
    token: $("#csrf_token").val(),
  };

  $.ajax({
    url: "../Servidor/PHP/sae.php",
    type: "POST",
    data: JSON.stringify(data),
    contentType: "application/json",
    dataType: "json",
    success: function (response) {
      console.log("Respuesta del servidor:", response); // Verifica lo que devuelve el servidor
      if (response.success) {
        Swal.fire({
          icon: "success",
          title: "Conexión Creada",
          text: "Se ha creado la conexión correctamente. Se cerrará la sesión para aplicar los cambios.",
          timer: 2000, // Muestra el mensaje durante 2 segundos antes de cerrar sesión
          showConfirmButton: false,
          allowOutsideClick: false,
          allowEscapeKey: false,
        }).then(() => {
          cerrarSesionAutomatica();
        });
      } else {
        Swal.fire({
          title: "Error",
          text: response.message,
          icon: "error",
        });
      }
    },
    error: function (xhr, status, error) {
      console.error("Error:", xhr.responseText); // Mostrar respuesta completa para debug
      Swal.fire({
        title: "Error",
        text: "Error al conectar con el servidor: " + error,
        icon: "error",
      });
    },
  });
}
// Función para cerrar sesión automáticamente después de crear la conexión
function cerrarSesionAutomatica() {
  Swal.fire({
    title: "Cerrando Sesión...",
    text: "Espere un momento",
    icon: "info",
    timer: 1500, // Espera 1.5 segundos antes de cerrar sesión
    showConfirmButton: false,
    allowOutsideClick: false,
    allowEscapeKey: false,
  });

  setTimeout(() => {
    $.post("../Servidor/PHP/conexion.php", { numFuncion: 2 }, function (data) {
      limpiarCacheEmpresa();
      window.location.href = "index.php"; // Redirigir al login después de cerrar sesión
    }).fail(function () {
      Swal.fire({
        title: "Error",
        text: "Error al intentar cerrar sesión.",
        icon: "error",
      });
    });
  }, 1500);
}

function informaSae() {
  const noEmpresa = sessionStorage.getItem("noEmpresaSeleccionada");
  const data = {
    action: "mostrar",
    noEmpresa: noEmpresa,
    claveSae: $("#claveSae").val(),
  };
  $.ajax({
    url: "../Servidor/PHP/sae.php",
    type: "POST",
    contentType: "application/json",
    data: JSON.stringify(data),
    dataType: "json",
    success: function (response) {
      if (response.success && response.data) {
        const data = response.data;
        $("#idDocumento").val(data.id);
        $("#host").val(data.host);
        $("#puerto").val(data.puerto);
        $("#usuarioSae").val(data.usuarioSae);
        $("#password").val(data.password);
        $("#nombreBase").val(data.nombreBase);
        $("#claveSae").val(data.claveSae);
      } else {
        console.warn(
          "Error:",
          response.message || "Error al obtener la conexión."
        );
        /*Swal.fire({
          title: "Eror",
          text: response.message,
          icon: "error",
        });*/
        //alert(response.message || 'Error al obtener la conexión.');
      }
    },
    error: function (jqXHR, textStatus, errorThrown) {
      console.error("Error en la petición:", textStatus, errorThrown);
    },
  });
}
function informaSaeInicio(claveSae) {
  const noEmpresa = sessionStorage.getItem("noEmpresaSeleccionada");
  const data = {
    action: "mostrar",
    noEmpresa: noEmpresa,
    claveSae: claveSae,
  };
  $.ajax({
    url: "../Servidor/PHP/sae.php",
    type: "POST",
    contentType: "application/json",
    data: JSON.stringify(data),
    dataType: "json",
    success: function (response) {
      if (response.success && response.data) {
        const data = response.data;
        $("#idDocumento").val(data.id);
        $("#host").val(data.host);
        $("#puerto").val(data.puerto);
        $("#usuarioSae").val(data.usuarioSae);
        $("#password").val(data.password);
        $("#nombreBase").val(data.nombreBase);
        $("#claveSae").val(data.claveSae);
      } else {
        console.warn(
          "Error:",
          response.message || "Error al obtener la conexión."
        );
        Swal.fire({
          title: "Eror",
          text: response.message,
          icon: "error",
        });
        //alert(response.message || 'Error al obtener la conexión.');
      }
    },
    error: function (jqXHR, textStatus, errorThrown) {
      console.error("Error en la petición:", textStatus, errorThrown);
    },
  });
}
function sesionEmpresa(idEmpresarial) {
  var id = idEmpresarial.id;
  var noEmpresa = idEmpresarial.noEmpresa;
  var razonSocial = idEmpresarial.razonSocial;
  var claveUsuario = idEmpresarial.claveUsuario;
  var claveSae = idEmpresarial.claveSae;
  var contrasena = idEmpresarial.contrasena;
  $.post(
    "../Servidor/PHP/empresas.php",
    {
      action: "sesion",
      id: id,
      noEmpresa: noEmpresa,
      razonSocial: razonSocial,
      claveUsuario: claveUsuario,
      claveSae: claveSae,
      contrasena: contrasena,
    },
    function (response) {
      window.location.reload();
      if (response.success) {
        if (
          response.data &&
          response.data.id &&
          response.data.noEmpresa &&
          response.data.razonSocial
        ) {
          console.log(response.data);
          // alert(response.data);
        } else {
          alert(response.message || "Error al guardar la sesión de empresa.");
        }
      }
    }
  ).fail(function (jqXHR, textStatus, errorThrown) {
    console.log("Error en la solicitud: " + textStatus + ", " + errorThrown);
    alert("Error al comunicar con el servidor.");
  });
}
function validateForm2() {
  const fields = ["host", "puerto", "usuarioSae", "password", "nombreBase"];

  let isValid = true;

  for (let field of fields) {
    const input = document.getElementById(field);
    if (input && input.value.trim() === "") {
      input.classList.add("input-error");
      input.placeholder = `Campo Obligatorio`;
      isValid = false;
    } else {
      input.classList.remove("input-error");
      input.placeholder = "";
    }
  }
  return isValid;
}
function limpiarCacheEmpresa() {
  sessionStorage.removeItem("noEmpresaSeleccionada");
  console.log("Cache de la empresa limpiado.");
}
function mostrarMoldal(){
  limpiarFormulario();
  $("#formularioEmpresa").modal("show");
  obtenerRegimen();
}
function obtenerRegimen(){
  $.ajax({
    url: "../Servidor/PHP/empresas.php",
    method: "POST",
    data: { action: "regimen"}, // Obtener todos los clientes disponibles
    success: function (responseRegimen) {
      try {
        const resRegimen =
          typeof responseRegimen === "string"
            ? JSON.parse(responseRegimen)
            : responseRegimen;

        if (resRegimen.success && Array.isArray(resRegimen.data)) {
          const regimenFiscalModal = $("#regimenFiscalModal");
          regimenFiscalModal.empty();
          regimenFiscalModal.append(
            "<option selected disabled>Selecciona un regimen</option>"
          );

          resRegimen.data.forEach((regimen) => {
            regimenFiscalModal.append(
              `<option value="${regimen.c_RegimenFiscal}" 
                data-descripcion="${regimen.Descripcion}" 
                data-correo="${regimen.correo || ""}" 
                data-fisica="${regimen.Fisica || ""}"
                data-moral="${regimen.Moral || ""}">
                ${regimen.c_RegimenFiscal} || ${regimen.Descripcion}
              </option>`
            );
          });
        } else {
          Swal.fire({
            icon: "warning",
            title: "Aviso",
            text:
              resClientes.message || "No se encontraron clientes.",
          });
          $("#regimenFiscalModal").prop("disabled", true);
        }
      } catch (error) {
        console.error(
          "Error al procesar la respuesta de clientes:",
          error
        );
      }
    },
    error: function () {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Error al obtener la lista de clientes.",
      });
    },
  });
}
$("#cerrarModalHeader").on("click", function () {
  cerrarModalEmpresa();
});
$("#cerrarModalFooter").on("click", function () {
  cerrarModalEmpresa();
});
function cerrarModalEmpresa() {
  limpiarFormulario();
  $("#formularioEmpresa").modal("hide"); // Cierra el modal usando Bootstrap
  // Restaurar el aria-hidden al cerrar el modal
  $("#formularioEmpresa").attr("aria-hidden", "true");
  // Eliminar el atributo inert del fondo al cerrar
  $(".modal-backdrop").removeAttr("inert");
}
function limpiarFormulario() {
  $("#noEmpresaModal").val("");
  $("#razonSocialModal").val("");
  $("#rfcModal").val("");
  $("#regimenFiscalModal").val("");
  $("#calleModal").val("");
  $("#numExteriorModal").val("");
  $("#numInteriorModal").val("");
  $("#entreCalleModal").val("");
  $("#coloniaModal").val("");
  $("#referenciaModal").val("");
  $("#paisModal").val(""); 
  $("#estadoModal").val("");
  $("#municipioModal").val("");
  $("#codigoPostalModal").val("");
  $("#poblacionModal").val("");
}
function validarEmpresa(){
  var noEmpresa = document.getElementById("noEmpresaModal").value;
  
  const data = {
    action: "verificar",
    noEmpresa: noEmpresa,
  };
  $.ajax({
    url: "../Servidor/PHP/empresas.php",
    type: "POST",
    data: data,
    dataType: "json",
    success: function (response) {
      if (response.success) {
        Swal.fire({
          title: "Valida",
          text: "Este numero de empresa es valido.",
          icon: "success",
        });
      } else {
        Swal.fire({
          title: "Error",
          text: "Este numero de empresa ya esta ocupado",
          icon: "error",
        });
      }
    },
    error: function (xhr, status, error) {
      console.error("Error al enviar la solicitud", error);
      Swal.fire({
        title: "Error",
        text: "Ocurrió un error al guardar la empresa.",
        icon: "error",
      });
    },
  });
}
$(document).ready(function () {
  $("#cancelarModal").click(function () {
    cerrarModal();
  });
  $("#cancelarModalSae").click(function () {
    cerrarModalSae();
  });

  // Guardar o actualizar empresa
  $("#confirmarDatos").click(function () {
    event.preventDefault();
    actualizarEmpresa();
  });
  $("#guardarEmpresa").click(function () {
    event.preventDefault();
    guardarEmpresa();
  });

  // Eliminar empresa
  $("#eliminarEmpresa").click(function () {
    eliminarEmpresa();
  });
  /*$('#infoSae').click(function () {
        infoSae();
    });*/
  $("#probarConexion").click(function () {
    probarConexionSAE();
  });
  $("#confirmarConexion").click(function () {
    guardarConexionSAE();
  });
  $("#confirmarConexionNew").click(function () {
    guardarConexionSAENew();
  });
  $("#btnAgregar").click(function(){
    mostrarMoldal();
  });
  $("#noEmpresaModal").change(function(){
    validarEmpresa();
  });
  $("#Ayuda").click(function () {
    event.preventDefault();
    Swal.fire({
      title: "Ayuda",
      text: "Aquí puedes poner el mensaje de ayuda que desees mostrar a los usuarios.",
      icon: "info",
      confirmButtonText: "Entendido",
    });
  });

  $("#cerrarSesion").click(function (event) {
    event.preventDefault(); // Prevenir el comportamiento por defecto del botón

    // Mostrar el overlay
    $("#overlay").show();

    Swal.fire({
      title: "¿Estás seguro de que quieres cerrar sesión?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Sí, cerrar sesión",
      cancelButtonText: "Cancelar",
    }).then((result) => {
      // Ocultar el overlay después de la respuesta de SweetAlert
      $("#overlay").hide();

      if (result.isConfirmed) {
        $.post(
          "../Servidor/PHP/conexion.php",
          { numFuncion: 2 },
          function (data) {
            limpiarCacheEmpresa();
            window.location.href = "index.php"; // Redirigir al login
          }
        ).fail(function () {
          Swal.fire({
            title: "Error",
            text: "Error al intentar cerrar sesión.",
            icon: "error",
          });
        });
      }
    });
  });
  $("#cerrarSesionModal").click(function (event) {
    event.preventDefault(); // Prevenir el comportamiento por defecto del botón

    // Mostrar el overlay
    $("#overlay").show();

    Swal.fire({
      title: "¿Estás seguro de que quieres cerrar sesión?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Sí, cerrar sesión",
      cancelButtonText: "Cancelar",
    }).then((result) => {
      // Ocultar el overlay después de la respuesta de SweetAlert
      $("#overlay").hide();

      if (result.isConfirmed) {
        Swal.fire({
          title: "Cerrando Sesión...",
          text: "Espere un momento",
          icon: "info",
          timer: 1500, // Espera 1.5 segundos
          showConfirmButton: false,
          allowOutsideClick: false,
          allowEscapeKey: false,
        });

        // Esperar 1.5 segundos antes de cerrar la sesión
        setTimeout(() => {
          $.post(
            "../Servidor/PHP/conexion.php",
            { numFuncion: 2 },
            function (data) {
              limpiarCacheEmpresa();
              window.location.href = "index.php"; // Redirigir al login
            }
          ).fail(function () {
            Swal.fire({
              title: "Error",
              text: "Error al intentar cerrar sesión.",
              icon: "error",
            });
          });
        }, 1500);
      }
    });
  });
  $("#claveSae").change(function (event) {
    event.preventDefault();
    //informaSae();
  });
});
