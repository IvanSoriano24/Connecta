//Obtener el token de seguridad
const token = document.getElementById("csrf_token").value;
let paginaActual = 1;
//const registrosPorPagina = 5; // Ajusta según convenga
let registrosPorPagina = 10;

//Obtener los prodcutos
function cargarProductosDash(limpiarTabla = true) {
  const xhr = new XMLHttpRequest();
  xhr.open(
    "GET",
    "../Servidor/PHP/ventas.php"
    + "?numFuncion=11"
    + "&token="     + encodeURIComponent(token)
    + "&pagina="    + paginaActual
    + "&porPagina=" + registrosPorPagina,
    true
  ); //URL con los parametros
  xhr.onload = function () {
    if (xhr.status === 200) {
      let response;
      try {
        response = JSON.parse(xhr.responseText);
      } catch (e) {
        return alert("JSON inválido: " + e.message);
      }
      if (response.success) {
        //Si devolvio un succes, mostrar los productos en tabla
        mostrarProductosEnTabla(response.productos, response.total, limpiarTabla);
      } else {
        Swal.fire({
            title: "Aviso",
            text: "Error desde el servidor: " + response.message,
            icon: "warning",
            confirmButtonText: "Entendido",
          });
        //alert("Error desde el servidor: " + response.message);
      }
    } else {
      alert("Error HTTP: " + xhr.status);
    }
  };
  xhr.onerror = () => alert("Error de conexión");
  xhr.send();
}
//Funcion para mostrar los productos en tabla
function mostrarProductosEnTabla(productos, total, limpiarTabla) {
  const $tbody = $("#datosProductos");
  if (limpiarTabla) {
    $tbody.empty();
  }

  if (!productos || productos.length === 0) {
    $tbody.html(
      `<tr>
         <td colspan="3" class="text-center text-muted">
           No hay productos disponibles.
         </td>
       </tr>`
    );
    $("#pagination").empty();
    return;
  }

  // Rellenar filas
  productos.forEach(prod => {
    const existencia = prod.EXIST - prod.APART;
    const fila = `
      <tr>
        <td>${prod.CVE_ART}</td>
        <td>${prod.DESCR}</td>
        <td class="text-end">${Intl.NumberFormat().format(existencia)}</td>
      </tr>
    `;
    $tbody.append(fila);
  });

  // Construir la paginación UNA SOLA VEZ
  buildPagination(total);
}
//Crear boton de navegacion
function makeBtn(text, page, disabled, active) {
  const $btn = $("<button>")
    .text(text)
    .prop("disabled", disabled)
    .toggleClass("active", active);

  if (!disabled) {
    $btn.on("click", () => {
      paginaActual = page;
      cargarProductosDash(true);   // o datosPedidos(true) si así la llamas
    });
  }

  return $btn;
}
//Ahora buildPagination puede usar makeBtn sin problema
function buildPagination(total) {
    console.log("total : ", total);
    console.log("registrosPorPagina : ", registrosPorPagina);
  const totalPages = Math.ceil(total / registrosPorPagina);
  const maxButtons = 5;
  const $cont = $("#pagination").empty();
  console.log("totalPages: ", totalPages);

  if (totalPages <= 1) return;

  let start = Math.max(1, paginaActual - Math.floor(maxButtons / 2));
  let end   = start + maxButtons - 1;
  if (end > totalPages) {
    end   = totalPages;
    start = Math.max(1, end - maxButtons + 1);
  }

  // Flechas «Primera» y «Anterior»
  $cont.append(makeBtn("«", 1, paginaActual === 1, false));
  $cont.append(makeBtn("‹", paginaActual - 1, paginaActual === 1, false));

  // Botones numéricos
  for (let i = start; i <= end; i++) {
    $cont.append(makeBtn(i, i, false, i === paginaActual));
  }

  // Flechas «Siguiente» y «Última»
  $cont.append(makeBtn("›", paginaActual + 1, paginaActual === totalPages, false));
  $cont.append(makeBtn("»", totalPages, paginaActual === totalPages, false));

  console.log("paginaActual: ", paginaActual);
}

let debounceTimeout;
function debouncedSearch() {
  clearTimeout(debounceTimeout);

  // Espera 3 segundos antes de ejecutar doSearch
  debounceTimeout = setTimeout(() => {
    doSearch(true);
  }, 500);
}

const noEmpresa = sessionStorage.getItem("noEmpresaSeleccionada");

//Funcion para buscar un producto(s)
function doSearch(limpiarTabla = true) {
  const searchText = document.getElementById("searchTerm").value.toLowerCase();
  
  if (searchText.length >= 2) {
    $.post(
      "../Servidor/PHP/producto.php",
      {
        numFuncion: "2",
        noEmpresa: noEmpresa,
        token: token,
        pagina: paginaActual,
        porPagina: registrosPorPagina,
        searchText: searchText
      },
      function (response) {
        mostrarProductosEnTabla(response.productos, response.total, limpiarTabla);
      },
      "json"
    ).fail(function (jqXHR, textStatus, errorThrown) {
      console.error("Error en la solicitud:", textStatus, errorThrown, jqXHR);
    });
  } else {
    cargarProductosDash(true);
  }
}

$("#selectCantidad").on("change", function () {
  const seleccion = parseInt($(this).val(), 10);
  registrosPorPagina = isNaN(seleccion) ? registrosPorPagina : seleccion;
  paginaActual = 1; // volvemos a la primera página
  cargarProductosDash(true); // limpia la tabla y carga sólo registrosPorPagina filas
});
// Llamar a la función cuando cargue la página
document.addEventListener("DOMContentLoaded", () => {
  // Variables globales de paginación
  paginaActual = 1;
  //const registrosPorPagina = 5; // Ajusta según convenga
  registrosPorPagina = 10; // Ajusta según convenga
  cargarProductosDash(true);
});
