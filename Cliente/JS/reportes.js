const noEmpresa = sessionStorage.getItem("noEmpresaSeleccionada");

let filtroFecha = "";
let filtroVendedor = "";
let filtroCliente = "";

function datosReportes(limpiarTabla = true) {
    lineaSeleccionada = null;
    const tabla = document.getElementById("datosReportes");
    const numColumns = tabla.querySelector("thead")
        ? tabla.querySelector("thead").rows[0].cells.length
        : 13;

    const spinnerHTML = `<svg viewBox="25 25 50 50" style="width:40px;height:40px;"><circle r="20" cy="50" cx="50"></circle></svg>`;
    const spinnerRow = `<tr><td colspan="${numColumns}" style="text-align:center;">${spinnerHTML}</td></tr>`;

    if (limpiarTabla) {
        tabla.innerHTML = spinnerRow;
    } else {
        tabla.insertAdjacentHTML("beforeend", spinnerRow);
    }

    $.post(
        "../Servidor/PHP/reportesLineas.php",
        {
            numFuncion: "1",
            noEmpresa: noEmpresa,
            filtroFecha: filtroFecha,
            filtroCliente: filtroCliente

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
                        const row = document.createElement("tr");
                        row.innerHTML = `
                            <td class="linea-click" style="cursor:pointer;" data-linea="${reporte.CVE_LIN}">
                              ${reporte.DESC_LIN || ""}
                              <i class='bx bx-chevron-down'></i>
                            </td>
                            <td>${reporte[1] || 0}</td>
                            <td>${reporte[2] || 0}</td>
                            <td>${reporte[3] || 0}</td>
                            <td>${reporte[4] || 0}</td>
                            <td>${reporte[5] || 0}</td>
                            <td>${reporte[6] || 0}</td>
                            <td>${reporte[7] || 0}</td>
                            <td>${reporte[8] || 0}</td>
                            <td>${reporte[9] || 0}</td>
                            <td>${reporte[10] || 0}</td>
                            <td>${reporte[11] || 0}</td>
                            <td>${reporte[12] || 0}</td>
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
function cargarProductosLinea(cveLinea, limpiarTabla = true) {
    lineaSeleccionada = cveLinea;

    const tabla = document.getElementById("datosReportes");
    const numColumns = tabla.querySelector("thead")
        ? tabla.querySelector("thead").rows[0].cells.length
        : 13;

    const spinnerHTML = `<svg viewBox="25 25 50 50" style="width:40px;height:40px;"><circle r="20" cy="50" cx="50"></circle></svg>`;
    const spinnerRow = `<tr><td colspan="${numColumns}" style="text-align:center;">${spinnerHTML}</td></tr>`;

    if (limpiarTabla) {
        tabla.innerHTML = spinnerRow;
    } else {
        tabla.insertAdjacentHTML("beforeend", spinnerRow);
    }

    $.post(
        "../Servidor/PHP/reportesLineas.php",
        {
            numFuncion: "2",
            noEmpresa: noEmpresa,
            cveLinea: cveLinea,
            filtroFecha: filtroFecha,
            filtroCliente: filtroCliente,
            lineaSeleccionada: lineaSeleccionada
        },
        function (response) {
            try {
                if (typeof response === "string") response = JSON.parse(response);

                if (response.success) {
                    if (limpiarTabla) {
                        tabla.innerHTML = "";
                    } else {
                        const lastRow = tabla.lastElementChild;
                        if (lastRow) tabla.removeChild(lastRow);
                    }

                    const fragment = document.createDocumentFragment();

                    // Pintar resumen aunque data esté vacía
                    if (response.resumen) {
                        const resumenRow = document.createElement("tr");
                        resumenRow.style.fontWeight = "bold";

                        let columnasMeses = "";
                        for (let i = 1; i <= 12; i++) {
                            columnasMeses += `<td>${response.resumen[`mes_${i}`] || 0}</td>`;
                        }

                        resumenRow.innerHTML = `
                            <td style="cursor:pointer;" onclick="datosReportes(true)">
                              ${response.resumen.descripcionLinea}
                              <i class='bx bx-chevron-up'></i>
                            </td>
                            ${columnasMeses}
                        `;
                        fragment.appendChild(resumenRow);
                    }

                    // Ahora procesar data si hay
                    if (response.data && response.data.length > 0) {
                        response.data.forEach((reporte) => {
                            const row = document.createElement("tr");
                            row.innerHTML = `
                                <td>${reporte.CVE_ART}</td>
                                <td>${reporte[1] || 0}</td>
                                <td>${reporte[2] || 0}</td>
                                <td>${reporte[3] || 0}</td>
                                <td>${reporte[4] || 0}</td>
                                <td>${reporte[5] || 0}</td>
                                <td>${reporte[6] || 0}</td>
                                <td>${reporte[7] || 0}</td>
                                <td>${reporte[8] || 0}</td>
                                <td>${reporte[9] || 0}</td>
                                <td>${reporte[10] || 0}</td>
                                <td>${reporte[11] || 0}</td>
                                <td>${reporte[12] || 0}</td>
                            `;
                            fragment.appendChild(row);
                        });
                    }

                    tabla.appendChild(fragment);
                }

                else {
                    mostrarSinDatosReportes();
                }
            } catch (error) {
                console.error("Error al procesar JSON:", error);
                mostrarSinDatosReportes();
            }
        },
        "json"
    ).fail(function (jqXHR, textStatus, errorThrown) {
        console.error("Error al cargar productos de línea:", textStatus, errorThrown);
    });
}

let lineaSeleccionada = null;

$(document).on("click", ".linea-click", function () {
    const cveLinea = $(this).data("linea");
    if (!cveLinea) return;
    cargarProductosLinea(cveLinea, true);
});

$(document).on("change", "#filtroFecha, #filtroVendedor, #filtroClientes", function () {
    filtroFecha = $("#filtroFecha").val();
    filtroVendedor = $("#filtroVendedor").val();
    filtroCliente = $("#filtroClientes").val();

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
        // Ejecutar según si hay línea seleccionada
        if (lineaSeleccionada) {
            cargarProductosLinea(lineaSeleccionada, true);
        } else {
            datosReportes(true);
        }
    }
}

function llenarFiltroVendedor() {
    $.ajax({
        url: "../Servidor/PHP/usuarios.php",
        method: "GET",
        data: { numFuncion: "13" }, // Obtener todos los vendedores disponibles
        success: function (responseVendedores) {
            console.log("Respuesta del servidor (vendedores):", responseVendedores); // DEBUG

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
            console.log("Respuesta del servidor (clientes):", responseClientes); // DEBUG
            try {
                const res = typeof responseClientes === "string"
                    ? JSON.parse(responseClientes)
                    : responseClientes;

                if (res.success && Array.isArray(res.data)) {
                    const selectCliente = $("#filtroClientes");
                    selectCliente.empty();

                    res.data.forEach((cliente, index) => {
                        const selected = index === 0 ? 'selected' : '';
                        selectCliente.append(
                            `<option value="${cliente.CLAVE}" ${selected}>${cliente.NOMBRE}</option>`
                        );
                    });

                    // 👉 Establecer valores de filtro globales
                    if (res.data.length > 0) {
                        // Obtener año actual
                        const anioActual = new Date().getFullYear();

                        // Obtener el primer año de compra del primer cliente
                        const primerAno = parseInt(res.data[0].PrimerAnoCompra) || anioActual;

                        // Llenar el select de años
                        const selectFecha = $("#filtroFecha");
                        selectFecha.empty(); // Limpiar opciones anteriores

                        for (let anio = anioActual; anio >= primerAno; anio--) {
                            selectFecha.append(`<option value="${anio}">${anio}</option>`);
                        }

                        $("#filtroClientes").val(res.data[0].CLAVE);
                        filtroCliente = res.data[0].CLAVE;
                    }

                    // 👇 Mover aquí la llamada a datosReportes
                    filtroFecha = $("#filtroFecha").val() || "Hoy";
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