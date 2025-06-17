function obtenerCamposTabla() {
  fetch("../Servidor/PHP/parametros.php?numFuncion=1")
    .then(resp => resp.json())
    .then(json => {
      if (!json.success) {
        console.error("Error al obtener los parámetros");
        return;
      }
      const datos = json.data; // array de { tabla, campo, descripcion }

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
    .catch(err => console.error("Error de fetch:", err));
}

function mostrarMoldal() {}
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


$(document).ready(function () {
  $("#btnAgregar").click(function () {
    mostrarMoldal();
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

            // Habilitar el select si hay empresas disponibles
            if (selectCampo.children("option").length > 1) {
              selectCampo.prop("disabled", false);
            } else {
              selectCampo.append(
                "<option disabled>No hay campos disponibles</option>"
              );
              selectCampo.prop("disabled", true);
            }

            // Mostrar empresas asociadas
           /* const listaEmpresas = $("#listaCamposSeleccionado");
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
                '<li class="list-group-item">Sin campos</li>'
              );
            }*/
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
            obtenerCamposTabla()
            //location.reload();
          });
        } else {
          Swal.fire({
            icon: "warning",
            title: "Error",
            text: res.message || "Error al guardar la asociación.",
          });
        }
      } catch (error) {
        console.error("Error al procesar la respuesta:", error);
        Swal.fire({
          icon: "warning",
          title: "Error",
          text: "Error al guardar la asociación.",
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


document.addEventListener("DOMContentLoaded", obtenerCamposTabla);