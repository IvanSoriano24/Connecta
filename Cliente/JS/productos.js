const token = document.getElementById("csrf_token").value;
let paginaActual = 1;
//const registrosPorPagina = 5; // Ajusta según convenga
let registrosPorPagina = 10;

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
  );
  xhr.onload = function () {
    if (xhr.status === 200) {
      let response;
      try {
        response = JSON.parse(xhr.responseText);
      } catch (e) {
        return alert("JSON inválido: " + e.message);
      }
      if (response.success) {
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
// 2) Ahora buildPagination puede usar makeBtn sin problema
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
function doSearch() {
  const tableReg = document.getElementById("producto");
  const searchText = document.getElementById("searchTerm").value.toLowerCase();
  let total = 0;
  // Recorremos todas las filas con contenido de la tabla
  for (let i = 1; i < tableReg.rows.length; i++) {
    // Si el td tiene la clase "noSearch" no se busca en su cntenido
    if (tableReg.rows[i].classList.contains("noSearch")) {
      continue;
    }
    let found = false;
    const cellsOfRow = tableReg.rows[i].getElementsByTagName("td");

    // Recorremos todas las celdas
    for (let j = 0; j < cellsOfRow.length && !found; j++) {
      const compareWith = cellsOfRow[j].innerHTML.toLowerCase();
      // Buscamos el texto en el contenido de la celda

      if (searchText.length == 0 || compareWith.indexOf(searchText) > -1) {
        found = true;
        total++;
      }
    }
    if (found) {
      tableReg.rows[i].style.display = "";
    } else {
      // si no ha encontrado ninguna coincidencia, esconde la
      // fila de la tabla
      tableReg.rows[i].style.display = "none";
    }
  }
  // mostramos las coincidencias
  const lastTR = tableReg.rows[tableReg.rows.length - 1];
  const td = lastTR.querySelector("td");
  lastTR.classList.remove("hide", "red");
  if (searchText == "") {
    lastTR.classList.add("hide");
  } else if (total) {
    //td.innerHTML="Se ha encontrado "+total+" coincidencia"+((total>1)?"s":"");
  } else {
    lastTR.classList.add("red");
    //td.innerHTML="No se han encontrado coincidencias";
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
