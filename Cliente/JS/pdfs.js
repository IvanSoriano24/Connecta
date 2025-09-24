// src="https://cdn.jsdelivr.net/npm/sweetalert2@11"

function subirImagenes(cliente, files) {
  const formData = new FormData();
  formData.append("numFuncion", 14);
  formData.append("cliente", cliente); //clave cliente

  // Añadir múltiples imágenes
  Array.from(files).forEach((file) => {
    formData.append("imagen[]", file);
  });
  fetch("../Servidor/PHP/ventas.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        Swal.fire({
          title: "Subida",
          text: "Las imagenes se subieron correctamente.",
          icon: "success",
          confirmButtonColor: "#3085d6",
          returnFocus: false,
        }).then(() => {
          actualizarCarrusel(cliente); // Recargar solo el carrusel del producto
        });
      } else {
        alert("Error al subir las imágenes: " + data.message);
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("Ocurrió un error al subir las imágenes.");
    });
}
function abrirModalPdf() {
  try {
    //alert("Hola");
   const el = document.getElementById("modalPDF");
    if (!el) throw new Error("No se encontró #modalPDF en el DOM");

    // obtiene la instancia si ya existe, o crea una nueva
    const modal = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
    modal.show();
  } catch (error) {
    console.error(error);
  }
}
function subirPDFs(selectedFiles) {
  const cliente = document.getElementById("cliente").value;
  const fd = new FormData();
  fd.append("cliente", cliente); //clave cliente
  fd.append("numFuncion", 33);

  // === OBLIGATORIO: asegurar pedidoId ===
  // usa el id real que tengas en tu página (hidden/input). Deja los 3 intentos:
  const numero =
    document.getElementById("numero")?.value ||
    document.querySelector('[name="numero"]')?.value ||
    window.numero ||
    "";

  if (!numero) {
    throw new Error(
      'Falta el numero en el front (agrega un input hidden "numero").'
    );
  }
  fd.append("numero", numero);

  // === OBLIGATORIO: asegurar PDFs (al menos 1) ===
  const files = Array.from(selectedFiles || []);
  if (!files.length) {
    // último intento: tomar del input file si existe
    const input =
      document.getElementById("inputPDFs") ||
      document.querySelector(
        'input[type="file"][name="pdfs[]"], input[type="file"][name="pdfs"]'
      );
    if (input?.files?.length) files.push(...Array.from(input.files));
  }
  if (!files.length) {
    throw new Error("No hay PDFs seleccionados.");
  }

  // OJO: el nombre de campo debe ser 'pdfs[]' (PHP lo recibe como $_FILES['pdfs'])
  for (const f of files) fd.append("pdfs[]", f, f.name);

  // === fetch robusto: muestra el mensaje real del PHP ===
  return fetch("../Servidor/PHP/enviarHistorico.php", {
    method: "POST",
    body: fd,
  }).then(async (r) => {
    const text = await r.text();
    let data = null;
    try {
      data = text ? JSON.parse(text) : null;
    } catch {}
    if (!r.ok) {
      const msg =
        (data && (data.message || data.error || data.msg)) ||
        `HTTP ${r.status} ${r.statusText}`;
      throw new Error(
        msg + (text && !data ? ` · body: ${text.slice(0, 200)}` : "")
      );
    }
    if (data && (data.success === true || data.ok === true)) return data;
    throw new Error(
      (data && (data.message || data.error || data.msg)) ||
        "Respuesta JSON inesperada del servidor"
    );
  });
}

// =====================
// Módulo PDFs del modal
// =====================
(() => {
  // Estado interno
  let selectedPDFs = [];
  const maxFiles = 4;
  const maxSizeMB = 5;

  // Referencias a elementos (se resuelven en init)
  let pdfFilesInput,
    btnAgregarPDF,
    contadorPDFs,
    guardarPDFsBtn,
    enviarInput,
    selectedFilesContainer,
    selectedFilesList,
    modalEl;

  function init() {
    // Resolver elementos por ID (deben existir en el DOM)
    pdfFilesInput = document.getElementById("pdfFiles");
    btnAgregarPDF = document.getElementById("btnAgregarPDF");
    contadorPDFs = document.getElementById("contadorPDFs");
    guardarPDFsBtn = document.getElementById("guardarPDFs");
    enviarInput = document.getElementById("enviar");
    selectedFilesContainer = document.getElementById("selectedFilesContainer");
    selectedFilesList = document.getElementById("selectedFilesList");
    modalEl = document.getElementById("modalPDF");

    // === Configura tus selectores (ajusta IDs si usas otros) ===
    const $btnGuardar =
      document.getElementById("btnGuardarPDFs") ||
      document.getElementById("guardarPDFsBtn");
    const $inputPDFs =
      document.getElementById("inputPDFs") ||
      document.querySelector(
        'input[type="file"][name="pdfs[]"], input[type="file"][name="pdfs"]'
      );
    const $formPDFs = document.getElementById("formPDFs") || null;
    const $progressWrap = document.getElementById("pdfProgress") || null;
    const $numero =
      document.getElementById("numero") ||
      document.querySelector('[name="numero"]');

    // Si falta algo crítico, no inicializamos (evita errores en páginas sin el modal)
    if (
      !pdfFilesInput ||
      !btnAgregarPDF ||
      !contadorPDFs ||
      !guardarPDFsBtn ||
      !enviarInput ||
      !selectedFilesContainer ||
      !selectedFilesList ||
      !modalEl
    ) {
      // console.warn('[MDPDFs] Elementos del modal incompletos; init omitido.');
      return;
    }

    // Listeners (evitamos duplicarlos si ya existen)
    detachAll(); // seguridad para no duplicar si vuelves a llamar init()

    btnAgregarPDF.addEventListener("click", onClickAgregarPDF);
    pdfFilesInput.addEventListener("change", onChangeFiles);
    guardarPDFsBtn.addEventListener("click", onClickGuardar);

    // Mantener selección al cerrar: no limpiamos en hidden.bs.modal (tú lo pediste)
    modalEl.addEventListener("hidden.bs.modal", () => {
      // Si quisieras limpiar al cerrar, llama resetAll();
    });

    // Estado inicial
    selectedFilesContainer.style.display = "none";
    updateCounter();
  }

  // Remueve listeners si existían (para evitar duplicados)
  function detachAll() {
    if (document.activeElement === btnAgregarPDF) btnAgregarPDF.blur();
    if (btnAgregarPDF) btnAgregarPDF.replaceWith(btnAgregarPDF.cloneNode(true));

    if (document.activeElement === pdfFilesInput) pdfFilesInput.blur();
    if (pdfFilesInput) pdfFilesInput.replaceWith(pdfFilesInput.cloneNode(true));

    if (document.activeElement === guardarPDFsBtn) guardarPDFsBtn.blur();
    if (guardarPDFsBtn)
      guardarPDFsBtn.replaceWith(guardarPDFsBtn.cloneNode(true));

    // Re-resolver referencias tras clonar
    btnAgregarPDF = document.getElementById("btnAgregarPDF");
    pdfFilesInput = document.getElementById("pdfFiles");
    guardarPDFsBtn = document.getElementById("guardarPDFs");
  }

  function onClickAgregarPDF() {
    if (!pdfFilesInput) return;
    pdfFilesInput.click();
  }

  function onChangeFiles(e) {
    const files = e.target.files || [];
    for (let i = 0; i < files.length; i++) {
      const file = files[i];

      if (selectedPDFs.length >= maxFiles) {
        alert(`Solo puedes subir un máximo de ${maxFiles} archivos.`);
        break;
      }
      if (file.type !== "application/pdf") {
        alert("Solo se permiten archivos PDF.");
        continue;
      }
      if (file.size > maxSizeMB * 1024 * 1024) {
        alert(
          `El archivo ${file.name} excede el tamaño máximo de ${maxSizeMB}MB.`
        );
        continue;
      }
      if (
        selectedPDFs.some((f) => f.name === file.name && f.size === file.size)
      ) {
        alert(`El archivo ${file.name} ya fue seleccionado.`);
        continue;
      }

      selectedPDFs.push(file);
      createFileCard(file);
    }

    updateCounter();
    selectedFilesContainer.style.display =
      selectedPDFs.length > 0 ? "block" : "none";

    // Permitir seleccionar otra vez los mismos archivos
    pdfFilesInput.value = "";
  }

  function createFileCard(file) {
    const fileCard = document.createElement("div");
    fileCard.className = "pdf-file-card";
    fileCard.dataset.filename = file.name;

    fileCard.innerHTML = `
      <div class="pdf-file-info">
        <i class="bi bi-file-earmark-pdf pdf-file-icon"></i>
        <div class="pdf-file-details">
          <span class="pdf-file-title">${truncateFileName(file.name, 20)}</span>
          <span class="pdf-file-size">${(file.size / 1024 / 1024).toFixed(
            2
          )} MB</span>
        </div>
      </div>
      <div class="pdf-file-remove" data-filename="${file.name}">
        <i class="bi bi-x"></i>
      </div>
    `;

    selectedFilesList.appendChild(fileCard);

    // Eliminar
    const removeBtn = fileCard.querySelector(".pdf-file-remove");
    removeBtn.addEventListener("click", function () {
      const filename = this.dataset.filename;
      removePDF(filename);
    });
  }

  function truncateFileName(name, maxLength) {
    if (name.length <= maxLength) return name;
    const ext = name.split(".").pop();
    const base = name.substring(0, name.length - ext.length - 1);
    return base.substring(0, maxLength - 3) + "..." + ext;
  }

  function removePDF(filename) {
    selectedPDFs = selectedPDFs.filter((file) => file.name !== filename);

    const cardToRemove = selectedFilesList.querySelector(
      `.pdf-file-card[data-filename="${CSS.escape(filename)}"]`
    );
    if (cardToRemove) selectedFilesList.removeChild(cardToRemove);

    if (selectedPDFs.length === 0)
      selectedFilesContainer.style.display = "none";

    updateCounter();
  }

  function updateCounter() {
    const count = selectedPDFs.length;
    if (contadorPDFs) contadorPDFs.textContent = `${count}/${maxFiles}`;
    if (guardarPDFsBtn) guardarPDFsBtn.disabled = count === 0;
  }

  function onClickGuardar() {
    if (selectedPDFs.length === 0) {
      Swal.fire({
        icon: "info",
        title: "Sin archivos",
        text: "Selecciona al menos un PDF para continuar.",
        confirmButtonText: "Entendido",
        returnFocus: false,
      });
      return;
    }

    if (guardarPDFsBtn) guardarPDFsBtn.disabled = true;

    Swal.fire({
      icon: "success",
      title: "PDFs listos",
      text: 'Se guardarán junto con el pedido al presionar el botón verde "Guardar".',
      confirmButtonText: "Aceptar",
      returnFocus: false,
    }).then(() => {
      if (modalEl && window.bootstrap && bootstrap.Modal) {
        const instance = bootstrap.Modal.getOrCreateInstance(modalEl);
        instance.hide(); // ← SOLO cerrar
      }
      if (guardarPDFsBtn) guardarPDFsBtn.disabled = false;
    });
  }

  /*(function () {
    const $btnGuardar = document.getElementById("btnGuardar");
    const $formPedido =
      document.getElementById("formPedido") || document.querySelector("form"); // usa tu id real
    const $metaPDFInput = document.getElementById("ordenCompraMeta");

    // Utiliza la función global subirPDFs(...) ya definida en pdfs.js
    async function subirSeleccionSiAplica() {
      const files =
        window.MDPDFs && window.MDPDFs.getSelected
          ? window.MDPDFs.getSelected()
          : [];
      if (!files.length) return null; // no hay PDFs que subir

      // Subimos aquí (NO en el modal)
      const resp = await subirPDFs(files); // ← usa tu enviarHistorico.php por dentro
      return resp || null;
    }

    $btnGuardar?.addEventListener("click", async function (e) {
      // Si el botón manda submit de un form, evita enviarlo hasta subir PDFs
      if ($formPedido) e.preventDefault();

      try {
        Swal.fire({
          title: "Guardando…",
          allowOutsideClick: false,
          didOpen: Swal.showLoading,
          returnFocus: false,
        });

        // 1) Sube PDFs seleccionados (si hay)
        const resultadoPDFs = await subirSeleccionSiAplica();

        // 2) Coloca metadatos en el hidden (ajusta a lo que te devuelva tu PHP)
        if ($metaPDFInput) {
          $metaPDFInput.value = JSON.stringify(resultadoPDFs || {});
        }

        // 3) Envía el formulario normalmente (tu backend ya guarda en Firestore)
        if ($formPedido) $formPedido.submit();

        // 4) Limpia selección temporal
        if (window.MDPDFs && window.MDPDFs.reset) window.MDPDFs.reset();
      } catch (err) {
        Swal.close();
        Swal.fire({
          icon: "error",
          title: "Error al guardar",
          text: (err && err.message) || String(err),
          returnFocus: false,
        });
      }
    });
  })();*/

  // Si algún día quieres limpiar todo manualmente
  function resetAll() {
    selectedPDFs = [];
    selectedFilesList.innerHTML = "";
    selectedFilesContainer.style.display = "none";
    updateCounter();
    if (pdfFilesInput) pdfFilesInput.value = "";
    const enviar = document.getElementById("enviar");
    if (enviar) enviar.value = ""; // ✅ asegura que quede vacío
  }

  // Inicializa automáticamente cuando el DOM esté listo
  document.addEventListener("DOMContentLoaded", init);
  window.addEventListener("beforeunload", resetAll);

  // Exponer una pequeña API por si insertas el HTML del modal dinámicamente
  window.MDPDFs = {
    init,
    getSelected: () => selectedPDFs.slice(),
    reset: resetAll,
  };
})();
