//lineaSelect
function obtenerLineas() {
  $.ajax({
    url: "../Servidor/PHP/inventario.php",
    method: "GET",
    data: {
      numFuncion: "3",
    },
    success: function (response) {
      try {
        const res =
          typeof response === "string" ? JSON.parse(response) : response;

        if (res.success && Array.isArray(res.data)) {
          const lineaSelect = $("#lineaSelect");
          lineaSelect.empty();
          lineaSelect.append(
            "<option selected disabled>Seleccione una linea</option>"
          );

          res.data.forEach((dato) => {
            lineaSelect.append(
              `<option value="${dato.CVE_LIN}" data-id="${dato.CVE_LIN}" data-descripcion="${dato.DESC_LIN}">
                ${dato.DESC_LIN}
              </option>`
            );
          });

          // Habilitar el select si hay vendedores disponibles
          //lineaSelect.prop("disabled", res.data.length === 0);
        } else {
          /*Swal.fire({
            icon: "warning",
            title: "Aviso",
            text: res.message || "No se Encontraron Datos de Envio.",
          });*/
          //$("#lineaSelect").prop("disabled", true);
        }
      } catch (error) {
        console.error("Error al Procesar la Respuesta:", error);
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Error al Cargar las Lineas.",
        });
      }
    },
    error: function () {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Error al Obtener las Lineas.",
      });
    },
  });
}
/*function cargarProductos(){
    const filtroLinea = $("#lineaSelect").val(); // Obtener el filtro seleccionado
}*/
function noInventario() {
  $.ajax({
    url: "../Servidor/PHP/inventario.php",
    method: "GET",
    data: { numFuncion: "2" },

    // 1) Indica que esperas JSON  
    dataType: "json",

    // 2) Coloca aquí el callback con la clave `success`
    success: function(data) {
      console.log("Respuesta recibida:", data);
      if (data.success) {
        document.getElementById("noInventario").value = data.noInventario;
      } else {
        console.error("Error del servidor:", data.message);
      }
    },

    // Error de comunicación
    error: function(jqXHR, textStatus, errorThrown) {
      console.error("AJAX error:", textStatus, errorThrown);
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "No se pudo obtener el número de inventario."
      });
    }
  });
}
function abrirModal(){
  
  const lineaSelect = document.getElementById("lineaSelect").value;
  /*const lineaSelect = $('#lineaSelect').val;*/
  document.getElementById("lineaSeleccionada").value = lineaSelect;
  console.log(lineaSelect);
  $("#resumenInventario").modal("show");
}


window.onload = function () {
  var fecha = new Date(); //Fecha actual
  var mes = fecha.getMonth() + 1; //obteniendo mes
  var dia = fecha.getDate(); //obteniendo dia
  var ano = fecha.getFullYear(); //obteniendo año
  if (dia < 10) dia = "0" + dia; //agrega cero si el menor de 10
  if (mes < 10) mes = "0" + mes; //agrega cero si el menor de 10
  document.getElementById("fechaInicio").value = ano + "-" + mes + "-" + dia;
  document.getElementById("fechaFin").value = ano + "-" + mes + "-" + dia;
};
$(document).ready(function () {
  obtenerLineas();
  noInventario();
});

// Escuchar el cambio en el filtro
/*$("#lineaSelect").change(function () {
  cargarProductos(); // Recargar las comandas con el filtro aplicado
});*/
// Escuchar el clic en el boton
$("#btnNext").click(function () {
  abrirModal();
});
