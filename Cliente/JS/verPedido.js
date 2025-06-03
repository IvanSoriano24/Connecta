// --------Funciones para mostrar articulos----------------

// -----------------------------------------------------------------------------

// Maneja la creación de la fila de partidas
function obtenerProductos(input) {
    const numFuncion = 5; // Número de función para identificar la acción (en este caso obtener productos)
    const xhr = new XMLHttpRequest();
    xhr.open("GET", "../Servidor/PHP/ventas.php?numFuncion=" + numFuncion, true);
    xhr.setRequestHeader("Content-Type", "application/json");

    xhr.onload = function () {
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                // Procesamos la respuesta de los productos
                mostrarListaProductos(response.productos, input);
            } else {
                Swal.fire({
                    title: "Aviso",
                    text: response.message,
                    icon: "warning",
                    confirmButtonText: "Aceptar",
                });
                //alert("Error: " + response.message);
            }
        } else {
            Swal.fire({
                title: "Aviso",
                text: "Hubo un problema con la consulta de productos.",
                icon: "error",
                confirmButtonText: "Aceptar",
            });
            //alert("Hubo un problema con la consulta de productos.");
        }
    };

    xhr.onerror = function () {
        Swal.fire({
            title: "Aviso",
            text: "Hubo un problema con la conexión.",
            icon: "error",
            confirmButtonText: "Aceptar",
        });
        //alert("Hubo un problema con la conexión.");
    };

    xhr.send();
}

async function obtenerImpuesto(cveEsqImpu) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: "../Servidor/PHP/ventas.php",
            type: "POST",
            data: { cveEsqImpu: cveEsqImpu, numFuncion: "7" },
            success: function (response) {
                try {
                    // Usa el objeto directamente
                    if (response.success) {
                        const { IMPUESTO1, IMPUESTO2, IMPUESTO3, IMPUESTO4 } =
                            response.impuestos;
                        resolve({
                            impuesto1: IMPUESTO1,
                            impuesto2: IMPUESTO2,
                            impuesto3: IMPUESTO3,
                            impuesto4: IMPUESTO4,
                        });
                    } else {
                        console.error("Error del servidor:", response.message);
                        reject("Error del servidor: " + response.message);
                    }
                } catch (error) {
                    console.error("Error al procesar la respuesta:", error);
                    reject("Error al procesar la respuesta: " + error.message);
                }
            },
            error: function (xhr, status, error) {
                reject("Error en la solicitud AJAX: " + error);
            },
        });
    });
}
async function completarPrecioProducto(cveArt, filaTabla) {
    try {
        // Obtener la lista de precios correctamente
        const listaPrecioElement = document.getElementById("listaPrecios");
        console.log(listaPrecioElement.value);
        let descuento = filaTabla.querySelector(".descuento");
        const descuentoCliente = document.getElementById("descuentoCliente").value;
        descuento.value = descuentoCliente;
        //descuento.readOnly = false;
        const cvePrecio = listaPrecioElement ? listaPrecioElement.value : "1";
        // Obtener el precio del producto
        const resultado = await obtenerPrecioProducto(cveArt, cvePrecio);
        if (!resultado) return;

        const { precio, listaUsada } = resultado;

        if (!precio) {
            Swal.fire({
                title: "Aviso",
                text: "No se pudo obtener el precio del producto.",
                icon: "warning",
                confirmButtonText: "Aceptar",
            });
            //alert("No se pudo obtener el precio del producto.");
            return;
        }
        if(listaUsada != 1){
            descuento.value = 0;
        }
        // Seleccionar el input correspondiente dentro de la fila
        const precioInput = filaTabla.querySelector(".precioUnidad");
        if (precioInput) {
            precioInput.value = parseFloat(precio).toFixed(2); // Establecer el precio con 2 decimales
            precioInput.readOnly = true;
        } else {
            console.error("No se encontró el campo 'precioUnidad' en la fila.");
        }
        // Obtener y manejar impuestos
        var CVE_ESQIMPU = document.getElementById("CVE_ESQIMPU").value; // Asegurarse de usar el valor
        if (!CVE_ESQIMPU) {
            console.error("CVE_ESQIMPU no tiene un valor válido.");
            return;
        }
        const impuestos = await obtenerImpuesto(CVE_ESQIMPU);

        // Obtén los campos de la fila
        const impuesto1Input = filaTabla.querySelector(".ieps");
        const impuesto2Input = filaTabla.querySelector(".impuesto2");
        const impuesto4Input = filaTabla.querySelector(".iva");
        const impuesto3Input = filaTabla.querySelector(".impuesto3");
        /*const impuesto1Input = document.querySelector(".ieps");
        const impuesto2Input = document.querySelector(".descuento2");
        const impuesto4Input = document.querySelector(".iva");
        const impuesto3Input = document.querySelector(".impuesto3");*/

        // Verifica si los campos existen y asigna los valores de los impuestos
        if (impuesto1Input && impuesto4Input) {
            impuesto1Input.value = parseFloat(impuestos.impuesto1);
            impuesto4Input.value = parseFloat(impuestos.impuesto4);
            impuesto3Input.value = parseFloat(impuestos.impuesto3);
            impuesto1Input.readOnly = true;
            impuesto4Input.readOnly = true;
        } else {
            console.error(
                "No se encontraron uno o más campos 'descuento' en la fila."
            );
        }

        // Maneja los impuestos como sea necesario
    } catch (error) {
        console.error("Error al completar el precio del producto:", error);
    }
}

// Función para obtener el precio del producto
async function obtenerPrecioProducto(claveProducto, listaPrecioCliente) {
    try {
        const response = await $.get("../Servidor/PHP/ventas.php", {
            numFuncion: "6", // Cambia según la función que uses en tu PHP
            claveProducto: claveProducto,
            listaPrecioCliente: listaPrecioCliente,
        });
        if (response.success) {
            //console.log(response);
            return {
                precio: parseFloat(response.precio),
                listaUsada: response.listaUsada
            };

        } else {
            Swal.fire({
                title: "Aviso",
                text: response.message,
                icon: "warning",
                confirmButtonText: "Aceptar",
            });
            //alert(response.message); // Muestra el mensaje de error
            return null;
        }
    } catch (error) {
        console.error("Error al obtener el precio del producto:", error);
        return null;
    }
}

// Boton para mostrar Productos
function mostrarProductos(input) {
    // Abre el modal de productos automáticamente
    const modalProductos = new bootstrap.Modal(
        document.getElementById("modalProductos")
    );
    modalProductos.show();

    document.getElementById('campoBusqueda').value = ''

    // Llamar a la función AJAX para obtener los productos desde el servidor
    obtenerProductos(input);
}

// FUNCION PARA LISTAR Productos
function mostrarListaProductos(productos, input) {
    const tablaProductos = document.querySelector("#tablalistaProductos tbody");
    const campoBusqueda = document.getElementById("campoBusqueda");
    const filtroCriterio = document.getElementById("filtroCriterio");

    // Función para renderizar productos
    function renderProductos(filtro = "") {
        tablaProductos.innerHTML = ""; // Limpiar la tabla antes de agregar nuevos productos
        const criterio = filtroCriterio.value; // Obtener criterio seleccionado
        const productosFiltrados = productos.filter((producto) =>
            producto[criterio].toLowerCase().includes(filtro.toLowerCase())
        );
        productosFiltrados.forEach(function (producto) {
            const fila = document.createElement("tr");
            const celdaClave = document.createElement("td");
            celdaClave.textContent = producto.CVE_ART;
            const celdaDescripcion = document.createElement("td");
            celdaDescripcion.textContent = producto.DESCR;
            const celdaExist = document.createElement("td");
            celdaExist.textContent = producto.EXIST;
            // const celdaLinea = document.createElement("td");
            // celdaLinea.textContent = producto.LIN_PROD;

            // const celdaDisponible = document.createElement("td");
            // celdaDisponible.textContent = producto.DISPONIBLE;

            // const celdaClaveAlterna = document.createElement("td");
            // celdaClaveAlterna.textContent = producto.CVE_ALT;
            fila.appendChild(celdaClave);
            fila.appendChild(celdaDescripcion);
            fila.appendChild(celdaExist);
            // fila.appendChild(celdaLinea);
            // fila.appendChild(celdaDisponible);
            // fila.appendChild(celdaClaveAlterna);
            fila.onclick = async function () {
                if (producto.EXIST > 0) {
                    input.value = producto.CVE_ART;
                    $("#CVE_ESQIMPU").val(producto.CVE_ESQIMPU);
                    const filaTabla = input.closest("tr");
                    const campoUnidad = filaTabla.querySelector(".unidad");
                    if (campoUnidad) {
                        campoUnidad.value = producto.UNI_MED;
                    }
                    const CVE_UNIDAD = filaTabla.querySelector(".CVE_UNIDAD");
                    const COSTO_PROM = filaTabla.querySelector(".COSTO_PROM");

                    CVE_UNIDAD.value = producto.CVE_UNIDAD;
                    COSTO_PROM.value = producto.COSTO_PROM;
                    // Desbloquear o mantener bloqueado el campo de cantidad según las existencias
                    const campoCantidad = filaTabla.querySelector("input.cantidad");
                    if (campoCantidad) {
                        // if (producto.EXIST > 0) {
                        campoCantidad.readOnly = false;
                        campoCantidad.value = 0; // Valor inicial opcional
                    }
                    // Cerrar el modal
                    cerrarModal();
                    await completarPrecioProducto(producto.CVE_ART, filaTabla);
                } else {
                    // campoCantidad.readOnly = true;
                    // campoCantidad.value = "Sin existencias"; // Mensaje opcional
                    Swal.fire({
                        title: "Aviso",
                        text: `El producto "${producto.CVE_ART}" no tiene existencias disponibles.`,
                        icon: "warning",
                        confirmButtonText: "Entendido",
                    });
                }
            };
            tablaProductos.appendChild(fila);
        });
    }
    // Evento para actualizar la tabla al escribir en el campo de búsqueda
    campoBusqueda.addEventListener("input", () => {
        renderProductos(campoBusqueda.value);
    });
    // Renderizar productos inicialmente
    renderProductos();
}
// Cierra el modal usando la API de Bootstrap
function cerrarModal() {
    const modal = bootstrap.Modal.getInstance(
        document.getElementById("modalProductos")
    );
    if (modal) {
        modal.hide();
    }
}
// Variables globales
let clienteSeleccionado = false;
let clienteId = null; // Variable para almacenar el ID del cliente
let clientesData = []; // Para almacenar los datos originales de los clientes

document.addEventListener("click", function (e) {
    const sugerencias = document.getElementById("clientesSugeridos");
    const clienteInput = document.getElementById("cliente");

    if (!sugerencias.contains(e.target) && !clienteInput.contains(e.target)) {
        sugerencias.classList.add("d-none");
    }
});

$("#AyudaCondicion").click(function () {
    event.preventDefault();
    Swal.fire({
        title: "Ayuda",
        text: "Podrás capturar la condición bajo la cual se efectuará el pago del cliente (por ejemplo, Efectivo, Crédito, Cóbrese o Devuélvase - C.O.D., etc.). ",
        icon: "info",
        confirmButtonText: "Entendido",
    });
});

$("#AyudaDescuento").click(function () {
    event.preventDefault();
    Swal.fire({
        title: "Ayuda",
        text: "Al momento de indicar la clave del cliente en el documento de venta, se muestra automáticamente el porcentaje de descuento asignado a dicho cliente desde el Módulo de clientes. ",
        icon: "info",
        confirmButtonText: "Entendido",
    });
});

$("#AyudaDescuentofin").click(function () {
    event.preventDefault();
    Swal.fire({
        title: "Ayuda",
        text: "Un descuento financiero, es aquel que se otorga en circunstancias particulares (por Ejemplo, por pronto pago o por adquirir grandes volúmenes de mercancía). ",
        icon: "info",
        confirmButtonText: "Entendido",
    });
});

$("#AyudaEnviarA").click(function () {
    event.preventDefault();
    Swal.fire({
        title: "Ayuda",
        text: "Escribe quien enviaran ",
        icon: "info",
        confirmButtonText: "Entendido",
    });
});

$("#cancelarPedido").click(function () {
    window.location.href = "Remisiones.php";
});
function obtenerDatosPedido(pedidoID) {
    $.post(
        "../Servidor/PHP/ventas.php",
        {
            numFuncion: 2, // Función para obtener el pedido por ID
            pedidoID: pedidoID,
        },
        function (response) {
            console.log("Respuesta cruda:", response); // 👈 Imprime lo que llega
            if (response.success) {
                const pedido = response.pedido;
                console.log("Datos del pedido:", pedido);

                // Cargar datos del cliente

                document.getElementById("nombre").value = pedido.NOMBRE_CLIENTE || "";
                document.getElementById("rfc").value = pedido.RFC || "";
                document.getElementById("calle").value = pedido.CALLE || "";
                document.getElementById("numE").value = pedido.NUMEXT || "";
                document.getElementById("colonia").value = pedido.COLONIA || "";
                document.getElementById("codigoPostal").value = pedido.CODIGO || "";
                document.getElementById("pais").value = pedido.PAIS || "";
                document.getElementById("condicion").value = pedido.CONDICION || "";
                document.getElementById("almacen").value = pedido.NUM_ALMA || "";
                document.getElementById("comision").value = pedido.COM_TOT || "";
                document.getElementById("diaAlta").value = pedido.FECHA_DOC || "";
                document.getElementById("entrega").value = pedido.FECHA_ENT || "";
                document.getElementById("numero").value = pedido.FOLIO || "";

                //document.getElementById("enviar").value = pedido.CALLE_ENVIO || "";
                document.getElementById("descuentoCliente").value =
                    pedido.DESCUENTO || "";
                document.getElementById("cliente").value = pedido.CLAVE || "";
                //document.getElementById("descuentofin").value = pedido.DES_FIN || "";
                document.getElementById("cliente").value = pedido.CVE_CLPV || "";
                document.getElementById("supedido").value = pedido.CONDICION || "";
                //document.getElementById("esquema").value = pedido.CONDICION || "";

                // Actualizar estado de cliente seleccionado en sessionStorage
                sessionStorage.setItem("clienteSeleccionado", true);

                // Cargar las partidas existentes
                //cargarPartidas(pedido.partidas);
                //alert("Datos del pedido cargados con éxito");

                console.log("Datos del pedido cargados correctamente.");

                $("#datosEnvio").prop("disabled", false);
                obtenerDatosEnvioEditar(pedido.DAT_ENVIO); // Función para cargar partidas del pedido
            } else {
                Swal.fire({
                    title: "Aviso",
                    text: "No se pudo cargar el pedido.",
                    icon: "warning",
                    confirmButtonText: "Aceptar",
                });
                //alert("No se pudo cargar el pedido: " + response.message);
            }
        },
        "json"
    ).fail(function (jqXHR, textStatus, errorThrown) {
        //console.log(errorThrown);
        Swal.fire({
            title: "Aviso",
            text: "Error al cargar el pedido.",
            icon: "error",
            confirmButtonText: "Aceptar",
        });
        //alert("Error al cargar el pedido: " + textStatus + " " + errorThrown);
        console.log("Error al cargar el pedido: " + textStatus + " " + errorThrown);
    });
}
function obtenerDatosEnvioEditar(envioID) {
    //
    $("#datosEnvio").prop("disabled", false);
    $("#selectDatosEnvio").prop("disabled", true);

    $.post(
        "../Servidor/PHP/clientes.php",
        {
            numFuncion: 11, // Función para obtener el pedido por ID
            envioID: envioID,
        },
        function (response) {
            if (response.success) {
                //console.log("Respuesta: ", response.data);
                const pedido = response.data;

                document.getElementById("enviar").value = pedido.CALLE || "";

            } else {
                Swal.fire({
                    title: "Aviso",
                    text: "No se Pudo cargar los Datos de Envio.",
                    icon: "warning",
                    confirmButtonText: "Aceptar",
                });
                //alert("No se pudo cargar el pedido: " + response.message);
            }
        },
        "json"
    ).fail(function (jqXHR, textStatus, errorThrown) {
        //console.log(errorThrown);
        Swal.fire({
            title: "Aviso",
            text: "Error al cargar el pedido.",
            icon: "error",
            confirmButtonText: "Aceptar",
        });
        //alert("Error al cargar el pedido: " + textStatus + " " + errorThrown);
        console.log("Error al cargar el pedido: " + textStatus + " " + errorThrown);
    });
}
function cargarPartidasPedido(pedidoID) {
    $.post(
        "../Servidor/PHP/ventas.php",
        {
            numFuncion: "3",
            accion: "obtenerPartidas",
            clavePedido: pedidoID,
        },
        function (response) {
            if (response.success) {
                const partidas = response.partidas;
                partidasData = [...partidas]; // Almacena las partidas en el array global
                actualizarTablaPartidas(pedidoID); // Actualiza la tabla visualmente
                console.log("Partidas cargadas correctamente.");
            } else {
                console.error("Error al obtener partidas:", response.message);
                Swal.fire({
                    title: "Aviso",
                    text: "No se pudieron cargar las partidas.",
                    icon: "warning",
                    confirmButtonText: "Aceptar",
                });
                //alert("No se pudieron cargar las partidas: " + response.message);
            }
        },
        "json"
    ).fail(function (jqXHR, textStatus, errorThrown) {
        console.error("Error al cargar las partidas:", textStatus, errorThrown);
        Swal.fire({
            title: "Aviso",
            text: "Error al cargar las partidas.",
            icon: "error",
            confirmButtonText: "Aceptar",
        });
        //alert("Error al cargar las partidas: " + textStatus + " " + errorThrown);
    });
}
function actualizarTablaPartidas(pedidoID) {
    const tablaProductos = document.querySelector("#tablaProductos tbody");
    tablaProductos.innerHTML = ""; // Limpia la tabla

    partidasData.forEach((partida) => {
        const nuevaFila = document.createElement("tr");
        nuevaFila.setAttribute("data-num-par", partida.NUM_PAR); // Identifica cada fila por NUM_PAR

        nuevaFila.innerHTML = `
    <td>
        <div class="d-flex flex-column position-relative">
            <div class="d-flex align-items-center">
                <input type="text" class="producto" placeholder="" value="${partida.CVE_ART}" oninput="mostrarSugerencias(this)" readonly />
            </div>
            <ul class="lista-sugerencias position-absolute bg-white list-unstyled border border-secondary mt-1 p-2 d-none"></ul>
        </div>
    </td>
    <td><input type="number" class="cantidad" value="${partida.CANT}" style="text-align: right;" readonly /></td>
    <td><input type="text" class="unidad" value="${partida.UNI_VENTA}" readonly /></td>
    <td><input type="number" class="descuento" value="${partida.DESC1}" style="text-align: right;" readonly /></td>
    <td><input type="number" class="iva" value="${partida.IMPU4}" style="text-align: right;" readonly /></td>
    
    <td><input type="number" class="precioUnidad" value="${partida.PREC}" style="text-align: right;" readonly /></td>
    <td><input type="number" class="subtotalPartida" value="${partida.TOT_PARTIDA}" style="text-align: right;" readonly /></td>`;

        // Validar que la cantidad no sea negativa
        const cantidadInput = nuevaFila.querySelector(".cantidad");
        cantidadInput.addEventListener("input", () => {
            if (parseFloat(cantidadInput.value) < 0) {
                Swal.fire({
                    title: "Aviso",
                    text: "La cantidad no puede ser negativa.",
                    icon: "error",
                    confirmButtonText: "Entendido",
                });
                cantidadInput.value = 0; // Restablecer el valor a 0
            } else {
                calcularSubtotal(nuevaFila); // Recalcular subtotal si el valor es válido
            }
        });
        tablaProductos.appendChild(nuevaFila);
    });
}
document.addEventListener("DOMContentLoaded", function () {
    // Verificar si estamos en la página de creación o edición de pedidos
    if (window.location.pathname.includes("verPedido.php")) {
        // Obtener parámetros de la URL
        const urlParams = new URLSearchParams(window.location.search);
        const pedidoID = urlParams.get("pedidoID"); // Puede ser null si no está definido

        console.log("ID del pedido recibido:", pedidoID); // Log en consola para depuración

        if (pedidoID) {
            // Si es un pedido existente (pedidoID no es null)
            console.log("Cargando datos del pedido existente...");
            obtenerDatosPedido(pedidoID); // Función para cargar datos del pedido
            cargarPartidasPedido(pedidoID); // Función para cargar partidas del pedido
            $("#datosEnvio").prop("disabled", false);
            //obtenerDatosEnvioEditar(pedidoID); // Función para cargar partidas del pedido
        }
    }
});