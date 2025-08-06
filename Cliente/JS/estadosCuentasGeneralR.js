const noEmpresa = sessionStorage.getItem("noEmpresaSeleccionada");

let filtroFechaInicio = "";
let filtroFechaFin = "";
let filtroVendedor = "";
let filtroCliente = 0;
const inputCliente = $("#cliente");
const clearButton = $("#clearInput");
const suggestionsList = $("#clientesSugeridos");
const token = document.getElementById("csrf_token").value;
// Vincular los eventos de búsqueda y cambio de criterio
document.addEventListener("DOMContentLoaded", () => {
    document
        .getElementById("campoBusquedaClientes")
        .addEventListener("input", filtrarClientes);
    document
        .getElementById("filtroCriterioClientes")
        .addEventListener("change", filtrarClientes);

    const clienteInput = document.getElementById("filtroClientes");
    clienteInput.addEventListener("input", () => {
        if (!clienteSeleccionado) {
            showCustomerSuggestions();
        }
    });
});

$(document).ready(function() {
    // Referencias a los elementos UL donde se mostrarán las sugerencias
    const suggestionsList = $('#clientesSugeridos');
    let highlightedIndex = -1; // Índice del elemento actualmente resaltado

    // Evento que se dispara al escribir en el campo de cliente
    $('#filtroClientes').on('input', function() {
        const clienteInput = $(this).val().trim(); // Valor ingresado
        const claveUsuario = '<?php echo $claveUsuario ?>'; // Clave de usuario PHP inyectada
        if (clienteInput.length >= 1) {
            // Si hay al menos un carácter, solicitamos sugerencias al servidor
            $.ajax({
                url: '../Servidor/PHP/ventas.php',
                type: 'POST',
                data: {
                    cliente: clienteInput, // Texto a buscar
                    numFuncion: '4', // Código de función para "buscar cliente"
                    clave: claveUsuario // Clave de usuario para filtrar resultados
                },
                success: function(response) {
                    try {
                        // Si la respuesta viene como string, intentamos parsear a JSON
                        if (typeof response === 'string') {
                            response = JSON.parse(response);
                        }
                    } catch (e) {
                        console.error("Error al parsear la respuesta JSON", e);
                        return;
                    }

                    // Si la búsqueda tuvo éxito y devolvió un arreglo con al menos un cliente...
                    if (response.success && Array.isArray(response.cliente) && response.cliente.length > 0) {
                        suggestionsList.empty().show(); // Limpiamos y mostramos el listado
                        highlightedIndex = -1; // Reiniciamos índice resaltado

                        // Iteramos sobre cada cliente encontrado y creamos un <li> para cada uno
                        response.cliente.forEach((cliente, index) => {
                            const listItem = $('<li></li>')
                                .text(`${cliente.CLAVE.trim()} - ${cliente.NOMBRE}`) // Texto visible
                                .attr('data-index', index) // Índice en el arreglo
                                .attr('data-cliente', JSON.stringify(cliente)) // Datos completos JSON en atributo
                                .on('click', function() {
                                    // Al hacer clic, seleccionamos ese cliente
                                    seleccionarClienteDesdeSugerencia(cliente);
                                });

                            suggestionsList.append(listItem);
                        });
                    } else {
                        // Si no hay coincidencias, ocultamos el listado
                        suggestionsList.empty().hide();
                    }
                },
                error: function() {
                    console.error("Error en la solicitud AJAX para sugerencias");
                    suggestionsList.empty().hide(); // Ocultamos ante fallo
                }
            });
        } else {
            // Si el input queda vacío, limpamos y ocultamos las sugerencias
            suggestionsList.empty().hide();
        }
    });

    // Manejo de navegación con teclado en el campo de cliente
    $('#filtroClientes').on('keydown', function(e) {
        const items = suggestionsList.find('li');
        if (!items.length) return; // Si no hay sugerencias, nada que hacer

        if (e.key === 'ArrowDown') {
            // Flecha abajo: avanzamos índice (circular) y resaltamos
            highlightedIndex = (highlightedIndex + 1) % items.length;
            actualizarDestacado(items, highlightedIndex);
            e.preventDefault();
        } else if (e.key === 'ArrowUp') {
            // Flecha arriba: retrocedemos índice (circular) y resaltamos
            highlightedIndex = (highlightedIndex - 1 + items.length) % items.length;
            actualizarDestacado(items, highlightedIndex);
            e.preventDefault();
        } else if (e.key === 'Tab' || e.key === 'Enter') {
            // Tab o Enter: si hay elemento resaltado, lo seleccionamos
            if (highlightedIndex >= 0) {
                const clienteSeleccionado = JSON.parse(
                    $(items[highlightedIndex]).attr('data-cliente')
                );
                seleccionarClienteDesdeSugerencia(clienteSeleccionado);
                suggestionsList.empty().hide();
            }
            e.preventDefault();
        }
    });

    // Función para aplicar/remover la clase "highlighted" al <li> correcto
    function actualizarDestacado(items, index) {
        items.removeClass('highlighted');
        $(items[index]).addClass('highlighted');
    }

    // Si se hace clic fuera del campo #cliente, ocultamos la lista de sugerencias de clientes
    $(document).on('click', function(event) {
        if (!$(event.target).closest('#filtroClientes').length) {
            $('#clientesSugeridos').empty().hide();
        }
    });

    // Evento para el botón "X" que limpia el input de cliente y sus campos relacionados
    $('#clearInput').on('click', function() {
        $('#filtroClientes').val(''); // Limpia campo cliente
        $(this).hide(); // Oculta el botón "X"
    });
});
function datosReportes(limpiarTabla = true) {

    // --- Solicitud para llenar la tarjeta del cliente ---
    if (filtroCliente && filtroCliente !== "") {
        cargarDatosCliente(filtroCliente, token);
    } else {
        limpiarTarjetaCliente();
    }

    const tabla = document.getElementById("datosReportes");
    const numColumns = tabla.parentElement.querySelector("thead")
        ? tabla.parentElement.querySelector("thead").rows[0].cells.length
        : 10;

    const spinnerHTML = `<svg viewBox="25 25 50 50" style="width:40px;height:40px;"><circle r="20" cy="50" cx="50"></circle></svg>`;
    const spinnerRow = `<tr><td colspan="${numColumns}" style="text-align:center;">${spinnerHTML}</td></tr>`;

    if (limpiarTabla) {
        tabla.innerHTML = spinnerRow;
    } else {
        tabla.insertAdjacentHTML("beforeend", spinnerRow);
    }

    // --- Solicitud de reportes ---
    $.post(
        "../Servidor/PHP/reportesGeneral.php",
        {
            numFuncion: "3",
            noEmpresa: noEmpresa,
            filtroFechaInicio: filtroFechaInicio,
            filtroFechaFin: filtroFechaFin,
            filtroCliente: filtroCliente,
        },
        function (response) {
            try {
                if (typeof response === "string") response = JSON.parse(response);

                if (response.success && response.data && response.data.length > 0) {
                    if (limpiarTabla) {
                        tabla.innerHTML = "";
                    } else {
                        const lastRow = tabla.lastElementChild;
                        if (lastRow) tabla.removeChild(lastRow);
                    }

                    const fragment = document.createDocumentFragment();
                    response.data.forEach((reporte) => {
                        // Aquí asegúrate de usar los nombres del backend
                        const cargos = Number(reporte.CARGOS || 0);
                        const abonos = Number(reporte.ABONOS || 0);
                        const saldo = Number(reporte.SALDO || 0);

                        const row = document.createElement("tr");
                        row.innerHTML = `
                            <td>${reporte.CLAVE || ""}</td>
                            <td>${reporte.TIPO || ""}</td>
                            <td>${reporte.CONCEPTO || ""}</td>
                            <td>${reporte.DOCUMENTO || ""}</td>
                            <td>${reporte.NUM || ""}</td>
                            <td>${reporte.FECHA_APLICACION || ""}</td>
                            <td>${reporte.FECHA_VENCIMIENTO || ""}</td>
                            <td style="text-align:right;">${cargos.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })}</td>
                            <td style="text-align:right;">${abonos.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })}</td>
                            <td style="text-align:right;">${saldo.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })}</td>
                        `;
                        fragment.appendChild(row);
                    });

                    tabla.appendChild(fragment);
                } else {
                    mostrarSinDatosReportes();
                }
            } catch (error) {
                console.error("Error al procesar JSON:", error);
                mostrarSinDatosReportes();
            }
        },
        "json"
    ).fail(function (jqXHR, textStatus, errorThrown) {
        console.error("Error en la solicitud:", textStatus, errorThrown);
        mostrarSinDatosReportes();
    });
}

function cargarDatosCliente(clienteId, token) {
    // Construye la URL con parámetros
    const url = `../Servidor/PHP/clientes.php?clave=${(clienteId)}&numFuncion=2&token=${(token)}`;
    fetch(url)
        .then((response) => response.json())
        .then((data) => {
            if (data.success && data.cliente) {
                const c = data.cliente;

                // Llenar los campos de la tarjeta
                $("#clienteNombre").text(c.NOMBRE || "");
                $("#clienteClasificacion").text(c.CLASIFIC || "");
                $("#clienteDireccion").text([
                    c.CALLE || "",
                    c.COLONIA ? "Col. " + c.COLONIA : "",
                    c.MUNICIPIO || "",
                    c.ESTADO || ""
                ].filter(Boolean).join(", "));
                $("#clienteNumExterior").text(c.NUMEXT || "");
                $("#clienteCP").text(c.CODIGO || "");
                $("#clienteRFC").text(c.RFC || "");
                $("#clienteTelefono").text(c.TELEFONO || "");
                $("#clienteDiasCredito").text(c.DIASCRED || "");
                $("#clienteLimiteCredito").text(
                    c.LIMCRED !== undefined && c.LIMCRED !== null
                        ? Number(c.LIMCRED).toLocaleString('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 2 })
                        : "$0.00"
                );
                $("#clienteMoneda").text(c.MON_CRED || "MXN");
                $("#clienteTipoCambio").text(c.TIPO_CAMBIO || "1.00");
                $("#clienteSaldoDisp").text(
                    c.SALDO !== undefined && c.SALDO !== null
                        ? Number(c.SALDO).toLocaleString('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 2 })
                        : "$0.00"
                );
            } else {
                limpiarTarjetaCliente();
            }
        })
        .catch(() => limpiarTarjetaCliente());
}

// La función de limpiar la tarjeta sigue igual
function limpiarTarjetaCliente() {
    $("#clienteNombre").text("Nombre Cliente");
    $("#clienteClasificacion").text("Clasificación");
    $("#clienteDireccion").text("Dirección");
    $("#clienteNumExterior").text("");
    $("#clienteCP").text("");
    $("#clienteRFC").text("");
    $("#clienteTelefono").text("");
    $("#clienteDiasCredito").text("");
    $("#clienteLimiteCredito").text("");
    $("#clienteMoneda").text("");
    $("#clienteTipoCambio").text("");
    $("#clienteSaldoDisp").text("");
}

$(document).on("change", "#filtroFechaInicio, #filtroFechaFin, #filtroVendedor, #filtroClientes", function () {
    filtroFechaInicio = $("#filtroFechaInicio").val();
    filtroFechaFin = $("#filtroFechaFin").val();
    filtroVendedor = $("#filtroVendedor").val();
    filtroCliente = $("#cliente").val();

    datosReportes(true);
});
let partidasData = []; // Este contiene las partidas actuales del formulario

// CARGAR Los Datos
// Función para mostrar mensaje cuando no hay datos
function mostrarSinDatosReportes() {
    const tabla = document.getElementById("datosReportes");
    if (!tabla) {
        console.error("No se encontró el elemento con id 'datosReportes'");
        return;
    }
    tabla.innerHTML = "";

    const row = document.createElement("tr");
    const numColumns = tabla.querySelector("thead")
        ? tabla.querySelector("thead").rows[0].cells.length
        : 10;

    row.innerHTML = `<td colspan="${numColumns}" style="text-align: center;">No hay datos disponibles</td>`;
    tabla.appendChild(row);
}

let debounceTimeoutReportes;

function debouncedSearchReportes() {
    clearTimeout(debounceTimeoutReportes);
    debounceTimeoutReportes = setTimeout(() => {
        doSearchReportes(true);
    }, 500);
}

function doSearchReportes(limpiarTabla = true) {
    const searchText = document.getElementById("searchTerm").value.toLowerCase();
    const tabla = document.getElementById("datosReportes");
    const filas = tabla.getElementsByTagName("tr");

    if (searchText.length >= 2) {
        const resultados = [];

        for (let i = 0; i < filas.length; i++) {
            const celdas = filas[i].getElementsByTagName("td");
            let coincide = false;

            for (let j = 0; j < celdas.length; j++) {
                const texto = celdas[j].textContent || celdas[j].innerText;
                if (texto.toLowerCase().includes(searchText)) {
                    coincide = true;
                    break;
                }
            }

            if (coincide) {
                resultados.push(filas[i].cloneNode(true));
            }
        }

        if (limpiarTabla) {
            tabla.innerHTML = "";
        }

        if (resultados.length > 0) {
            const fragment = document.createDocumentFragment();
            resultados.forEach((fila) => fragment.appendChild(fila));
            tabla.appendChild(fragment);
        } else {
            tabla.innerHTML = `<tr><td colspan="13" style="text-align:center;">No se encontraron resultados</td></tr>`;
        }

    } else {
        datosReportes(true);

    }
}

function llenarFiltroVendedor() {
    $.ajax({
        url: "../Servidor/PHP/usuarios.php",
        method: "GET",
        data: { numFuncion: "13" }, // Obtener todos los vendedores disponibles
        success: function (responseVendedores) {
            try {
                const res = typeof responseVendedores === "string"
                    ? JSON.parse(responseVendedores)
                    : responseVendedores;

                if (res.success && Array.isArray(res.data)) {
                    const selectVendedor = $("#filtroVendedor");
                    selectVendedor.empty();

                    // Agregar opciones y seleccionar la primera automáticamente
                    res.data.forEach((vendedor, index) => {
                        const selected = index === 0 ? 'selected' : '';
                        selectVendedor.append(
                            `<option value="${vendedor.clave}" ${selected}>${vendedor.nombre}</option>`
                        );
                    });
                    llenarFiltroCliente();

                    // Si al menos un vendedor fue agregado, actualizar el valor del filtro
                    if (res.data.length > 0) {
                        $("#filtroVendedor").val(res.data[0].clave);
                    }

                } else {
                    Swal.fire({
                        icon: "warning",
                        title: "Aviso",
                        text: res.message || "No se encontraron vendedores.",
                    });
                }
            } catch (error) {
                console.error("Error al procesar los vendedores:", error);
            }
        },
        error: function () {
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "Error al obtener la lista de vendedores.",
            });
        },
    });
}


function llenarFiltroCliente() {
    const token = document.getElementById("csrf_token").value;

    $.ajax({
        url: "../Servidor/PHP/clientes.php",
        method: "POST",
        data: {
            numFuncion: 12,
            token: token
        },
        success: function (responseClientes) {
            try {
                const res = typeof responseClientes === "string"
                    ? JSON.parse(responseClientes)
                    : responseClientes;

                if (res.success && Array.isArray(res.data)) {
                    const selectCliente = $("#cliente");
                    selectCliente.empty();

                    res.data.forEach((cliente, index) => {
                        const selected = index === 0 ? 'selected' : '';
                        selectCliente.append(
                            `<option value="${cliente.CLAVE}" ${selected}>${cliente.NOMBRE}</option>`
                        );
                    });

                    // 👉 Establecer valores de filtro globales
                    if (res.data.length > 0) {
                        // Obtener primer año de compra y setear fechas
                        const primerAno = parseInt(res.data[0].PrimerAnoCompra) || new Date().getFullYear();
                        const primerFecha = `${primerAno}-01-01`;

                        const hoy = new Date();
                        const hoyStr = hoy.toISOString().substring(0, 10);

                        // Inputs de tipo date
                        const $fechaInicio = $("#filtroFechaInicio");
                        const $fechaFin = $("#filtroFechaFin");

                        $fechaInicio.attr("min", primerFecha);
                        $fechaInicio.attr("max", hoyStr);
                        $fechaInicio.val(primerFecha);

                        $fechaFin.attr("min", primerFecha);
                        $fechaFin.attr("max", hoyStr);
                        $fechaFin.val(hoyStr);

                        $("#cliente").val(res.data[0].CLAVE);
                        $("#filtroClientes").val(res.data[0].NOMBRE);
                        filtroCliente = res.data[0].CLAVE;
                    }

                    // 👇 Mover aquí la llamada a datosReportes
                    filtroFechaInicio = $("#filtroFechaInicio").val() || "";
                    filtroFechaFin = $("#filtroFechaFin").val() || "";
                    filtroVendedor = $("#filtroVendedor").val() || "";
                    datosReportes(true);

                } else {
                    Swal.fire({
                        icon: "warning",
                        title: "Aviso",
                        text: res.message || "No se encontraron clientes.",
                    });
                }
            } catch (error) {
                console.error("Error al procesar los clientes:", error);
            }
        },
        error: function () {
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "Error al obtener la lista de clientes.",
            });
        }
    });
}
// Mostrar/ocultar el botón "x"
function toggleClearButton() {
    if (inputCliente.val().trim() !== "") {
        clearButton.show();
    } else {
        clearButton.hide();
    }
}
// Limpiar todos los campos
function clearAllFields() {
    // Limpiar valores de los inputs
    $("#filtroClientes").val("");

    // Limpiar la lista de sugerencias
    suggestionsList.empty().hide();

    // Ocultar el botón "x"
    clearButton.hide();
}
let clienteSeleccionado = false;
let clienteId = null; // Variable para almacenar el ID del cliente
let clientesData = []; // Para almacenar los datos originales de los clientes

// Función para abrir el modal y cargar los clientes
function abrirModalClientes() {
    const modalElement = document.getElementById("modalClientes");
    const modal = new bootstrap.Modal(modalElement);
    const datosClientes = document.getElementById("datosClientes"); //Tbody de la tabla del modal
    const token = document.getElementById("csrf_token").value; //Token de seguridad

    // Solicitar datos al servidor
    $.post(
        "../Servidor/PHP/clientes.php",
        { numFuncion: "9", token: token },
        function (response) {
            try {
                if (response.success && response.data) {
                    clientesData = response.data; // Guardar los datos originales
                    datosClientes.innerHTML = ""; // Limpiar la tabla

                    // Renderizar los datos en la tabla
                    renderClientes(clientesData);
                } else {
                    datosClientes.innerHTML =
                        '<tr><td colspan="4">No se encontraron clientes</td></tr>';
                }
            } catch (error) {
                console.error("Error al cargar clientes:", error);
            }
        }
    );
    //Abrir modal
    modal.show();
}
// Filtrar clientes según la entrada de búsqueda en el modal
function filtrarClientes() {
    const criterio = document.getElementById("filtroCriterioClientes").value;
    const busqueda = document
        .getElementById("campoBusquedaClientes")
        .value.toLowerCase();

    const clientesFiltrados = clientesData.filter((cliente) => {
        const valor = cliente[criterio]?.toLowerCase() || "";
        return valor.includes(busqueda);
    });

    renderClientes(clientesFiltrados);
}
// Función para renderizar los clientes en la tabla
function renderClientes(clientes) {
    const datosClientes = document.getElementById("datosClientes");
    datosClientes.innerHTML = "";
    clientes.forEach((cliente) => {
        const fila = document.createElement("tr");
        //Contruir fila con datos
        fila.innerHTML = `
            <td>${cliente.CLAVE}</td>
            <td>${cliente.NOMBRE}</td>
            <td>${cliente.TELEFONO || "Sin teléfono"}</td>
            <td>$${parseFloat(cliente.SALDO || 0).toFixed(2)}</td>
        `;

        // Agregar evento de clic para seleccionar cliente desde el modal
        fila.addEventListener("click", () => seleccionarClienteDesdeModal(cliente));

        datosClientes.appendChild(fila);
    });
}
// Función para cerrar el modal
function cerrarModalClientes() {
    const modalElement = document.getElementById("modalClientes");
    const modal = bootstrap.Modal.getInstance(modalElement);
    modal.hide();
}
// Función para seleccionar un cliente desde el modal
function seleccionarClienteDesdeModal(cliente) {
    filtroCliente = cliente.CLAVE; // Guardar el ID del cliente
    $("#filtroClientes").val(cliente.NOMBRE);
    cerrarModalClientes(); // Cerrar el modal
    datosReportes(true);
}
// Función para mostrar sugerencias de clientes
function showCustomerSuggestions() {
    const clienteInput = document.getElementById("filtroClientes");
    const clienteInputValue = clienteInput.value.trim();
    const sugerencias = document.getElementById("clientesSugeridos");

    sugerencias.classList.remove("d-none"); // Mostrar las sugerencias

    // Generar las sugerencias en base al texto ingresado
    const clientesFiltrados = clientesData.filter((cliente) =>
        cliente.NOMBRE.toLowerCase().includes(clienteInputValue.toLowerCase())
    );

    sugerencias.innerHTML = ""; // Limpiar las sugerencias anteriores

    if (clientesFiltrados.length === 0) {
        sugerencias.innerHTML = "<li>No se encontraron coincidencias</li>";
    } else {
        clientesFiltrados.forEach((cliente) => {
            const sugerencia = document.createElement("li");
            sugerencia.textContent = `${cliente.CLAVE} - ${cliente.NOMBRE}`;
            sugerencia.classList.add("suggestion-item");

            // Evento para seleccionar cliente desde las sugerencias
            sugerencia.addEventListener("click", (e) => {
                e.stopPropagation(); // Evitar que el evento de clic global oculte las sugerencias
                seleccionarClienteDesdeSugerencia(cliente);
            });

            sugerencias.appendChild(sugerencia);
        });
    }
}
// Función para seleccionar un cliente desde las sugerencias
function seleccionarClienteDesdeSugerencia(cliente) {
    filtroCliente = cliente.CLAVE; // Guardar el ID del cliente
    $("#filtroClientes").val(cliente.NOMBRE);
    datosReportes(true);
}