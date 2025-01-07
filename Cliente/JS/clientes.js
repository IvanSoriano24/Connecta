document.addEventListener("DOMContentLoaded", function () {
    // URL del archivo PHP que retorna los datos de los clientes
    const url = "../Servidor/PHP/clientes.php";
    // Parámetros para enviar la función deseada (numFuncion=1 para mostrar clientes)
    const params = new URLSearchParams();
    params.append("numFuncion", 1); // Indica la función a ejecutar en PHP
    // Obtener los datos de los clientes
    fetch(url, {
        method: "POST", // Cambiamos a POST porque el PHP espera POST para numFuncion
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
        },
        body: params.toString(), // Enviar los parámetros en el cuerpo de la solicitud
    }).then((response) => {
        console.log('Respuesta completa:', response);  // Ver la respuesta completa del servidor
        // Verificar si la respuesta es exitosa (código de estado 2xx)
        if (!response.ok) {
            throw new Error(`Error en la solicitud: ${response.statusText}`);
        }
        // Procesar la respuesta como JSON
        return response.json(); // Usar json() para obtener directamente un objeto JavaScript
    }).then((data) => {
        console.log('Datos recibidos:', data);  // Imprimir los datos recibidos
        // Verificar si la respuesta contiene datos
        if (data.success && data.data) {
            const clientes = data.data;
            const tablaBody = document.getElementById("datosClientes");
            
            tablaBody.innerHTML = "";  // Limpiar la tabla antes de agregar nuevos datos

            // Recorrer los clientes y agregarlos a la tabla
            clientes.forEach(cliente => {
                const fila = document.createElement("tr");
                fila.innerHTML = `
                    <td>${cliente.CLAVE || 'Sin clave'}</td>
                    <td>${cliente.NOMBRE || 'Sin nombre'}</td>
                    <td>${cliente.CALLE || 'Sin calle'}</td>
                    <td>${cliente.TELEFONO || 'Sin teléfono'}</td>
                    <td>${cliente.SALDO || '0'}</td>
                    <td>${cliente.EstadoDatosTimbrado || 'Sin estado'}</td>
                    <td>${cliente.NOMBRECOMERCIAL || 'Sin nombre comercial'}</td>
                    <td>
                        <button class="btnVisualizarCliente" name="btnVisualizarCliente" data-id="${cliente.CLAVE}">Visualizar</button>
                    </td>
                `;
                tablaBody.appendChild(fila);
            });
        } else {
            console.error('Error al obtener los datos:', data.message);
        }
    })
    .catch((error) => {
        // Capturar errores de la solicitud o del proceso de JSON
        console.error("Error:", error);
    });
});
