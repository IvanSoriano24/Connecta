// function datosUsuarios(tipoUsuario, usuario) {
//   $.ajax({
//     url: "../Servidor/PHP/usuarios.php",
//     type: "POST",
//     data: { usuarioLogueado: tipoUsuario, usuario: usuario, numFuncion: "3" },
//     success: function (response) {
//       if (response.success) {
//         mostrarUsuarios(response.data, tipoUsuario); // Llama a otra función para mostrar los usuarios en la página
//       } else {
//         console.log(response.message);
//         alert("Error: " + response.message);
//       }
//     },
//     error: function () {
//       alert("Error en la solicitud AJAX.");
//     },
//   });
// }
console.log(mostrarUsuarios);
// function mostrarUsuarios(usuarios, tipoUsuario) {
//   var tablaClientes = $("#tablaUsuarios"); // Selección del cuerpo de la tabla
//   tablaClientes.empty(); // Limpieza de los datos previos en la tabla

//   // Ordenar los usuarios alfabéticamente por nombreCompleto
//   usuarios.sort(function (a, b) {
//       var nombreA = (a.nombreCompleto || "").toUpperCase(); // Manejar valores nulos o indefinidos
//       var nombreB = (b.nombreCompleto || "").toUpperCase();
//       return nombreA.localeCompare(nombreB); // Comparación estándar de cadenas
//   });

//   // Crear filas para cada usuario
//   usuarios.forEach(function (usuario) {
//       var fila = $("<tr>"); // Crear el elemento <tr>
//       fila.append($("<td>").text(usuario.nombreCompleto || "-")); // Agregar columna de nombre
//       fila.append($("<td>").text(usuario.correo || "-")); // Agregar columna de correo
//       fila.append($("<td>").text(usuario.status || "-")); // Agregar columna de estatus
//       fila.append($("<td>").text(usuario.rol || "-")); // Agregar columna de rol

//       // Botón Editar
//       var botonEditar = $("<button>")
//           .addClass("btn btn-info btn-sm")
//           .text("Editar")
//           .attr("onclick", 'editarUsuario("' + usuario.id + '")');

//       fila.append($("<td>").append(botonEditar));

//       // Botón Visualizar
//       var botonVer = $("<button>")
//           .addClass("btn btn-info btn-sm")
//           .text("Visualizar")
//           .attr("onclick", 'visualizarUsuario("' + usuario.id + '")');

//       fila.append($("<td>").append(botonVer));

//       // Botón Asociaciones
//       var botonVerAsociaciones = $("<button>")
//           .addClass("btn btn-info btn-sm")
//           .text("Asociaciones")
//           .attr("onclick", 'visualizarAsociaciones("' + usuario.usuario + '")');

//       fila.append($("<td>").append(botonVerAsociaciones));

//       if(tipoUsuario == "ADMINISTRADOR"){

//       // Botón Dar de Baja
//       var botonBaja = $("<button>")
//           .addClass("btn btn-danger btn-sm")
//           .text("Dar de Baja")
//           .attr("onclick", 'darDeBajaUsuario("' + usuario.id + '")');

//       fila.append($("<td>").append(botonBaja));
      
//         // Botón Activar Usuario (solo si el estado es 'Baja')
//         if (usuario.status === "Baja") {
//             var botonActivar = $("<button>")
//                 .addClass("btn btn-success btn-sm")
//                 .text("Activar")
//                 .attr("onclick", 'activarUsuario("' + usuario.id + '")'); // Usar el ID del usuario

//             fila.append($("<td>").append(botonActivar));
//         } else {
//             fila.append($("<td>").text("-")); // Columna vacía si no está en 'Baja'
//         }
//       }

//       // Añadir la fila completa a la tabla
//       tablaClientes.append(fila);
//   });
// }

var listaUsuarios = []; // Almacena la lista de usuarios globalmente

/*$(document).ready(function () {
  let tipoUsuario = "ADMINISTRADOR"; // Cambiar si necesitas otro tipo de usuario
  datosUsuarios(tipoUsuario, ""); // Cargar usuarios al inicio
});*/

/* -------------------------------------------------------------------------- */
/*                          FUNCIONES AUXILIARES                              */
/* -------------------------------------------------------------------------- */

// Cargar usuarios desde el servidor
function datosUsuarios(tipoUsuario, usuario) {
  $.ajax({
    url: "../Servidor/PHP/usuarios.php",
    type: "POST",
    data: { usuarioLogueado: tipoUsuario, usuario: usuario, numFuncion: "3" },
    success: function (response) {
      try {
        if (response.success) {
          listaUsuarios = response.data; // Guardar datos en la variable global
          mostrarUsuarios(listaUsuarios, tipoUsuario, "TODOS"); // Mostrar todos los usuarios por defecto
          inicializarEventosBotones(); // Activar eventos en los botones de navegación
        } else {
          console.log(response.message);
          alert("Error: " + response.message);
        }
      } catch (error) {
        console.error("Error procesando respuesta: ", error);
      }
    },
    error: function () {
      alert("Error en la solicitud AJAX.");
    },
  });
}

// Mostrar los usuarios en la tabla filtrados por rol
function mostrarUsuarios(usuarios, tipoUsuario, rolSeleccionado = "TODOS") {
  var tablaClientes = $("#tablaUsuarios");
  tablaClientes.empty(); // Limpiar la tabla antes de mostrar nuevos datos

  // Filtrar usuarios según el rol seleccionado
  let usuariosFiltrados = usuarios.filter(usuario =>
    rolSeleccionado === "TODOS" ? true : usuario.rol === rolSeleccionado
  );

  // Ordenar alfabéticamente
  usuariosFiltrados.sort((a, b) => (a.nombreCompleto || "").localeCompare(b.nombreCompleto || ""));

  // Generar filas
  usuariosFiltrados.forEach(function (usuario) {
    var fila = $("<tr>");
    fila.append($("<td>").text(usuario.nombreCompleto || "-"));
    fila.append($("<td>").text(usuario.correo || "-"));
    fila.append($("<td>").text(usuario.status || "-"));
    fila.append($("<td>").text(usuario.rol || "-"));

    // Botón Editar
    fila.append($("<td>").append($("<button>").addClass("btn btn-info btn-sm").text("Editar").attr("onclick", 'editarUsuario("' + usuario.id + '")')));

    // Botón Visualizar
    fila.append($("<td>").append($("<button>").addClass("btn btn-info btn-sm").text("Visualizar").attr("onclick", 'visualizarUsuario("' + usuario.id + '")')));

    // Solo ADMINISTRADOR puede dar de baja y activar usuarios
    if (tipoUsuario === "ADMINISTRADOR") {
      fila.append($("<td>").append($("<button>").addClass("btn btn-danger btn-sm").text("Dar de Baja").attr("onclick", 'darDeBajaUsuario("' + usuario.id + '")')));

      if (usuario.status === "Baja") {
        fila.append($("<td>").append($("<button>").addClass("btn btn-success btn-sm").text("Activar").attr("onclick", 'activarUsuario("' + usuario.id + '")')));
      } else {
        fila.append($("<td>").text("-"));
      }
    }
    tablaClientes.append(fila);
  });
}

// Inicializar eventos de los botones de navegación
function inicializarEventosBotones() {
  $(".filtro-rol").off("click").on("click", function () {
    let rolSeleccionado = $(this).data("rol"); // Obtener el rol del botón
    $(".filtro-rol").removeClass("btn-primary").addClass("btn-secondary"); // Resetear colores de botones
    $(this).removeClass("btn-secondary").addClass("btn-primary"); // Resaltar botón seleccionado
    mostrarUsuarios(listaUsuarios, "ADMINISTRADOR", rolSeleccionado); // Filtrar la tabla
  });
}



function activarUsuario(usuarioId) {
  Swal.fire({
      title: "¿Estás seguro?",
      text: "Esta acción activará al usuario.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Sí, activar",
      cancelButtonText: "Cancelar",
  }).then((result) => {
      if (result.isConfirmed) {
          $.ajax({
              url: "../Servidor/PHP/usuarios.php", // Cambia la ruta si es necesario
              method: "POST",
              data: {
                  numFuncion: "12", // Identificador para activar usuario
                  usuarioId: usuarioId,
              },
              success: function (response) {
                  try {
                      const res = JSON.parse(response);
                      if (res.success) {
                          Swal.fire({
                              icon: "success",
                              title: "Éxito",
                              text: "El usuario ha sido activado.",
                              timer: 2000,
                              showConfirmButton: false,
                          }).then(() => {
                              location.reload();
                          });
                      } else {
                          Swal.fire({
                              icon: "error",
                              title: "Error",
                              text: res.message || "No se pudo activar al usuario.",
                          });
                      }
                  } catch (error) {
                      console.error("Error al procesar la respuesta:", error);
                      Swal.fire({
                          icon: "error",
                          title: "Error",
                          text: "Error al procesar la respuesta del servidor.",
                      });
                  }
              },
              error: function () {
                  Swal.fire({
                      icon: "error",
                      title: "Error",
                      text: "Error al realizar la solicitud.",
                  });
              },
          });
      }
  });
}
function darDeBajaUsuario(usuarioId) {
  Swal.fire({
      title: "¿Estás seguro?",
      text: "Esta acción marcará al usuario como dado de baja.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#3085d6",
      confirmButtonText: "Sí, dar de baja",
      cancelButtonText: "Cancelar",
  }).then((result) => {
      if (result.isConfirmed) {
          $.ajax({
              url: "../Servidor/PHP/usuarios.php", // Cambia la ruta si es necesario
              method: "POST",
              data: {
                  numFuncion: "11", // Identificador para dar de baja
                  usuarioId: usuarioId,
              },
              success: function (response) {
                  try {
                      const res = JSON.parse(response);
                      if (res.success) {
                        Swal.fire({
                            icon: "success",
                            title: "Éxito",
                            text: "El usuario ha sido dado de baja.",
                            timer: 2000,
                            showConfirmButton: false,
                        }).then(() => {
                            location.reload(); // Recargar la página después de que el mensaje se cierre
                        });
                    } else {
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: res.message || "No se pudo dar de baja al usuario.",
                        });
                    }
                  } catch (error) {
                      console.error("Error al procesar la respuesta:", error);
                      Swal.fire({
                          icon: "error",
                          title: "Error",
                          text: "Error al procesar la respuesta del servidor.",
                      });
                  }
              },
              error: function () {
                  Swal.fire({
                      icon: "error",
                      title: "Error",
                      text: "Error al realizar la solicitud.",
                  });
              },
          });
      }
  });
}
function visualizarAsociaciones(usuarioId) {
    // Realizar la solicitud AJAX para obtener las asociaciones del usuario
    $.ajax({
        url: "../Servidor/PHP/usuarios.php",
        method: "GET",
        data: { numFuncion: "10", usuarioId: usuarioId }, // Función para obtener asociaciones
        success: function (response) {
            try {
                const res =
                    typeof response === "string" ? JSON.parse(response) : response;

                if (res.success && Array.isArray(res.data)) {
                    const tablaAsociaciones = $("#tablaAsociaciones");
                    tablaAsociaciones.empty(); // Limpia la tabla antes de agregar las asociaciones

                    if (res.data.length > 0) {
                        res.data.forEach((asociacion) => {
                            const fila = `
                                <tr>
                                    <td>${asociacion.razonSocial}</td>
                                    <td>${asociacion.noEmpresa}</td>
                                </tr>
                            `;
                            tablaAsociaciones.append(fila);
                        });
                    } else {
                        const fila = `
                            <tr>
                                <td colspan="2" class="text-center">Sin asociaciones</td>
                            </tr>
                        `;
                        tablaAsociaciones.append(fila);
                    }

                    // Mostrar el modal
                    $("#verAsociacionesModal").modal("show");
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: res.message || "No se pudieron cargar las asociaciones.",
                    });
                }
            } catch (error) {
                console.error("Error al procesar la respuesta:", error);
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Error al procesar la respuesta del servidor.",
                });
            }
        },
        error: function () {
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "Error al realizar la solicitud.",
            });
        },
    });
}
function visualizarUsuario(idUsuario) {
  $.ajax({
    url: "../Servidor/PHP/usuarios.php", // Cambia esta ruta
    method: "GET",
    data: { numFuncion: "5", id: idUsuario },
    success: function (response) {
      try {
        const data = JSON.parse(response);

        if (data.success) {
          // Si la respuesta es exitosa, mostramos los datos en el modal
          $("#usuario").val(data.data.usuario);
          $("#nombreUsuario").val(data.data.nombre);
          $("#apellidosUsuario").val(data.data.apellido);
          $("#correoUsuario").val(data.data.correo);
          $("#contrasenaUsuario").val(data.data.password);
          $("#rolUsuario").val(data.data.tipoUsuario);
          $("#telefonoUsuario").val(data.data.telefono);
          $("#estatusUsuario").val(data.data.estatus);
          $("#idUsuario").val(idUsuario);

          // Bloquear los campos
          $("#usuario").prop("disabled", true);
          $("#nombreUsuario").prop("disabled", true);
          $("#apellidosUsuario").prop("disabled", true);
          $("#correoUsuario").prop("disabled", true);
          $("#contrasenaUsuario").prop("disabled", true);
          $("#rolUsuario").prop("disabled", true);
          $("#telefonoUsuario").prop("disabled", true);
          $("#estatusUsuario").prop("disabled", true);

          // Ocultar el botón de guardar cambios
          $("#guardarDatosBtn").hide();

          // Mostrar el modal
          $("#usuarioModal").modal("show");
        } else {
          alert(data.message); // En caso de error
        }
      } catch (e) {
        alert("Error al parsear la respuesta: " + e.message);
      }
    },
    error: function () {
      alert("Hubo un problema al obtener los datos del usuario.");
    },
  });
}

function editarUsuario(idUsuario) {
  $.ajax({
    url: "../Servidor/PHP/usuarios.php", // Cambia esta ruta
    method: "GET",
    data: { numFuncion: "5", id: idUsuario },
    success: function (response) {
      try {
        const data = JSON.parse(response);

        if (data.success) {
          // Si la respuesta es exitosa, mostramos los datos en el modal
          $("#usuario").val(data.data.usuario);
          $("#nombreUsuario").val(data.data.nombre);
          $("#apellidosUsuario").val(data.data.apellido);
          $("#correoUsuario").val(data.data.correo);
          $("#contrasenaUsuario").val(data.data.password);
          $("#rolUsuario").val(data.data.tipoUsuario);
          $("#telefonoUsuario").val(data.data.telefono);
          $("#estatusUsuario").val(data.data.estatus);
          $("#idUsuario").val(idUsuario);
          // Mostrar el modal
          $("#usuarioModal").modal("show");
        } else {
          alert(data.message); // En caso de error
        }
      } catch (e) {
        alert("Error al parsear la respuesta: " + e.message);
      }
    },
    error: function () {
      alert("Hubo un problema al obtener los datos del usuario.");
    },
  });
}
function cargarEmpresas() {
  $.ajax({
    url: "../Servidor/PHP/usuarios.php",
    method: "GET",
    data: { numFuncion: "4" }, // Función para obtener empresas
    success: function (response) {
      try {
        const res =
          typeof response === "string" ? JSON.parse(response) : response; // Asegúrate de que sea JSON

        if (res.success && Array.isArray(res.data)) {
          const selectEmpresa = $("#selectEmpresa");
          selectEmpresa.empty();
          selectEmpresa.append(
            "<option selected disabled>Seleccione una empresa</option>"
          );
          res.data.forEach((empresa) => {
            selectEmpresa.append(
              `<option value="${empresa.id}" data-noempresa="${empresa.noEmpresa}">${empresa.razonSocial}</option>`
            );
          });
        } else {
          alert(res.message || "No se pudieron cargar las empresas.");
        }
      } catch (error) {
        console.error("Error al procesar la respuesta:", error);
        alert("Error al cargar empresas.");
      }
    },
    error: function () {
      alert("Error al cargar empresas.");
    },
  });
}
function cargarUsuarios() {
  $.ajax({
    url: "../Servidor/PHP/usuarios.php",
    method: "GET",
    data: { numFuncion: "6" }, // Función para obtener usuarios
    success: function (response) {
      try {
        const res =
          typeof response === "string" ? JSON.parse(response) : response;

        if (res.success && Array.isArray(res.data)) {
          const selectUsuario = $("#selectUsuario");
          selectUsuario.empty();
          selectUsuario.append(
            "<option selected disabled>Seleccione un usuario</option>"
          );
          res.data.forEach((usuario) => {
            // Incluimos `data-usuario` para usarlo posteriormente
            selectUsuario.append(
              `<option value="${usuario.usuario}" data-usuario="${usuario.usuario}" data-id="${usuario.id}">${usuario.nombre}</option>`
            );
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
$("#btnGuardarAsociacion").on("click", function () {
  // Obtener los valores seleccionados
  const idEmpresa = $("#selectEmpresa").val(); // ID de la empresa seleccionada
  const razonSocial = $("#selectEmpresa option:selected").text(); // Razón social de la empresa
  const noEmpresa = $("#selectEmpresa option:selected").data("noempresa"); // Número de empresa
  const usuario = $("#selectUsuario option:selected").data("usuario"); // Valor del campo 'usuario'

  // Validar que todos los campos estén seleccionados
  if (!idEmpresa || !usuario) {
    Swal.fire({
      icon: "warning",
      title: "Campos faltantes",
      text: "Por favor, seleccione un usuario y una empresa.",
    });
    return;
  }

  // Realizar la solicitud AJAX para guardar los datos
  $.ajax({
    url: "../Servidor/PHP/usuarios.php", // Ruta al PHP
    method: "POST",
    data: {
      numFuncion: "7", // Identificador para guardar la asociación
      empresa: razonSocial,
      id: idEmpresa,
      noEmpresa: noEmpresa,
      usuario: usuario,
    },
    success: function (response) {
      try {
        const res = JSON.parse(response);
        if (res.success) {
          Swal.fire({
            icon: "success",
            title: "Éxito",
            text: "Asociación guardada exitosamente.",
            timer: 2000,
            showConfirmButton: false,
          });
          $("#selectUsuario").val("").change(); // Limpia el selector de usuarios
          $("#selectEmpresa").val(""); // Limpia el selector de empresas
          $("#listaEmpresasAsociadas")
            .empty()
            .append('<li class="list-group-item">Sin asociaciones</li>'); // Limpia la lista de empresas asociadas
          $("#asociarEmpresaModal").modal("hide"); // Cerrar el modal
          //location.reload();
        } else {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: res.message || "Error al guardar la asociación.",
          });
        }
      } catch (error) {
        console.error("Error al procesar la respuesta:", error);
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Error al guardar la asociación.",
        });
      }
    },
    error: function () {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Error al realizar la solicitud.",
      });
    },
  });
});
$("#selectUsuario").on("change", function () {
  const usuario = $(this).find(":selected").data("usuario"); // Obtener el valor de `data-usuario`

  if (!usuario) {
    $("#listaEmpresasAsociadas").empty(); // Limpia la lista si no hay usuario seleccionado
    return;
  }

  // Solicitar empresas asociadas al servidor
  $.ajax({
    url: "../Servidor/PHP/usuarios.php",
    method: "GET",
    data: { numFuncion: "8", usuarioId: usuario }, // Enviar el campo `usuario` como usuarioId
    success: function (response) {
      try {
        const res =
          typeof response === "string" ? JSON.parse(response) : response;

        if (res.success && Array.isArray(res.data)) {
          const listaEmpresas = $("#listaEmpresasAsociadas");
          listaEmpresas.empty();

          if (res.data.length > 0) {
            res.data.forEach((empresa) => {
              listaEmpresas.append(`
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  ${empresa.razonSocial} (No. Empresa: ${empresa.noEmpresa})
                  <button 
                    class="btn btn-danger btn-sm btnEliminarAsociacion" 
                    data-id="${empresa.id}" 
                    data-usuario="${usuario}">Eliminar</button>
                </li>
              `);
            });
          } else {
            listaEmpresas.append(
              '<li class="list-group-item">Sin asociaciones</li>'
            );
          }
        } else {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: res.message || "No se pudieron cargar las empresas asociadas.",
          });
        }
      } catch (error) {
        console.error("Error al procesar la respuesta:", error);
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Error al cargar las empresas asociadas.",
        });
      }
    },
    error: function () {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Error al realizar la solicitud.",
      });
    },
  });
});

$("#listaEmpresasAsociadas").on("click", ".btnEliminarAsociacion", function () {
  const idAsociacion = $(this).data("id"); // ID del documento en Firestore
  const usuario = $(this).data("usuario"); // Usuario asociado

  if (!idAsociacion || !usuario) {
    Swal.fire({
      icon: "error",
      title: "Error",
      text: "No se pudo identificar la asociación o el usuario.",
    });
    return;
  }

  // Confirmar eliminación con SweetAlert2
  Swal.fire({
    title: "¿Está seguro?",
    text: "Esta acción eliminará la asociación permanentemente.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Eliminar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      // Realizar la solicitud AJAX para eliminar el documento
      $.ajax({
        url: "../Servidor/PHP/usuarios.php",
        method: "POST",
        data: {
          numFuncion: "9", // Identificador para eliminar asociación
          id: idAsociacion,
          usuario: usuario, // Enviar también el usuario
        },
        success: function (response) {
          try {
            const res = JSON.parse(response);
            if (res.success) {
              Swal.fire({
                icon: "success",
                title: "Eliminada",
                text: "Asociación eliminada exitosamente.",
                timer: 2000,
                showConfirmButton: false,
              });
              $("#selectUsuario").trigger("change"); // Recargar las asociaciones
            } else {
              Swal.fire({
                icon: "error",
                title: "Error",
                text: res.message || "Error al eliminar la asociación.",
              });
            }
          } catch (error) {
            console.error("Error al procesar la respuesta:", error);
            Swal.fire({
              icon: "error",
              title: "Error",
              text: "Error al procesar la respuesta del servidor.",
            });
          }
        },
        error: function () {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: "Error al realizar la solicitud.",
          });
        },
      });
    }
  });
});

function limpiarFormulario() {
  $("#idUsuario").val("");
  $("#usuario").val("");
  $("#nombreUsuario").val("");
  $("#apellidosUsuario").val("");
  $("#correoUsuario").val("");
  $("#contrasenaUsuario").val("");
  $("#telefonoUsuario").val("");
  $("#rolUsuario").val(""); // Si es un select, también se debe resetear
  $("#selectEmpresa").val(""); // Si es un select, también se debe resetear
  $("#detallesEmpresa").val(""); // Limpiar el textarea
}
// Función para abrir el modal
document.getElementById("btnAgregar").addEventListener("click", () => {
  limpiarFormulario();
  // Usar las funciones de Bootstrap para abrir el modal
  $("#usuarioModal").modal("show");
  // Eliminar el aria-hidden cuando se muestra el modal
  $("#usuarioModal").removeAttr("aria-hidden");
  // Añadir el atributo inert al fondo para evitar que los elementos detrás sean interactivos
  $(".modal-backdrop").attr("inert", true);
});

function cerrarModal() {
  limpiarFormulario();
  $("#usuarioModal").modal("hide"); // Cierra el modal usando Bootstrap
  // Restaurar el aria-hidden al cerrar el modal
  $("#usuarioModal").attr("aria-hidden", "true");
  // Eliminar el atributo inert del fondo al cerrar
  $(".modal-backdrop").removeAttr("inert");
}

$(document).ready(function () {
  $("#btnAsociarEmpresa").on("click", function () {
    // Obtener usuarios y empresas
    cargarUsuarios();
    cargarEmpresas();

    // Mostrar el modal
    $("#asociarEmpresaModal").modal("show");
  });
  $("#cerrarModalAsociasionHeader").on("click", function () {
    $("#asociarEmpresaModal").modal("hide"); // Cierra el modal
    $("#selectUsuario").val("").change(); // Limpia el selector de usuarios
    $("#selectEmpresa").val(""); // Limpia el selector de empresas
    $("#listaEmpresasAsociadas")
      .empty()
      .append('<li class="list-group-item">Sin asociaciones</li>'); // Limpia la lista de empresas asociadas
  });
  $("#cerrarModalAsociasionFooter").on("click", function () {
    $("#asociarEmpresaModal").modal("hide"); // Cierra el modal
    $("#selectUsuario").val("").change(); // Limpia el selector de usuarios
    $("#selectEmpresa").val(""); // Limpia el selector de empresas
    $("#listaEmpresasAsociadas")
      .empty()
      .append('<li class="list-group-item">Sin asociaciones</li>'); // Limpia la lista de empresas asociadas
  });
  $('#verAsociacionesModal').on('hidden.bs.modal', function () {
    // Limpiar la tabla de asociaciones
    $('#tablaAsociaciones').empty();
});
$('.btn-close').on('click', function () {
    $('#verAsociacionesModal').modal('hide'); // Cierra el modal
});
$('#cerrarModalVisAsoFooter').on('click', function () {
    $('#verAsociacionesModal').modal('hide'); // Cierra el modal
    $('#tablaAsociaciones').empty();
});

  $("#cerrarModalHeader").on("click", cerrarModal);
  $("#cerrarModalFooter").on("click", cerrarModal);
  /*$('#agregarEmpresaBtn').on('click', function () {
        const selectedOption = $('#selectEmpresa').find('option:selected'); // Obtener la opción seleccionada
        // Verificar que se haya seleccionado una empresa válida
        if (selectedOption.val() === null || selectedOption.val() === "Selecciona una empresa") {
            alert('Por favor, selecciona una empresa antes de agregar.');
            return;
        }

        // Extraer los atributos de la opción seleccionada
        const razonSocial = selectedOption.data('razon-social');
        const idEmpresa = selectedOption.data('id');
        const numeroEmpresa = selectedOption.data('numero');

        // Verificar si la empresa ya fue agregada
        const existe = $(`#tablaEmpresas tbody tr[data-id="${idEmpresa}"]`).length > 0;
        if (existe) {
            alert('Esta empresa ya ha sido agregada.');
            return;
        }

        // Crear una nueva fila con los datos de la empresa
        const nuevaFila = `
            <tr data-id="${idEmpresa}">
                <td>${idEmpresa}</td>
                <td>${razonSocial}</td>
                <td>${numeroEmpresa}</td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm eliminarEmpresaBtn">Eliminar</button>
                </td>
            </tr>
        `;
        // Agregar la fila a la tabla
        $('#tablaEmpresas tbody').append(nuevaFila);
    });*/

  // Detectar clic en los botones "Eliminar" dentro de la tabla
  $("#tablaEmpresas").on("click", ".eliminarEmpresaBtn", function () {
    // Eliminar la fila correspondiente
    $(this).closest("tr").remove();
  });

  $("#guardarDatosBtn").on("click", function (e) {
    e.preventDefault(); // Evitar que el formulario recargue la página
    // Obtener los valores del formulario
    const idUsuario = $("#idUsuario").val();
    const usuario = $("#usuario").val();
    const nombreUsuario = $("#nombreUsuario").val();
    const apellidosUsuario = $("#apellidosUsuario").val();
    const correoUsuario = $("#correoUsuario").val();
    const contrasenaUsuario = $("#contrasenaUsuario").val();
    const telefonoUsuario = $("#telefonoUsuario").val();
    const rolUsuario = $("#rolUsuario").val();

    // Validar que todos los campos requeridos estén completos
    if (
      !usuario ||
      !nombreUsuario ||
      !apellidosUsuario ||
      !correoUsuario ||
      !contrasenaUsuario ||
      !telefonoUsuario ||
      !rolUsuario
    ) {
      alert("Por favor, complete todos los campos.");
      return;
    }
    // Enviar los datos al servidor mediante AJAX
    $.ajax({
      url: "../Servidor/PHP/usuarios.php",
      type: "POST",
      data: {
        numFuncion: 1, // Identificador para la función en el servidor
        idUsuario: idUsuario,
        usuario: usuario,
        nombreUsuario: nombreUsuario,
        apellidosUsuario: apellidosUsuario,
        correoUsuario: correoUsuario,
        contrasenaUsuario: contrasenaUsuario,
        telefonoUsuario: telefonoUsuario,
        rolUsuario: rolUsuario,
      },
      success: function (response) {
        const res = JSON.parse(response);
        if (res.success) {
          //alert('Usuario guardado exitosamente.');
          Swal.fire({
            text: "Usuario guardado exitosamente.",
            icon: "success",
          });
          // Cerrar el modal y limpiar el formulario
          $("#usuarioModal").modal("hide");
          $("#agregarUsuarioForm")[0].reset();

          // Recargar la tabla de usuarios (llama a tu función para mostrar usuarios)
          location.reload();
        } else {
          //alert(res.message || 'Error al guardar el usuario.');
          Swal.fire({
            title: "Eror",
            text: res.message,
            icon: "error",
          });
        }
      },
      error: function () {
        alert("Error al realizar la solicitud.");
      },
    });
  });
});
