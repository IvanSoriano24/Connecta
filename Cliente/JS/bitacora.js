const accionesPorModulo = {
    PEDIDOS: [
        { value: "TODAS", label: "Todas" },
        { value: "CREACION", label: "Creación" },
        { value: "EDICION", label: "Edición" },
        { value: "CANCELACION", label: "Cancelación" }
    ],
    CLIENTES: [],
    FACTURAS: []
};

let registrosBitacora = [];
let modalDetalle;
let paginaActual = 1;
let registrosPorPagina = 10;
let totalPaginas = 1;

document.addEventListener("DOMContentLoaded", () => {
    const moduloFiltro = document.getElementById("moduloFiltro");
    const accionFiltro = document.getElementById("accionFiltro");
    const folioFiltro = document.getElementById("folioFiltro");
    const fechaInicio = document.getElementById("fechaInicio");
    const fechaFin = document.getElementById("fechaFin");
    modalDetalle = new bootstrap.Modal(document.getElementById("modalDetalleBitacora"));

    moduloFiltro.addEventListener("change", () => {
        actualizarAcciones(moduloFiltro.value);
        manejarVisibilidadFolio(moduloFiltro.value);
        limpiarResultados();
    });

    accionFiltro.addEventListener("change", () => {
        const modulo = moduloFiltro.value;
        const accion = accionFiltro.value;
        if (modulo && accion) {
            paginaActual = 1;
            cargarBitacora(modulo, accion, folioFiltro.value.trim(), fechaInicio.value, fechaFin.value);
        } else {
            limpiarResultados();
        }
    });

    [folioFiltro, fechaInicio, fechaFin].forEach((campo) =>
        campo.addEventListener("input", () => {
            const modulo = moduloFiltro.value;
            const accion = accionFiltro.value;
            if (modulo && accion) {
                paginaActual = 1;
                cargarBitacora(modulo, accion, folioFiltro.value.trim(), fechaInicio.value, fechaFin.value);
            }
        })
    );

    document.getElementById("selectCantidadBitacora").addEventListener("change", (e) => {
        registrosPorPagina = Number(e.target.value) || 10;
        paginaActual = 1;
        const modulo = moduloFiltro.value;
        const accion = accionFiltro.value;
        if (modulo && accion) {
            cargarBitacora(modulo, accion, folioFiltro.value.trim(), fechaInicio.value, fechaFin.value);
        }
    });

    document.getElementById("tablaBitacora").addEventListener("click", (event) => {
        const btn = event.target.closest(".btn-detalle-bitacora");
        if (!btn) return;

        const index = btn.getAttribute("data-index");
        mostrarDetalleRegistro(index);
    });
});

function actualizarAcciones(modulo) {
    const accionFiltro = document.getElementById("accionFiltro");
    accionFiltro.innerHTML = "";

    if (!modulo || !accionesPorModulo[modulo] || accionesPorModulo[modulo].length === 0) {
        accionFiltro.disabled = true;
        accionFiltro.innerHTML = `<option value="">${modulo ? "Sin acciones disponibles" : "Selecciona un módulo primero"}</option>`;
        return;
    }

    accionFiltro.disabled = false;
    accionFiltro.innerHTML = `<option value="">Selecciona una opción</option>`;
    accionesPorModulo[modulo].forEach((accion) => {
        const option = document.createElement("option");
        option.value = accion.value;
        option.textContent = accion.label;
        accionFiltro.appendChild(option);
    });
}

function manejarVisibilidadFolio(modulo) {
    const wrapper = document.getElementById("folioWrapper");
    if (!wrapper) return;
    if (modulo === "PEDIDOS") {
        wrapper.style.display = "";
    } else {
        wrapper.style.display = "none";
        const folioFiltro = document.getElementById("folioFiltro");
        if (folioFiltro) {
            folioFiltro.value = "";
        }
    }
}

function mostrarEstado(mensaje, tipo = "muted") {
    const estado = document.getElementById("estadoBitacora");
    const tablaWrapper = document.getElementById("tablaBitacoraWrapper");
    estado.className = `estado-bitacora text-${tipo}`;
    estado.textContent = mensaje;
    tablaWrapper.classList.add("d-none");
}

function limpiarResultados() {
    registrosBitacora = [];
    document.getElementById("tablaBitacora").innerHTML = "";
    mostrarEstado("Completa los filtros para mostrar información.");
    ocultarPaginacion();
}

async function cargarBitacora(modulo, accion, folio = "", fechaInicio = "", fechaFin = "") {
    mostrarEstado("Consultando registros, por favor espera...", "primary");

    try {
        const response = await fetch("../Servidor/PHP/bitacoraConsulta.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ modulo, accion, folio, fechaInicio, fechaFin })
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || "No se pudo obtener la bitácora.");
        }

        registrosBitacora = data.data || [];
        totalPaginas = Math.max(1, Math.ceil(registrosBitacora.length / registrosPorPagina));
        if (paginaActual > totalPaginas) {
            paginaActual = totalPaginas;
        }
        renderizarTabla();
        actualizarPaginacion();
    } catch (error) {
        console.error(error);
        Swal.fire("Error", error.message || "Ocurrió un error al consultar la bitácora.", "error");
        mostrarEstado("No se pudo obtener la información. Intenta nuevamente.", "danger");
    }
}

function renderizarTabla() {
    const tablaWrapper = document.getElementById("tablaBitacoraWrapper");
    const estado = document.getElementById("estadoBitacora");
    const tbody = document.getElementById("tablaBitacora");

    if (!registrosBitacora.length) {
        mostrarEstado("No se encontraron registros para los filtros seleccionados.", "warning");
        return;
    }

    estado.textContent = "";
    estado.className = "estado-bitacora d-none";
    tablaWrapper.classList.remove("d-none");

    const inicio = (paginaActual - 1) * registrosPorPagina;
    const registrosPagina = registrosBitacora.slice(inicio, inicio + registrosPorPagina);

    tbody.innerHTML = registrosPagina
        .map((registro, index) => {
            const campos = registro.camposModulo || {};
            const fecha = new Date(registro.creacion || Date.now()).toLocaleString("es-MX");

            return `
                <tr>
                    <td>${registro.num ?? "-"}</td>
                    <td><span class="badge bg-info text-dark badge-accion">${registro.accion}</span></td>
                    <td>${registro.usuario || "-"}</td>
                    <td>${fecha}</td>
                    <td>${campos.pedidoID || "-"}</td>
                    <td>${campos.clienteID || "-"}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary btn-detalle-bitacora" data-index="${inicio + index}">
                            Ver detalle
                        </button>
                    </td>
                </tr>
            `;
        })
        .join("");
}

function mostrarDetalleRegistro(index) {
    const registro = registrosBitacora[index];
    if (!registro) return;

    const campos = registro.camposModulo || {};
    const fecha = new Date(registro.creacion || Date.now()).toLocaleString("es-MX");

    document.getElementById("detalleAccion").textContent = registro.accion || "-";
    document.getElementById("detalleUsuario").textContent = registro.usuario || "-";
    document.getElementById("detalleFecha").textContent = fecha;
    document.getElementById("detallePedido").textContent = campos.pedidoID || "-";
    document.getElementById("detalleCliente").textContent = campos.clienteID || "-";

    renderizarProductos(campos.productos || [], campos.cambiosProductos || {});
    renderizarCambios(campos.cambiosProductos || {});

    modalDetalle.show();
}

function renderizarProductos(productos, cambios) {
    const tbody = document.getElementById("detalleProductos");
    if (!Array.isArray(productos) || productos.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted">Sin información de productos.</td></tr>`;
        return;
    }

    const indiceCambios = construirIndiceCambios(cambios);

    tbody.innerHTML = productos
        .map((producto) => {
            const clave = producto.producto || producto.CVE_ART || "";
            const infoCambio = indiceCambios[clave] || {};
            const actual = Number(producto.cantidadActual ?? producto.cantidad ?? producto.CANT ?? 0);
            const anterior = infoCambio.tieneDato
                ? Number(infoCambio.anterior)
                : Number(producto.cantidadAnterior ?? actual);
            const ajuste = actual - anterior;
            const precio = Number(producto.precio ?? producto.PREC ?? 0);

            return `
            <tr>
                <td>${producto.producto || "-"}</td>
                <td>${producto.descripcion || "-"}</td>
                <td class="text-end">${formatearNumero(anterior)}</td>
                <td class="text-end">${formatearNumero(ajuste)}</td>
                <td class="text-end">${formatearNumero(actual)}</td>
                <td class="text-end">$${formatearNumero(precio)}</td>
            </tr>
        `;
        })
        .join("");
}

function construirIndiceCambios(cambios) {
    const indice = {};

    const agregados = Array.isArray(cambios.agregados) ? cambios.agregados : [];
    agregados.forEach((item) => {
        const clave = item.producto || "";
        indice[clave] = { anterior: 0, tieneDato: true };
    });

    const editados = Array.isArray(cambios.editados) ? cambios.editados : [];
    editados.forEach((item) => {
        const clave = item.producto || "";
        if (clave !== "") {
            indice[clave] = { anterior: Number(item.cantidadAnterior ?? 0), tieneDato: true };
        }
    });

    const eliminados = Array.isArray(cambios.eliminados) ? cambios.eliminados : [];
    eliminados.forEach((item) => {
        const clave = item.producto || "";
        if (clave !== "") {
            indice[clave] = { anterior: Number(item.cantidadAnterior ?? item.cantidad ?? 0), tieneDato: true };
        }
    });

    return indice;
}

function renderizarCambios(cambios) {
    const contenedor = document.getElementById("detalleCambios");
    if (contenedor) {
        contenedor.classList.add("d-none");
        contenedor.innerHTML = "";
    }
    return;
    const secciones = [
        { clave: "agregados", titulo: "Productos agregados", badge: "success" },
        { clave: "editados", titulo: "Productos editados", badge: "warning" },
        { clave: "eliminados", titulo: "Productos eliminados", badge: "danger" }
    ];

    const bloques = secciones
        .map((seccion) => {
            const lista = Array.isArray(cambios[seccion.clave]) ? cambios[seccion.clave] : [];
            const contenido = lista.length
                ? lista
                      .map((item) => {
                          if (seccion.clave === "editados") {
                              return `<li><strong>${item.producto}:</strong> ${formatearNumero(item.cantidadAnterior)} → ${formatearNumero(item.cantidadActual)}</li>`;
                          }
                          return `<li><strong>${item.producto}:</strong> ${formatearNumero(item.cantidad)}</li>`;
                      })
                      .join("")
                : '<li class="text-muted">Sin cambios</li>';

            return `
                <div class="col-md-4">
                    <div class="border rounded p-2 h-100">
                        <h6 class="text-${seccion.badge}">${seccion.titulo}</h6>
                        <ul class="mb-0 ps-3">
                            ${contenido}
                        </ul>
                    </div>
                </div>
            `;
        })
        .join("");

    contenedor.innerHTML = bloques;
}

function actualizarPaginacion() {
    const contenedor = document.getElementById("paginacionControles");
    const paginacion = document.getElementById("paginacionBitacora");
    if (!contenedor || !paginacion) return;

    if (!registrosBitacora.length) {
        ocultarPaginacion();
        return;
    }

    contenedor.classList.remove("d-none");
    contenedor.classList.add("active");
    const botones = [];
    const total = Math.max(1, totalPaginas);

    botones.push(`<button class="prev ${paginaActual === 1 ? "disabled" : ""}" data-nav="prev">&laquo;</button>`);

    for (let i = 1; i <= total; i++) {
        botones.push(`<button class="${i === paginaActual ? "active" : ""}" data-pag="${i}">${i}</button>`);
    }

    botones.push(
        `<button class="next ${paginaActual === total ? "disabled" : ""}" data-nav="next">&raquo;</button>`
    );

    paginacion.innerHTML = botones.join("");

    paginacion.querySelectorAll("button").forEach((btn) => {
        btn.addEventListener("click", () => {
            if (btn.classList.contains("disabled")) return;

            if (btn.dataset.nav === "prev" && paginaActual > 1) {
                paginaActual -= 1;
            } else if (btn.dataset.nav === "next" && paginaActual < total) {
                paginaActual += 1;
            } else if (btn.dataset.pag) {
                const pagina = Number(btn.dataset.pag) || 1;
                if (pagina === paginaActual) return;
                paginaActual = pagina;
            }

            renderizarTabla();
            actualizarPaginacion();
        });
    });
}

function ocultarPaginacion() {
    const contenedor = document.getElementById("paginacionControles");
    if (contenedor) {
        contenedor.classList.add("d-none");
        contenedor.classList.remove("active");
    }
}

function formatearNumero(valor) {
    const numero = Number(valor) || 0;
    return numero.toLocaleString("es-MX", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

