/* Funciones para Obtener los Datos a Mostrar (Campos Libres, Usuarios Administradores y Series de Folios)*/
// Funcion para obtener los campos libres
function obtenerCamposTabla() {
  //Peticion al servidor
  fetch("../Servidor/PHP/parametros.php?numFuncion=1")
    .then((resp) => resp.json())
    .then((json) => {
      if (!json.success) {
        //Mensaje de error si no se obtiene los datos
        console.error("Error al obtener los parámetros");
        return;
      }
      const datos = json.data; // array de { tabla, campo, descripcion }
      document.getElementById("idDocumentoClimb").value = datos[0].id;
      // 1) Agrupar por nombre de tabla
      const grupos = datos.reduce((acc, { tabla, campo, descripcion }) => {
        if (!acc[tabla]) acc[tabla] = [];
        acc[tabla].push({ campo, descripcion });
        return acc;
      }, {});

      // 2) Construir las filas
      const tbody = document.querySelector("#tablaParametros tbody");
      tbody.innerHTML = "";

      Object.entries(grupos).forEach(([tabla, campos]) => {
        // --- crear <select> con todas las opciones ---
        const select = document.createElement("select");
        select.className = "form-select form-select-sm";
        campos.forEach(({ campo, descripcion }, idx) => {
          const opt = document.createElement("option");
          opt.value = campo;
          opt.textContent = campo;
          // guardamos la descripción en un data-atributo
          opt.dataset.descripcion = descripcion;
          select.appendChild(opt);
        });

        // --- crear la celda de descripción ---
        const tdDescripcion = document.createElement("td");
        // inicializamos con la descripción del primer campo
        tdDescripcion.textContent = campos[0].descripcion;

        // al cambiar el select, actualizamos la descripción
        select.addEventListener("change", () => {
          const desc = select.selectedOptions[0].dataset.descripcion;
          tdDescripcion.textContent = desc;
        });

        // --- armar la fila completa ---
        const tr = document.createElement("tr");
        // Columna: nombre de la tabla
        const tdTabla = document.createElement("td");
        tdTabla.textContent = tabla;
        // Columna: el select de campos
        const tdSelect = document.createElement("td");
        tdSelect.appendChild(select);

        tr.appendChild(tdTabla);
        tr.appendChild(tdSelect);
        tr.appendChild(tdDescripcion);

        tbody.appendChild(tr);
      });
    })
    .catch((err) => console.error("Error de fetch:", err));
}
// Funcion para obtener a los administradores
function obtenerAdministradores() {
  // Peticion al servidor mediante AJAX
  $.ajax({
    url: "../Servidor/PHP/parametros.php",
    type: "POST",
    data: { numFuncion: "7" },
    success: function (response) {
      try {
        if (response.success) {
          //Si la respuesta fue satisfactoria
          listaUsuarios = response.data; // Guardar datos en la variable
          mostrarUsuarios(listaUsuarios); // Mostrar los usuarios 
        } else {
          //Si la respuesta no fue satisfactoria
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
/*------------------------------------------------Funciones------------------------------------------------*/
// Funcion para mostrar a los administradores
function mostrarUsuarios(usuarios) {
  //Obtenemos el tbody de la tabla donde se insertaran los datos
  var tablaAdministradores = $("#administradores");
  tablaAdministradores.empty(); // Limpiar la tabla antes de mostrar nuevos datos

  // Ordenar alfabéticamente
  usuarios.sort((a, b) =>
    (a.nombreCompleto || "").localeCompare(b.nombreCompleto || "")
  );

  // Generar filas
  usuarios.forEach(function (usuario) {
    var fila = $("<tr>");
    fila.append($("<td>").text(usuario.nombreCompleto || "-"));
    fila.append($("<td>").text(usuario.claveUsuario || "-"));

    // Botón Editar
    fila.append(
      $("<td>").append(
        $("<button>")
          .addClass("btn btn-info btn-sm")
          .text("Editar")
          .attr("onclick", 'editarAdministrador("' + usuario.id + '")')
      )
    );
    tablaAdministradores.append(fila);
  });
}
// Funcion para editar la calve de vendedor al administrador
function editarAdministrador(idUsuario) {
  // peticion al servidor
  $.ajax({
    url: "../Servidor/PHP/parametros.php",
    method: "GET",
    data: { numFuncion: "8", id: idUsuario }, // Obtener los datos del usuario
    success: function (response) {
      try {
        const data = JSON.parse(response);
        // Si la respuesta fue correcta
        if (data.success) {
          // Asignar valores del usuario
          $("#nombreAdministrador").val(data.data.usuario); //Nombre
          $("#idUsuario").val(idUsuario); //ID del documento en firebase
          // Cargar la lista de vendedores
          $.ajax({
            url: "../Servidor/PHP/usuarios.php",
            method: "GET",
            data: { numFuncion: "13" }, // Obtener todos los vendedores disponibles
            success: function (responseVendedores) {
              console.log(
                "Respuesta del servidor (vendedores):",
                responseVendedores
              );
              try {
                const res =
                  typeof responseVendedores === "string"
                    ? JSON.parse(responseVendedores)
                    : responseVendedores;
                // Si la respuesta fue correcta
                if (res.success && Array.isArray(res.data)) {
                  const selectVendedor = $("#vendedores");
                  selectVendedor.empty();
                  selectVendedor.append(
                    "<option selected disabled>Seleccione un Vendedor</option>"
                  ); //Creamos el select
                  res.data.forEach((vendedor) => {
                    selectVendedor.append(
                      `<option value="${vendedor.clave}">${vendedor.nombre} || ${vendedor.clave}</option>`
                    );
                  }); //Se mapea el select con los datos obtenidos
                  // ✅ Ahora obtenemos la clave del vendedor y la seleccionamos correctamente
                  if (data.data.claveUsuario != "") {
                    selectVendedor.val(data.data.claveUsuario);
                  }
                } else {
                  // Si la respuesta no fue correcta
                  Swal.fire({
                    icon: "warning",
                    title: "Aviso",
                    text: res.message || "No se encontraron vendedores.",
                  });
                }
                // Si hubo un problema en flujo de la funcion
              } catch (error) {
                console.error("Error al procesar los vendedores:", error);
              }
            },
            error: function () {
              // Si hubo un problema en la peticion
              Swal.fire({
                icon: "error",
                title: "Error",
                text: "Error al obtener la lista de vendedores.",
              });
            },
          });
          // Mostrar modal de edición
          $("#seleccionarClaveAdministrador").modal("show");
        } else {
          // Si la respuesta no fue correcta
          Swal.fire({
            icon: "error",
            title: "Error",
            text: data.message || "No se pudo cargar el usuario.",
          });
        }
      } catch (e) {
        // Si hubo un problema con el flujo de la funcion
        console.error("Error al parsear la respuesta:", e);
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Hubo un error al procesar la respuesta del servidor.",
        });
      }
    },
    error: function () {
      // Si hubo un problema en la peticion
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Hubo un problema al obtener los datos del usuario.",
      });
    },
  });
}

function cargarTablas() {
  $.ajax({
    url: "../Servidor/PHP/parametros.php",
    method: "GET",
    data: { numFuncion: "2" }, // Función para obtener usuarios
    success: function (response) {
      try {
        const res =
          typeof response === "string" ? JSON.parse(response) : response;

        if (res.success && Array.isArray(res.data)) {
          const selectTabla = $("#selectTabla");
          selectTabla.empty();
          selectTabla.append(
            "<option selected disabled>Seleccione una Tabla</option>"
          );
          res.data.forEach((tabla) => {
            // Incluimos `data-usuario` para usarlo posteriormente
            selectTabla.append(
              `<option value="${tabla.TABLE_NAME}">${tabla.TABLE_NAME}</option>`
            );
          });
        } else if (res.token) {
          Swal.fire({
            title: "Error",
            text: res.message,
            icon: "warning",
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
function mostrarCamposUsados(tabla) {
  const idDocumento = $("#idDocumentoClimb").val();
  $.ajax({
    url: "../Servidor/PHP/parametros.php",
    method: "GET",
    data: { numFuncion: "6", idDocumento, tabla },
    success(response) {
      const res =
        typeof response === "string" ? JSON.parse(response) : response;
      if (!res.success) {
        return Swal.fire(
          "Error",
          res.message || "No se pudieron cargar los campos.",
          "error"
        );
      }

      const $lista = $("#listaCamposSeleccionado").empty();

      if (res.data.length) {
        res.data.forEach(({ campo, descripcion }) => {
          // Usamos LI con Bootstrap
          const $li = $(`
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <span>${campo}</span>
              <small class="text-muted">${descripcion}</small>
            </li>
          `);
          $lista.append($li);
        });
      } else {
        $lista.append(`
          <li class="list-group-item text-center text-muted">
            Sin campos usados
          </li>
        `);
      }
    },
    error() {
      Swal.fire("Error", "Error al realizar la solicitud.", "error");
    },
  });
}

function cerrarModal() {
  limpiarCampos();
  $("#seleccionarCampoLibre").modal("hide"); // Cierra el modal usando Bootstrap
  // Restaurar el aria-hidden al cerrar el modal
  $("#seleccionarCampoLibre").attr("aria-hidden", "true");
  // Eliminar el atributo inert del fondo al cerrar
  $(".modal-backdrop").removeAttr("inert");
}
function limpiarCampos() {
  $("#selectTabla").val("");
  $("#selectCampo").val("");
  $("#descripcion").val("");
}
$(document).ready(function () {
  $("#btnAgregar").click(function () {
    mostrarMoldal();
  });
  $("#cerrarModalCamposHeader").click(function () {
    cerrarModal();
  });
  $("#cerrarModalCamposLibres").click(function () {
    cerrarModal();
  });

  $("#btnAgregarCampo").on("click", function () {
    // Obtener usuarios y empresas
    cargarTablas();
    //cargarEmpresas();

    // Mostrar el modal
    $("#seleccionarCampoLibre").modal("show");
  });

  $("#selectTabla").on("change", function () {
    const tabla = $(this).val();
    if (!tabla) {
      $("#selectCampo")
        .empty()
        .append("<option selected disabled>Seleccione un Campo</option>")
        .prop("disabled", true);
      $("#listaCamposSeleccionado").empty(); // Limpiar asociaciones
      return;
    }
    // Obtener las asociaciones del usuario
    $.ajax({
      url: "../Servidor/PHP/parametros.php",
      method: "GET",
      data: { numFuncion: "3", tabla: tabla },
      success: function (response) {
        try {
          const res =
            typeof response === "string" ? JSON.parse(response) : response;

          if (res.success && Array.isArray(res.data)) {
            const selectCampo = $("#selectCampo");
            selectCampo.empty();
            selectCampo.append(
              "<option selected disabled>Seleccione un Campo</option>"
            );

            res.data.forEach((campo) => {
              //Crear el option

              selectCampo.append(
                `<option value="${campo.COLUMN_NAME}">${campo.COLUMN_NAME}</option>`
              );
            });

            // Habilitar el select si hay campos disponibles
            if (selectCampo.children("option").length > 1) {
              selectCampo.prop("disabled", false);
              mostrarCamposUsados(tabla);
            } else {
              selectCampo.append(
                "<option disabled>No hay campos disponibles</option>"
              );
              selectCampo.prop("disabled", true);
            }
          } else {
            alert(res.message || "No se pudieron cargar los campos libres.");
          }
        } catch (error) {
          console.error("Error al procesar la respuesta:", error);
          alert("Error al cargar los campos libres.");
        }
      },
      error: function () {
        alert("Error al realizar la solicitud.");
      },
    });
  });
  $("#selectCampo").on("change", function () {
    const campoSeleccionado = $(this).find(":selected");
    const tablaSeleccionada = document.getElementById("selectTabla");

    if (campoSeleccionado.val()) {
      const campo = campoSeleccionado.val(); // Guardamos la clave seleccionada
      const tabla = tablaSeleccionada.value; // Guardamos la clave seleccionada

      validarCampo(campo, tabla, function (existe) {
        if (!existe) {
          // ✅ El vendedor no existe, permitimos la selección
          $("#selectCampo").val(campo);
        } else {
          // ❌ El vendedor ya existe, no permitimos seleccionarlo
          Swal.fire({
            icon: "warning",
            title: "Error",
            text: "Este campo ya está registrado. Seleccione otro.",
          });
          // 🔴 Deseleccionar la opción seleccionada
          $("#selectCampo").val("");
        }
      });
    }
  });
  function validarCampo(campo, tabla, callback) {
    $.ajax({
      url: "../Servidor/PHP/parametros.php",
      method: "POST",
      data: { numFuncion: "5", campo: campo, tabla: tabla }, // Llamamos la función PHP
      success: function (response) {
        try {
          const res = JSON.parse(response);
          console.log("Validación de campo:", res); // Depuración
          if (res.success) {
            callback(res.exists); // Devuelve true si el vendedor ya existe, false si no
          } else {
            Swal.fire({
              icon: "warning",
              title: "Error",
              text: res.message || "Error al validar el campo.",
            });
            callback(false);
          }
        } catch (error) {
          console.error("Error al procesar la validación del campo:", error);
          Swal.fire({
            icon: "warning",
            title: "Error",
            text: "Error en la validación del campo.",
          });
          callback(false);
        }
      },
      error: function () {
        Swal.fire({
          icon: "warning",
          title: "Error",
          text: "No se pudo verificar el campo.",
        });
        callback(false);
      },
    });
  }

  $("#btnGuardarCamposLibres").on("click", function () {
    const tabla = document.getElementById("selectTabla").value;
    const campo = document.getElementById("selectCampo").value;
    const descripcion = document.getElementById("descripcion").value;
    const id = document.getElementById("idDocumentoClimb").value;
    $.ajax({
      url: "../Servidor/PHP/parametros.php", // Ruta al PHP
      method: "POST",
      data: {
        numFuncion: "4",
        tabla: tabla,
        campo: campo,
        descripcion: descripcion,
        id: id,
      },
      success: function (response) {
        try {
          const res = JSON.parse(response);
          if (res.success) {
            Swal.fire({
              icon: "success",
              title: "Éxito",
              text: "Campo guardado exitosamente.",
              timer: 1000,
              showConfirmButton: false,
            }).then(() => {
              //$("#selectUsuario").val("").change(); // Limpia el selector de usuarios
              //$("#selectEmpresa").val(""); // Limpia el selector de empresas
              $("#seleccionarCampoLibre").modal("hide"); // Cerrar el modal
              obtenerCamposTabla();
              //location.reload();
            });
          } else {
            Swal.fire({
              icon: "warning",
              title: "Error",
              text: res.message || "Error al guardarel campo.",
            });
          }
        } catch (error) {
          console.error("Error al procesar la respuesta:", error);
          Swal.fire({
            icon: "warning",
            title: "Error",
            text: "Error al guardar el campo.",
          });
        }
      },
      error: function () {
        Swal.fire({
          icon: "warning",
          title: "Error",
          text: "Error al realizar la solicitud.",
        });
      },
    });
  });
  $("#btnGuardarClave").on("click", function () {
    const usuario = document.getElementById("nombreAdministrador").value;
    const vendedores = document.getElementById("vendedores").value;
    const idUsuario = document.getElementById("idUsuario").value;
    $.ajax({
      url: "../Servidor/PHP/parametros.php", // Ruta al PHP
      method: "POST",
      data: {
        numFuncion: "9",
        vendedores: vendedores,
        usuario: usuario,
        id: idUsuario,
      },
      success: function (response) {
        try {
          const res = JSON.parse(response);
          if (res.success) {
            Swal.fire({
              icon: "success",
              title: "Éxito",
              text: "Clave Actualizada.",
              timer: 1000,
              showConfirmButton: false,
            }).then(() => {
              //$("#selectUsuario").val("").change(); // Limpia el selector de usuarios
              //$("#selectEmpresa").val(""); // Limpia el selector de empresas
              $("#seleccionarClaveAdministrador").modal("hide"); // Cerrar el modal
              obtenerAdministradores();
              //location.reload();
            });
          } else {
            Swal.fire({
              icon: "warning",
              title: "Error",
              text: res.message || "Error al guardar la clave.",
            });
          }
        } catch (error) {
          console.error("Error al procesar la respuesta:", error);
          Swal.fire({
            icon: "warning",
            title: "Error",
            text: "Error al guardar la clave.",
          });
        }
      },
      error: function () {
        Swal.fire({
          icon: "warning",
          title: "Error",
          text: "Error al realizar la solicitud.",
        });
      },
    });
  });
});

// Ejecutar las funcione cuando se cargue la pagina
document.addEventListener("DOMContentLoaded", obtenerCamposTabla);
document.addEventListener("DOMContentLoaded", obtenerAdministradores);
