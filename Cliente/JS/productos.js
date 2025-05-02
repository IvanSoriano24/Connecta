const token = document.getElementById("csrf_token").value;
function cargarProductosDash() {
  const numFuncion = 11; // Identificador del caso en PHP
  const xhr = new XMLHttpRequest();
  xhr.open(
    "GET",
    "../Servidor/PHP/ventas.php?numFuncion=" + numFuncion + "&token=" + token,
    true
  );
  xhr.setRequestHeader("Content-Type", "application/json");
  xhr.onload = function () {
    if (xhr.status === 200) {
      try {
        const response = JSON.parse(xhr.responseText);
        if (response.success) {
          mostrarProductosEnTabla(response.productos);
        } else {
          alert("Error desde el servidor: " + response.message);
        }
      } catch (error) {
        alert("Error al analizar JSON: " + error.message);
      }
    } else {
      alert("Error en la respuesta HTTP: " + xhr.status);
    }
  };

  xhr.onerror = function () {
    alert("Hubo un problema con la conexión.");
  };

  xhr.send();
}
function mostrarProductosEnTabla(productos) {
  const tbody = document.querySelector("#datosProductos");

  if (!tbody) {
    console.error("Error: No se encontró la tabla para los productos.");
    return;
  }

  tbody.innerHTML = ""; // Limpiar la tabla antes de agregar productos

  if (!Array.isArray(productos) || productos.length === 0) {
    tbody.innerHTML =
      "<tr><td colspan='4' class='text-center text-muted'>No hay productos disponibles.</td></tr>";
    return;
  }

  productos.forEach((producto) => {
    let existenciaReal = producto.EXIST - producto.APART; // Se usa `let` en lugar de `const`

    const fila = document.createElement("tr");
    if (existenciaReal > 0) {
      //console.log(Intl.NumberFormat().format(existenciaReal));
      fila.innerHTML = `
            <td>${producto.CVE_ART}</td>
            <td>${producto.DESCR}</td>
            <td class="text-end">${Intl.NumberFormat().format(
              existenciaReal
            )}</td>
        `;
      tbody.appendChild(fila);
    } //<td class="text-end">${new Intl.NumberFormat().format(existenciaReal)}</td>
  });
}
function doSearch(){
    const tableReg = document.getElementById('producto');
    const searchText = document.getElementById('searchTerm').value.toLowerCase();
    let total = 0;
    // Recorremos todas las filas con contenido de la tabla
    for (let i = 1; i < tableReg.rows.length; i++) {
        // Si el td tiene la clase "noSearch" no se busca en su cntenido
        if (tableReg.rows[i].classList.contains("noSearch")) {
            continue;
        }
        let found = false;
        const cellsOfRow = tableReg.rows[i].getElementsByTagName('td');

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
            tableReg.rows[i].style.display = '';

        } else {
            // si no ha encontrado ninguna coincidencia, esconde la
            // fila de la tabla
            tableReg.rows[i].style.display = 'none';

        }
    }
    // mostramos las coincidencias
    const lastTR=tableReg.rows[tableReg.rows.length-1];
    const td=lastTR.querySelector("td");
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
// Llamar a la función cuando cargue la página
document.addEventListener("DOMContentLoaded", () => {
  cargarProductosDash();
});
