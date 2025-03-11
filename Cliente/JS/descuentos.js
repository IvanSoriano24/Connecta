function obtenerClientes() {
  $.ajax({
    url: "../Servidor/PHP/descuento.php",
    type: "GET",
    data: { numFuncion: "1" }, // Llamar a la función 15 en PHP
    success: function (response) {
      try {
        const res =
          typeof response === "string" ? JSON.parse(response) : response;

        if (res.success) {
          console.log("Clientes obtenidos: ", res);

          // Llenar el select de clientes
          const selectCliente = $("#descuentosClientes");
          selectCliente.empty();
          selectCliente.append(
            "<option selected disabled>Selecciona un Cliente</option>"
          );

          res.data.forEach((cliente) => {
            selectCliente.append(
              `<option value="${cliente.clave}" 
                  data-nombre="${cliente.nombre}">
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
function cargarProductos(cliente) {
  const numFuncion = 11; // Identificador del caso en PHP
  const xhr = new XMLHttpRequest();
  xhr.open("GET", "../Servidor/PHP/ventas.php?numFuncion=" + numFuncion, true);
  xhr.setRequestHeader("Content-Type", "application/json");
  xhr.onload = function () {
    if (xhr.status === 200) {
      try {
        const response = JSON.parse(xhr.responseText);
        if (response.success) {
          mostrarProductosEnTabla(response.productos, cliente);
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
async function obtenerDescuentoCliente(clienteClave) {
  const descuentoFirebase = await obtenerClienteDescunetoDeFirebase(clienteClave);
  $.ajax({
    url: "../Servidor/PHP/descuento.php",
    type: "POST",
    data: {
      cliente: clienteClave,
      numFuncion: "2",
    },
    success: function (response) {
      try {
        const res =
          typeof response === "string" ? JSON.parse(response) : response;
        if (res.success && res.data) {
          // Inserta el descuento en el elemento con id "descuentoCliente"
          if(res.data.DESCUENTO === "" || res.data.DESCUENTO === 0 || res.data.DESCUENTO === null){
            // Asigna el valor obtenido; si es falsy, asigna 0
            $("#descuentoCliente").val(descuentoFirebase || 0);
          }else{
            $("#descuentoCliente").val(res.data.DESCUENTO || 0);
          }
        } else {
          console.error(
            "No se encontró el descuento para el cliente o se produjo un error."
          );
        }
      } catch (e) {
        console.error("Error al parsear la respuesta JSON", e);
      }
    },
    error: function () {
      console.error(
        "Error en la solicitud AJAX para obtener el descuento del cliente"
      );
    },
  });
}
// Función para obtener los descuentos guardados en Firebase para un cliente
async function obtenerDescuentosDeFirebase(cliente) {
  try {
    const response = await $.ajax({
      url: '../Servidor/PHP/descuento.php', // Ajusta la URL a tu endpoint
      type: 'GET',
      dataType: 'json', // Fuerza que la respuesta se trate como JSON
      data: { cliente: cliente, numFuncion: 4 }
    });
    if (response.success) {
      return response.data;
    } else {
      console.error('Error en la consulta de descuentos:', response.message);
      return [];
    }
  } catch (error) {
    console.error('Error al obtener descuentos de Firebase:', error);
    return [];
  }
}
async function obtenerClienteDescunetoDeFirebase(cliente) {
  try {
    const response = await $.ajax({
      url: '../Servidor/PHP/descuento.php', // Ajusta la URL a tu endpoint PHP
      type: 'GET',
      dataType: 'json', // Se espera JSON
      data: { cliente: cliente, numFuncion: 5 }
    });
    if (response.success) {
      // Suponemos que response.data es el valor (por ejemplo, "40")
      return response.data;
    } else {
      console.error('Error en la consulta de descuentos:', response.message);
      return 0;
    }
  } catch (error) {
    console.error('Error al obtener descuento de Firebase:', error);
    return 0;
  }
}
async function mostrarProductosEnTabla(productos, cliente) {
  const tbody = document.querySelector("#datosDescuentos");

  if (!tbody) {
    console.error("Error: No se encontró la tabla para los productos.");
    return;
  }

  tbody.innerHTML = ""; // Limpiar la tabla

  if (!Array.isArray(productos) || productos.length === 0) {
    tbody.innerHTML = "<tr><td colspan='4' class='text-center text-muted'>No hay productos disponibles.</td></tr>";
    return;
  }

  // Primero, obtenemos los descuentos de Firebase para el cliente
  const descuentosFirebase = await obtenerDescuentosDeFirebase(cliente);
  // Convertir el array a un objeto donde la clave es el CVE_ART del producto
  const descuentosMap = {};
  descuentosFirebase.forEach(descuento => {
    // Se asume que cada objeto descuento tiene las propiedades 'clave' y 'descuento'
    descuentosMap[descuento.clave] = descuento.descuento;
  });

  productos.forEach((producto) => {
    let existenciaReal = producto.EXIST - producto.APART;
    if (existenciaReal > 0) {
      // Si se encontró un descuento para el producto, se utiliza; de lo contrario, se deja vacío
      const descuentoValor = descuentosMap[producto.CVE_ART] || "";
      const fila = document.createElement("tr");
      fila.innerHTML = `
        <td>${producto.CVE_ART}</td>
        <td>${producto.DESCR}</td>
        <td><input type="number" class="descuentoProducto" value="${descuentoValor}" style="text-align: right;"></td>
      `;
      tbody.appendChild(fila);
    }
  });
}
function obtenerDatosParaFirebase() {
  // Obtener el descuento asignado al cliente
  const descuentoCliente = $("#descuentoCliente").val() || "";

  // Inicializar un arreglo para almacenar los datos de cada producto
  let productos = [];

  // Recorrer cada fila de la tabla dentro del tbody con id "datosDescuentos"
  $("#datosDescuentos tr").each(function () {
    // Extraer la clave y la descripción de las dos primeras celdas
    const clave = $(this).find("td:eq(0)").text().trim();
    const descripcion = $(this).find("td:eq(1)").text().trim();
    // Extraer el descuento asignado al producto desde el input con clase "descuentoProducto"
    const descuentoProducto =
      $(this).find("input.descuentoProducto").val() || "";

    // Si existen clave y descripción, se agrega el objeto al arreglo
    if (clave && descripcion) {
      productos.push({
        clave: clave,
        descripcion: descripcion,
        descuento: descuentoProducto,
      });
    }
  });

  // Preparar el objeto con todos los datos que se enviarán a Firebase
  const datosFirebase = {
    descuentoCliente: descuentoCliente,
    productos: productos,
  };

  // Para depuración, puedes mostrar el resultado en la consola
  console.log("Datos a enviar a Firebase:", datosFirebase);

  // Retorna el objeto para que luego puedas utilizarlo (por ejemplo, en una llamada a Firebase)
  return datosFirebase;
}
function guardarDescuentos(cliente) {
  const datos = obtenerDatosParaFirebase();

  $.ajax({
    url: "../Servidor/PHP/descuento.php",
    type: "POST",
    data: {
      numFuncion: "3",
      cliente: cliente,
      datos: JSON.stringify(datos),
    },
    success: function (response) {
      try {
        const res =
          typeof response === "string" ? JSON.parse(response) : response;
        if (res.success) {
          Swal.fire({ title: "Éxito", text: res.message, icon: "success" });
        } else {
          Swal.fire({ title: "Error", text: res.message, icon: "error" });
        }
      } catch (e) {
        console.error("Error al parsear la respuesta", e);
      }
    },
    error: function () {
      Swal.fire({
        title: "Error",
        text: "Error en la solicitud AJAX",
        icon: "error",
      });
    },
  });
}

$(document).ready(function () {
  obtenerClientes();
  $("#descuentosClientes").on("change", function () {
    const clienteInput = document.getElementById("descuentosClientes");
    obtenerDescuentoCliente(clienteInput.value);
    cargarProductos(clienteInput.value);
  });
  $("#guardarDescuentos").on("click", function () {
    const clienteInput = document.getElementById("descuentosClientes");
    guardarDescuentos(clienteInput.value);
  });
});
