const accionesPorModulo = {
    PEDIDOS: [
        { value: "CREACION", label: "Creación" },
        { value: "EDICION", label: "Edición" }
    ],
    CLIENTES: [],
    FACTURAS: []
};

let registrosBitacora = [];
let modalDetalle;

document.addEventListener("DOMContentLoaded", () => {
    const moduloFiltro = document.getElementById("moduloFiltro");
    const accionFiltro = document.getElementById("accionFiltro");
    modalDetalle = new bootstrap.Modal(document.getElementById("modalDetalleBitacora"));

    moduloFiltro.addEventListener("change", () => {
        actualizarAcciones(moduloFiltro.value);
        limpiarResultados();
    });

    accionFiltro.addEventListener("change", () => {
        const modulo = moduloFiltro.value;
        const accion = accionFiltro.value;
        if (modulo && accion) {
            cargarBitacora(modulo, accion);
        } else {
            limpiarResultados();
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
}

async function cargarBitacora(modulo, accion) {
    mostrarEstado("Consultando registros, por favor espera...", "primary");

    try {
        const response = await fetch("../Servidor/PHP/bitacoraConsulta.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ modulo, accion })
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || "No se pudo obtener la bitácora.");
        }

        registrosBitacora = data.data || [];
        renderizarTabla();
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

    tbody.innerHTML = registrosBitacora
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
                        <button class="btn btn-sm btn-outline-primary btn-detalle-bitacora" data-index="${index}">
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

    renderizarProductos(campos.productos || []);
    renderizarCambios(campos.cambiosProductos || {});

    modalDetalle.show();
}

function renderizarProductos(productos) {
    const tbody = document.getElementById("detalleProductos");
    if (!Array.isArray(productos) || productos.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted">Sin información de productos.</td></tr>`;
        return;
    }

    tbody.innerHTML = productos
        .map((producto) => `
            <tr>
                <td>${producto.producto || "-"}</td>
                <td>${producto.descripcion || "-"}</td>
                <td class="text-end">${formatearNumero(producto.cantidad)}</td>
                <td class="text-end">$${formatearNumero(producto.precio)}</td>
            </tr>
        `)
        .join("");
}

function renderizarCambios(cambios) {
    const contenedor = document.getElementById("detalleCambios");
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

function formatearNumero(valor) {
    const numero = Number(valor) || 0;
    return numero.toLocaleString("es-MX", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

