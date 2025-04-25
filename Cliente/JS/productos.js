const token = document.getElementById("csrf_token").value;
function cargarProductosDash() {
  const numFuncion = 11; // Identificador del caso en PHP
  const xhr = new XMLHttpRequest();
  xhr.open("GET", "../Servidor/PHP/ventas.php?numFuncion=" + numFuncion +"&token=" + token, true);
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
        tbody.innerHTML = "<tr><td colspan='4' class='text-center text-muted'>No hay productos disponibles.</td></tr>";
        return;
    }
  
    productos.forEach((producto) => {
        let existenciaReal = producto.EXIST - producto.APART; // Se usa `let` en lugar de `const`
  
        const fila = document.createElement("tr");
        if (existenciaReal > 0){
            //console.log(Intl.NumberFormat().format(existenciaReal));
            fila.innerHTML = `
            <td>${producto.CVE_ART}</td>
            <td>${producto.DESCR}</td>
            <td class="text-end">${Intl.NumberFormat().format(existenciaReal)}</td>
        `;
        tbody.appendChild(fila);
        } //<td class="text-end">${new Intl.NumberFormat().format(existenciaReal)}</td>
    });
  }
  
// Llamar a la función cuando cargue la página
document.addEventListener("DOMContentLoaded", () => {
  cargarProductosDash();
});
