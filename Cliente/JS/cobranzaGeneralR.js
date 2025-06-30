const noEmpresa = sessionStorage.getItem("noEmpresaSeleccionada");

let filtroFechaInicio = "";
let filtroFechaFin = "";
let filtroVendedor = "";
let filtroCliente = 0;
const token = document.getElementById("csrf_token").value;

function datosReportes(limpiarTabla = true) {
    if (filtroCliente && filtroCliente !== "") {
        cargarDatosCliente(filtroCliente, token);
    } else {
        document.getElementById("infoCliente").style.display = 'none';
        limpiarTarjetaCliente();
    }

    const tabla = document.getElementById("datosReportes");
    const numColumns = tabla.parentElement.querySelector("thead")
        ? tabla.parentElement.querySelector("thead").rows[0].cells.length
        : 11;

    const spinnerHTML = `<svg viewBox="25 25 50 50" style="width:40px;height:40px;"><circle r="20" cy="50" cx="50"></circle></svg>`;
    const spinnerRow = `<tr><td colspan="${numColumns}" style="text-align:center;">${spinnerHTML}</td></tr>`;

    if (limpiarTabla) {
        tabla.innerHTML = spinnerRow;
    } else {
        tabla.insertAdjacentHTML("beforeend", spinnerRow);
    }

    $.post(
        "../Servidor/PHP/reportesGeneral.php",
        {
            numFuncion: "4",
            noEmpresa: noEmpresa,
            filtroFechaInicio: filtroFechaInicio,
            filtroFechaFin: filtroFechaFin,
            filtroCliente: filtroCliente,
            filtroVendedor: filtroVendedor,
        },
        function (response) {
            try {
                if (typeof response === "string") response = JSON.parse(response);

                let totalCargos = 0;
                let totalAbonos = 0;
                let totalSaldos = 0;

                if (response.success && response.data && response.data.length > 0) {
                    if (limpiarTabla) tabla.innerHTML = "";
                    else {
                        const lastRow = tabla.lastElementChild;
                        if (lastRow) tabla.removeChild(lastRow);
                    }

                    const fragment = document.createDocumentFragment();

                    // Agrupa las facturas por cliente si el filtro es TODOS
                    if (!filtroCliente || filtroCliente === "") {
                        // Remueve tfoot anterior si existía
                        const tfootExistente = tabla.parentElement.querySelector("tfoot");
                        if (tfootExistente) tfootExistente.remove();

                        let currentCliente = null;
                        let totalCargosCliente = 0;
                        let totalAbonosCliente = 0;
                        let totalSaldosCliente = 0;
                        let rowsCliente = [];

                        response.data.forEach((reporte, i) => {
                            const clienteKey = `${reporte.CLAVE}-${reporte.NOMBRE_CLIENTE}-${reporte.TELEFONO_CLIENTE}`;
                            // Si cambia de cliente (o es el primero), procesa y resetea totales
                            if (clienteKey !== currentCliente) {
                                // Si ya hay datos de un cliente anterior, imprime totales antes de cambiar
                                if (currentCliente !== null) {
                                    // Fila de totales por cliente (puedes ajustar el colspan)
                                    const totalRow = document.createElement("tr");
                                    totalRow.innerHTML = `
                                        <td style="text-align:right; padding-left: 24px"">Totales cliente:</td>
                                        <td colspan="6"></td>
                                        <td style="text-align:right;">${totalCargosCliente.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })}</td>
                                        <td style="text-align:right;">${totalAbonosCliente.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })}</td>
                                        <td style="text-align:right;">${totalSaldosCliente.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })}</td>
                                        <td></td>
                                    `;
                                    rowsCliente.push(totalRow);
                                    // Agrega todas las filas al fragmento
                                    rowsCliente.forEach(r => fragment.appendChild(r));
                                    rowsCliente = [];
                                }
                                // Fila cabecera cliente
                                currentCliente = clienteKey;
                                totalCargosCliente = 0;
                                totalAbonosCliente = 0;
                                totalSaldosCliente = 0;
                                const rowCliente = document.createElement("tr");
                                rowCliente.innerHTML = `
                                  <td colspan="11" style="padding:16px 16px; display: table-cell">
                                    <div style="display: flex; justify-content: space-around; align-items: center; width: 100%; font-weight: bold">
                                      <span style="display:flex;align-items:center;gap:8px;">
                                        <i class="fa-solid fa-user"></i>
                                        <span>${reporte.CLAVE}</span>
                                        <span>${reporte.NOMBRE_CLIENTE}</span>
                                      </span>
                                      <span>${reporte.TELEFONO_CLIENTE}</span>
                                    </div>
                                  </td>
                                `;

                                rowsCliente.push(rowCliente);
                            }
                            // Factura
                            const cargos = Number(reporte.CARGOS || 0);
                            const abonos = Number(reporte.ABONOS || 0);
                            const saldo = Number(reporte.SALDO || 0);
                            const moneda = reporte.MONEDA || "";

                            totalCargosCliente += cargos;
                            totalAbonosCliente += abonos;
                            totalSaldosCliente += saldo;

                            const row = document.createElement("tr");
                            row.innerHTML = `
                                <td style="display: table-cell; padding-left: 24px">${reporte.CLAVE || ""}</td>
                                <td>${reporte.TIPO || ""}</td>
                                <td>${reporte.CONCEPTO || ""}</td>
                                <td>${reporte.DOCUMENTO || ""}</td>
                                <td>${reporte.NUM || ""}</td>
                                <td>${reporte.FECHA_APLICACION || ""}</td>
                                <td>${reporte.FECHA_VENCIMIENTO || ""}</td>
                                <td style="text-align:right;">${cargos.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })}</td>
                                <td style="text-align:right;">${abonos.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })}</td>
                                <td style="text-align:right;">${saldo.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })}</td>
                                <td>${moneda}</td>
                            `;
                            rowsCliente.push(row);

                            // Si es el último registro, imprime totales cliente
                            if (i === response.data.length - 1) {
                                const totalRow = document.createElement("tr");
                                totalRow.innerHTML = `
                                    <td style="text-align:right; padding-left: 24px">Total Cliente:</td>
                                    <td colspan="6"></td>
                                    <td style="text-align:right;">${totalCargosCliente.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })}</td>
                                    <td style="text-align:right;">${totalAbonosCliente.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })}</td>
                                    <td style="text-align:right;">${totalSaldosCliente.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })}</td>
                                    <td></td>
                                `;
                                rowsCliente.push(totalRow);
                                rowsCliente.forEach(r => fragment.appendChild(r));
                            }
                        });

                        tabla.appendChild(fragment);
                    } else {
                        // Crea tfoot
                        const tfoot = document.createElement("tfoot");
                        tfoot.innerHTML = `
                            <tr id="totalesRow">
                                <td style="text-align:right;">Totales:</td>
                                <td colspan="6" style="text-align:right;"></td>
                                <td id="totalCargos" style="text-align:right;">${totalCargos.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })}</td>
                                <td id="totalAbonos" style="text-align:right;">${totalAbonos.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })}</td>
                                <td id="totalSaldos" style="text-align:right;">${totalSaldos.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })}</td>
                                <td></td>
                            </tr>
                        `;
                        tabla.parentElement.appendChild(tfoot);
                        // Si solo es de un cliente, imprime normal
                        response.data.forEach((reporte) => {
                            const cargos = Number(reporte.CARGOS || 0);
                            const abonos = Number(reporte.ABONOS || 0);
                            const saldo = Number(reporte.SALDO || 0);
                            const moneda = reporte.MONEDA || "";

                            totalCargos += cargos;
                            totalAbonos += abonos;
                            totalSaldos += saldo;

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
                                <td>${moneda}</td>
                            `;
                            fragment.appendChild(row);
                        });
                    }

                    tabla.appendChild(fragment);

                    // Totales


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
        document.getElementById("totalCargos").textContent = "$0.00";
        document.getElementById("totalAbonos").textContent = "$0.00";
        document.getElementById("totalSaldos").textContent = "$0.00";
    });
}

function cargarDatosCliente(clienteId, token) {
    document.getElementById("infoCliente").style.display = 'flex';
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
        : 11;

    row.innerHTML = `<td colspan="${numColumns}" style="text-align: center; display: table-cell">No hay datos disponibles</td>`;
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
                    const selectCliente = $("#filtroClientes");
                    selectCliente.empty();

                    selectCliente.append(`<option value="">Todos</option>`);

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

                        $("#filtroClientes").val("");  // Selecciona "Todos"
                        filtroCliente = "";            // Valor vacío = Todos los clientes
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