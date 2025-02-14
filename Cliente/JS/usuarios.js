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
//console.log(mostrarUsuarios);
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
  let usuariosFiltrados = usuarios.filter((usuario) =>
    rolSeleccionado === "TODOS" ? true : usuario.rol === rolSeleccionado
  );

  // Ordenar alfabéticamente
  usuariosFiltrados.sort((a, b) =>
    (a.nombreCompleto || "").localeCompare(b.nombreCompleto || "")
  );

  // Generar filas
  usuariosFiltrados.forEach(function (usuario) {
    var fila = $("<tr>");
    fila.append($("<td>").text(usuario.nombreCompleto || "-"));
    fila.append($("<td>").text(usuario.correo || "-"));
    fila.append($("<td>").text(usuario.status || "-"));
    fila.append($("<td>").text(usuario.rol || "-"));

    // Botón Editar
    fila.append(
      $("<td>").append(
        $("<button>")
          .addClass("btn btn-info btn-sm")
          .text("Editar")
          .attr("onclick", 'editarUsuario("' + usuario.id + '")')
      )
    );

    // Botón Visualizar
    fila.append(
      $("<td>").append(
        $("<button>")
          .addClass("btn btn-info btn-sm")
          .text("Visualizar")
          .attr("onclick", 'visualizarUsuario("' + usuario.id + '")')
      )
    );

    // Solo ADMINISTRADOR puede dar de baja y activar usuarios
    if (tipoUsuario === "ADMINISTRADOR") {
      fila.append(
        $("<td>").append(
          $("<button>")
            .addClass("btn btn-danger btn-sm")
            .text("Dar de Baja")
            .attr("onclick", 'darDeBajaUsuario("' + usuario.id + '")')
        )
      );

      if (usuario.status === "Baja") {
        fila.append(
          $("<td>").append(
            $("<button>")
              .addClass("btn btn-success btn-sm")
              .text("Activar")
              .attr("onclick", 'activarUsuario("' + usuario.id + '")')
          )
        );
      } else {
        fila.append($("<td>").text("-"));
      }
    }
    tablaClientes.append(fila);
  });
}

// Inicializar eventos de los botones de navegación
function inicializarEventosBotones() {
  $(".filtro-rol")
    .off("click")
    .on("click", function () {
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
    url: "../Servidor/PHP/usuarios.php",
    method: "GET",
    data: { numFuncion: "5", id: idUsuario },
    success: function (response) {
      try {
        const data = JSON.parse(response);
        if (data.success) {
          if (data.data.tipoUsuario === "CLIENTE") {
            $("#usuarioCliente").val(data.data.usuario);
            $("#claveUsuarioCliente").val(data.data.claveUsuario);
            $("#nombreUsuarioCliente").val(data.data.nombre);
            $("#correoUsuarioCliente").val(data.data.correo);
            $("#contrasenaUsuarioCliente").val(data.data.password);
            $("#telefonoUsuarioCliente").val(data.data.telefono);
            $("#idUsuarioCliente").val(idUsuario);

            // Llamar a la función 17 para obtener los datos del cliente en el select
            $.ajax({
              url: "../Servidor/PHP/usuarios.php",
              method: "GET",
              data: {
                numFuncion: "17",
                claveCliente: data.data.claveUsuario,
              },
              success: function (responseCliente) {
                try {
                  const resCliente = JSON.parse(responseCliente);
                  if (resCliente.success && resCliente.data) {
                    $("#selectCliente").empty();
                    $("#selectCliente").append(
                      `<option value="${resCliente.data.clave}" selected>
                        ${resCliente.data.nombre} || ${resCliente.data.clave}
                      </option>`
                    );
                  }
                } catch (error) {
                  console.error(
                    "Error al procesar la respuesta del cliente:",
                    error
                  );
                }
              },
            });

            $("#usuarioModalCliente").modal("show");
            $(
              "#claveUsuarioCliente, #usuarioCliente, #nombreUsuarioCliente, #correoUsuarioCliente, #contrasenaUsuarioCliente, #telefonoUsuarioCliente, #idUsuarioCliente, #selectCliente"
            ).prop("disabled", true);
          } else {
            $("#usuario").val(data.data.usuario);
            $("#nombreUsuario").val(data.data.nombre);
            $("#apellidosUsuario").val(data.data.apellido);
            $("#correoUsuario").val(data.data.correo);
            $("#contrasenaUsuario").val(data.data.password);
            $("#rolUsuario").val(data.data.tipoUsuario);
            $("#telefonoUsuario").val(data.data.telefono);
            $("#estatusUsuario").val(data.data.estatus);
            $("#idUsuario").val(idUsuario);

            // Si es VENDEDOR, hacer consulta para obtener su nombre
            if (data.data.tipoUsuario === "VENDEDOR") {
              $("#divVendedor").show();
              $("#selectVendedor").empty();

              // Llamada AJAX para obtener la información del vendedor por clave
              $.ajax({
                url: "../Servidor/PHP/usuarios.php",
                method: "GET",
                data: {
                  numFuncion: "14",
                  claveVendedor: data.data.claveVendedor,
                }, // Nueva función para buscar por clave
                success: function (responseVendedor) {
                  try {
                    const resVendedor = JSON.parse(responseVendedor);
                    if (resVendedor.success && resVendedor.data) {
                      $("#selectVendedor").append(
                        `<option value="${data.data.claveVendedor}" selected>
                          ${resVendedor.data.nombre} || ${data.data.claveVendedor}
                      </option>`
                      );
                    }
                  } catch (error) {
                    console.error(
                      "Error al procesar la respuesta del vendedor:",
                      error
                    );
                  }
                },
              });
            } else {
              $("#divVendedor").hide();
            }

            $("#usuarioModal").modal("show");
            $(
              "#usuario, #nombreUsuario, #apellidosUsuario, #correoUsuario, #contrasenaUsuario, #rolUsuario, #telefonoUsuario, #estatusUsuario, #selectVendedor"
            ).prop("disabled", true);
          }
        } else {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: data.message || "No se pudo cargar el usuario.",
          });
        }
      } catch (e) {
        console.error("Error al parsear la respuesta:", e);
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Hubo un error al procesar la respuesta del servidor.",
        });
      }
    },
    error: function () {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Hubo un problema al obtener los datos del usuario.",
      });
    },
  });
}
function editarUsuario(idUsuario) {
  $.ajax({
    url: "../Servidor/PHP/usuarios.php",
    method: "GET",
    data: { numFuncion: "5", id: idUsuario }, // Obtener los datos del usuario
    success: function (response) {
      try {
        const data = JSON.parse(response);
        if (data.success) {
          if (data.data.tipoUsuario === "CLIENTE") {
            $("#usuarioCliente").val(data.data.usuario);
            $("#claveUsuarioCliente").val(data.data.claveUsuario);
            $("#nombreUsuarioCliente").val(data.data.nombre);
            $("#correoUsuarioCliente").val(data.data.correo);
            $("#contrasenaUsuarioCliente").val(data.data.password);
            $("#telefonoUsuarioCliente").val(data.data.telefono);
            $("#idUsuarioCliente").val(idUsuario);
            
            $.ajax({
              url: "../Servidor/PHP/usuarios.php",
              method: "GET",
              data: { numFuncion: "15" }, // Obtener todos los clientes disponibles
              success: function (responseClientes) {
                try {
                  const resClientes =
                    typeof responseClientes === "string"
                      ? JSON.parse(responseClientes)
                      : responseClientes;

                  if (resClientes.success && Array.isArray(resClientes.data)) {
                    const selectCliente = $("#selectCliente");
                    selectCliente.empty();
                    selectCliente.append(
                      "<option selected disabled>Selecciona un Cliente</option>"
                    );

                    resClientes.data.forEach((cliente) => {
                      selectCliente.append(
                        `<option value="${cliente.clave}" 
                          data-nombre="${cliente.nombre}" 
                          data-correo="${cliente.correo || ""}" 
                          data-telefono="${cliente.telefono || ""}">
                          ${cliente.nombre} || ${cliente.clave}
                        </option>`
                      );
                    });

                    // ✅ Seleccionar automáticamente el cliente del usuario editado
                    selectCliente.val(data.data.claveUsuario);
                  } else {
                    Swal.fire({
                      icon: "warning",
                      title: "Aviso",
                      text: resClientes.message || "No se encontraron clientes.",
                    });
                    $("#selectCliente").prop("disabled", true);
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

            // Habilitar los campos para edición
            $("#claveUsuarioCliente, #usuarioCliente, #nombreUsuarioCliente, #correoUsuarioCliente, #contrasenaUsuarioCliente, #telefonoUsuarioCliente, #idUsuarioCliente, #selectCliente").prop("disabled", false);

            $("#usuarioModalCliente").modal("show");
          } else {
            // Habilitar todos los campos para edición
            $(
              "#usuario, #nombreUsuario, #apellidosUsuario, #correoUsuario, #contrasenaUsuario, #rolUsuario, #telefonoUsuario, #estatusUsuario, #selectVendedor"
            ).prop("disabled", false);

            // Asignar valores del usuario
            $("#usuario").val(data.data.usuario);
            $("#nombreUsuario").val(data.data.nombre);
            $("#apellidosUsuario").val(data.data.apellido);
            $("#correoUsuario").val(data.data.correo);
            $("#contrasenaUsuario").val(data.data.password);
            $("#rolUsuario").val(data.data.tipoUsuario);
            $("#telefonoUsuario").val(data.data.telefono);
            $("#estatusUsuario").val(data.data.estatus);
            $("#idUsuario").val(idUsuario);

            // Si el usuario es VENDEDOR, cargar los vendedores y luego obtener su clave
            if (data.data.tipoUsuario === "VENDEDOR") {
              $("#divVendedor").show();

              // Cargar la lista de vendedores
              $.ajax({
                url: "../Servidor/PHP/usuarios.php",
                method: "GET",
                data: { numFuncion: "13" }, // Obtener todos los vendedores disponibles
                success: function (responseVendedores) {
                  console.log(
                    "Respuesta del servidor (vendedores):",
                    responseVendedores
                  ); // DEBUG

                  try {
                    const res =
                      typeof responseVendedores === "string"
                        ? JSON.parse(responseVendedores)
                        : responseVendedores;

                    if (res.success && Array.isArray(res.data)) {
                      const selectVendedor = $("#selectVendedor");
                      selectVendedor.empty();
                      selectVendedor.append(
                        "<option selected disabled>Seleccione un vendedor</option>"
                      );

                      res.data.forEach((vendedor) => {
                        selectVendedor.append(
                          `<option value="${vendedor.clave}">${vendedor.nombre} || ${vendedor.clave}</option>`
                        );
                      });

                      // ✅ Ahora obtenemos la clave del vendedor y la seleccionamos correctamente
                      obtenerClaveVendedor(data.data.claveVendedor);
                    } else {
                      Swal.fire({
                        icon: "warning",
                        title: "Aviso",
                        text: res.message || "No se encontraron vendedores.",
                      });
                      $("#selectVendedor").prop("disabled", true);
                    }
                  } catch (error) {
                    console.error("Error al procesar los vendedores:", error);
                  }
                },
                error: function () {
                  Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Error al obtener la lista de vendedores.",
                  });
                },
              });
            } else {
              $("#divVendedor").hide();
            }

            // Mostrar modal de edición
            $("#usuarioModal").modal("show");
          }
        } else {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: data.message || "No se pudo cargar el usuario.",
          });
        }
      } catch (e) {
        console.error("Error al parsear la respuesta:", e);
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Hubo un error al procesar la respuesta del servidor.",
        });
      }
    },
    error: function () {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Hubo un problema al obtener los datos del usuario.",
      });
    },
  });
}

/**
 * Obtiene la clave del vendedor del usuario y la asigna al select usando la función 14 en PHP
 */
function obtenerClaveVendedor(idUsuario) {
  $.ajax({
    url: "../Servidor/PHP/usuarios.php",
    method: "GET",
    data: { numFuncion: "14", claveVendedor: idUsuario }, // Nueva función para buscar por clave
    success: function (responseVendedor) {
      try {
        const data = JSON.parse(responseVendedor);
        if (data.success) {
          console.log(data.data.clave);
          const claveVendedor = data.data.clave;

          // ✅ Asignar la clave del vendedor al select
          $("#selectVendedor").val(claveVendedor);
        }
      } catch (error) {
        console.error("Error al procesar la respuesta del vendedor:", error);
      }
    },
  });
}
/*function cargarEmpresas() {
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
}*/
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
              `<option value="${usuario.usuario}" data-usuario="${usuario.usuario}" data-id="${usuario.id}" data-claveVendedor="${usuario.claveVendedor}">${usuario.nombre}</option>`
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
  const claveVendedor = $("#selectUsuario option:selected").attr(
    "data-claveVendedor"
  );

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
      claveVendedor: claveVendedor,
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

$("#listaEmpresasAsociadas").on("click", ".btnEliminarAsociacion", function () {
  const idAsociacion = $(this).data("id"); // ID del documento en Firestore
  //const usuario = $(this).data("usuario"); // Usuario asociado
  const usuario = $("#selectUsuario option:selected").data("usuario");
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
  $("#selectVendedor").val(""); // Limpiar el textarea
  $(
    "#usuario, #nombreUsuario, #apellidosUsuario, #correoUsuario, #contrasenaUsuario, #rolUsuario, #telefonoUsuario, #estatusUsuario, #selectVendedor"
  ).prop("disabled", false);
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
// Función para obtener los clientes y llenar el select
function obtenerClientes() {
  $.ajax({
    url: "../Servidor/PHP/usuarios.php",
    type: "GET",
    data: { numFuncion: "15" }, // Llamar a la función 15 en PHP
    success: function (response) {
      try {
        const res =
          typeof response === "string" ? JSON.parse(response) : response;

        if (res.success) {
          console.log("Clientes obtenidos: ", res);

          // Llenar el select de clientes
          const selectCliente = $("#selectCliente");
          selectCliente.empty();
          selectCliente.append(
            "<option selected disabled>Selecciona un Cliente</option>"
          );

          res.data.forEach((cliente) => {
            selectCliente.append(
              `<option value="${cliente.clave}" 
                data-nombre="${cliente.nombre}" 
                data-correo="${cliente.correo || ""}" 
                data-telefono="${cliente.telefono || ""}">
                ${cliente.nombre} || ${cliente.clave}
              </option>`
            );
          });
        } else {
          Swal.fire({ title: "Error", text: res.message, icon: "error" });
        }
      } catch (error) {
        console.error("Error al procesar la respuesta de clientes:", error);
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
// Evento para llenar los datos del formulario cuando se selecciona un cliente
$("#selectCliente").on("change", function () {
  const clienteSeleccionado = $(this).find(":selected");

  if (clienteSeleccionado.val()) {
    const claveCliente = clienteSeleccionado.val();

    validarCliente(claveCliente, function (existe) {
      if (!existe) {
        // Si no existe, llenamos los campos
        $("#claveUsuarioCliente").val(clienteSeleccionado.val());
        $("#nombreUsuarioCliente").val(clienteSeleccionado.data("nombre"));
        $("#correoUsuarioCliente").val(clienteSeleccionado.data("correo"));
        $("#telefonoUsuarioCliente").val(clienteSeleccionado.data("telefono"));
      } else {
        // Si ya existe, mostramos un error
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Cliente ya existente en Firebase.",
        });

        // Limpiamos los campos
        $("#selectCliente").val("");
        $("#claveUsuarioCliente").val("");
        $("#nombreUsuarioCliente").val("");
        $("#correoUsuarioCliente").val("");
        $("#telefonoUsuarioCliente").val("");
      }
    });
  }
});
function validarCliente(claveCliente, callback) {
  $.ajax({
    url: "../Servidor/PHP/usuarios.php",
    method: "POST",
    data: { numFuncion: "16", claveCliente: claveCliente }, // Llamamos la función PHP
    success: function (response) {
      try {
        const res = JSON.parse(response);
        console.log("Validación de cliente:", res); // Depuración

        if (res.success) {
          callback(res.exists); // Devuelve true si el cliente ya existe, false si no existe
        } else {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: res.message || "Error al validar el cliente.",
          });
          callback(false);
        }
      } catch (error) {
        console.error("Error al procesar la validación del cliente:", error);
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Error en la validación del cliente.",
        });
        callback(false);
      }
    },
    error: function () {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "No se pudo verificar el cliente en Firebase.",
      });
      callback(false);
    },
  });
}

// Evento para abrir el modal y obtener los clientes
$("#btnAgregarCliente").on("click", function () {
  limpiarFormulario(); // Limpia el formulario antes de abrir
  $("#usuarioModalCliente").modal("show"); // Mostrar modal
  obtenerClientes(); // Llamar a la función para obtener clientes
});
function cerrarModal() {
  limpiarFormulario();
  $("#usuarioModal").modal("hide"); // Cierra el modal usando Bootstrap
  // Restaurar el aria-hidden al cerrar el modal
  $("#usuarioModal").attr("aria-hidden", "true");
  // Eliminar el atributo inert del fondo al cerrar
  $(".modal-backdrop").removeAttr("inert");
}
function cerrarModalCliente() {
  limpiarFormulario();
  $("#usuarioModalCliente").modal("hide"); // Cierra el modal usando Bootstrap
  // Restaurar el aria-hidden al cerrar el modal
  $("#usuarioModalCliente").attr("aria-hidden", "true");
  // Eliminar el atributo inert del fondo al cerrar
  $(".modal-backdrop").removeAttr("inert");
}
$(document).ready(function () {
  $("#btnAsociarEmpresa").on("click", function () {
    // Obtener usuarios y empresas
    cargarUsuarios();
    //cargarEmpresas();

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
  $("#verAsociacionesModal").on("hidden.bs.modal", function () {
    // Limpiar la tabla de asociaciones
    $("#tablaAsociaciones").empty();
  });
  $(".btn-close").on("click", function () {
    $("#verAsociacionesModal").modal("hide"); // Cierra el modal
  });
  $("#cerrarModalVisAsoFooter").on("click", function () {
    $("#verAsociacionesModal").modal("hide"); // Cierra el modal
    $("#tablaAsociaciones").empty();
  });

  $("#cerrarModalHeader").on("click", cerrarModal);
  $("#cerrarModalFooter").on("click", cerrarModal);
  $("#cerrarModalHeaderCliente").on("click", cerrarModalCliente);
  $("#cerrarModalFooterCliente").on("click", cerrarModalCliente);
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
    const claveVendedor = $("#selectVendedor").val();

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
        claveVendedor: claveVendedor,
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
  $("#guardarDatosClienteBtn").on("click", function (e) {
    e.preventDefault(); // Evitar que el formulario recargue la página
    // Obtener los valores del formulario
    const idUsuario = $("#idUsuarioCliente").val();
    const usuario = $("#usuarioCliente").val();
    const nombreUsuario = $("#nombreUsuarioCliente").val();
    const correoUsuario = $("#correoUsuarioCliente").val();
    const contrasenaUsuario = $("#contrasenaUsuarioCliente").val();
    const telefonoUsuario = $("#telefonoUsuarioCliente").val();
    const claveCliente = $("#claveUsuarioCliente").val();

    // Validar que todos los campos requeridos estén completos
    if (
      !usuario ||
      !nombreUsuario ||
      !correoUsuario ||
      !contrasenaUsuario ||
      !telefonoUsuario
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
        apellidosUsuario: "",
        correoUsuario: correoUsuario,
        contrasenaUsuario: contrasenaUsuario,
        telefonoUsuario: telefonoUsuario,
        rolUsuario: "CLIENTE",
        claveVendedor: "",
        claveCliente: claveCliente,
      },
      success: function (response) {
        const res = JSON.parse(response);
        if (res.success) {
          Swal.fire({
            text: "Usuario guardado exitosamente.",
            icon: "success",
            timer: 2000, // Cierra automáticamente después de 2 segundos
            showConfirmButton: false,
          }).then(() => {
            // Cerrar el modal
            $("#usuarioModalCliente").modal("hide");

            // Limpiar el formulario
            $("#agregarUsuarioClienteForm")[0].reset();

            // Refrescar la lista de usuarios sin recargar la página
            location.reload(); // Asegúrate de que esta función está definida y actualiza la lista de usuarios
          });
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
  /*********************************************************************************/
  $("#selectEmpresa").prop("disabled", true);
  $("#rolUsuario").on("change", function () {
    const rolSeleccionado = $(this).val(); // Obtener el rol seleccionado

    if (rolSeleccionado === "VENDEDOR") {
      // Mostrar el select de vendedores
      $("#divVendedor").show();

      // Realizar la solicitud AJAX para obtener los vendedores
      $.ajax({
        url: "../Servidor/PHP/usuarios.php",
        method: "GET",
        data: { numFuncion: "13" }, // Llamar la función para obtener vendedores
        success: function (response) {
          try {
            const res =
              typeof response === "string" ? JSON.parse(response) : response;

            if (res.success && Array.isArray(res.data)) {
              const selectVendedor = $("#selectVendedor");
              selectVendedor.empty();
              selectVendedor.append(
                "<option selected disabled>Seleccione un vendedor</option>"
              );

              res.data.forEach((vendedor) => {
                selectVendedor.append(
                  `<option value="${vendedor.clave}" data-nombre="${vendedor.nombre}">
                                    ${vendedor.nombre} || ${vendedor.clave}
                                </option>`
                );
              });

              // Habilitar el select si hay vendedores disponibles
              selectVendedor.prop("disabled", res.data.length === 0);
            } else {
              Swal.fire({
                icon: "warning",
                title: "Aviso",
                text: res.message || "No se encontraron vendedores.",
              });
              $("#selectVendedor").prop("disabled", true);
            }
          } catch (error) {
            console.error("Error al procesar la respuesta:", error);
            Swal.fire({
              icon: "error",
              title: "Error",
              text: "Error al cargar vendedores.",
            });
          }
        },
        error: function () {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: "Error al obtener la lista de vendedores.",
          });
        },
      });
    } else {
      // Ocultar el div del vendedor si no es "VENDEDOR"
      $("#divVendedor").hide();
      $("#selectVendedor").empty().prop("disabled", true);
    }
  });
  // Cuando se seleccione un vendedor, solo se mostrará la clave en el input
  $("#selectVendedor").on("change", function () {
    const claveSeleccionada = $(this).val();
    $("#selectVendedor").val(claveSeleccionada);
  });

  $("#selectUsuario").on("change", function () {
    const usuario = $(this).find(":selected").data("usuario"); // Obtener el valor de `data-usuario`
    if (!usuario) {
      $("#selectEmpresa")
        .empty()
        .append("<option selected disabled>Seleccione una empresa</option>")
        .prop("disabled", true);
      $("#listaEmpresasAsociadas").empty(); // Limpiar asociaciones
      return;
    }

    // Obtener las asociaciones del usuario
    $.ajax({
      url: "../Servidor/PHP/usuarios.php",
      method: "GET",
      data: { numFuncion: "8", usuarioId: usuario },
      success: function (response) {
        try {
          const res =
            typeof response === "string" ? JSON.parse(response) : response;

          if (res.success && Array.isArray(res.data)) {
            const empresasAsociadas = res.data.map(
              (empresa) => empresa.noEmpresa
            ); // Obtener noEmpresa de asociaciones

            // Cargar empresas disponibles (filtrando las que no están asociadas)
            $.ajax({
              url: "../Servidor/PHP/usuarios.php",
              method: "GET",
              data: { numFuncion: "4", usuarioId: usuario }, // Obtener todas las empresas
              success: function (responseEmp) {
                try {
                  const resEmp =
                    typeof responseEmp === "string"
                      ? JSON.parse(responseEmp)
                      : responseEmp;

                  if (resEmp.success && Array.isArray(resEmp.data)) {
                    const selectEmpresa = $("#selectEmpresa");
                    selectEmpresa.empty();
                    selectEmpresa.append(
                      "<option selected disabled>Seleccione una empresa</option>"
                    );

                    resEmp.data.forEach((empresa) => {
                      if (!empresasAsociadas.includes(empresa.noEmpresa)) {
                        selectEmpresa.append(
                          `<option value="${empresa.id}" data-noempresa="${empresa.noEmpresa}">${empresa.razonSocial}</option>`
                        );
                      }
                    });

                    // Habilitar el select si hay empresas disponibles
                    if (selectEmpresa.children("option").length > 1) {
                      selectEmpresa.prop("disabled", false);
                    } else {
                      selectEmpresa.append(
                        "<option disabled>No hay empresas disponibles</option>"
                      );
                      selectEmpresa.prop("disabled", true);
                    }
                  } else {
                    alert(
                      resEmp.message || "No se pudieron cargar las empresas."
                    );
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

            // Mostrar empresas asociadas
            const listaEmpresas = $("#listaEmpresasAsociadas");
            listaEmpresas.empty();
            if (res.data.length > 0) {
              res.data.forEach((empresa) => {
                listaEmpresas.append(
                  `<li class="list-group-item d-flex justify-content-between align-items-center">
                                        ${empresa.razonSocial} (No. Empresa: ${empresa.noEmpresa})
                                        <button class="btn btn-danger btn-sm btnEliminarAsociacion" data-id="${empresa.id}">
                                            Eliminar
                                        </button>
                                    </li>`
                );
              });
            } else {
              listaEmpresas.append(
                '<li class="list-group-item">Sin asociaciones</li>'
              );
            }
          } else {
            alert(
              res.message || "No se pudieron cargar las empresas asociadas."
            );
          }
        } catch (error) {
          console.error("Error al procesar la respuesta:", error);
          alert("Error al cargar las empresas asociadas.");
        }
      },
      error: function () {
        alert("Error al realizar la solicitud.");
      },
    });
  });
});
