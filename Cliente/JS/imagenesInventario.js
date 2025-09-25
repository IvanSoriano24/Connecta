// src="https://cdn.jsdelivr.net/npm/sweetalert2@11"
function abrirModalPdf() {
  try {
    //alert("Hola");
    const el = document.getElementById("modalPDF");
    if (!el) throw new Error("No se encontró #modalPDF en el DOM");
    //const linea = $(this).data("linea") || $("#lineaSelect").val();
    /*const $card = $(this).closest('.article-card');
    //console.log($card);
    const linea = $(this).data("linea") || $("#lineaSelect").val();
    const codigo = $(this).data('articulo');
    //const codigo = "AA-1625";
    console.log(linea);
    console.log(codigo);
    // 1) Fija el destino (esto limpia si cambió)
    window.MDPDFs?.setTarget?.({
      tipo: "producto",
      linea,
      cve_art: String(code),
    });*/
    // obtiene la instancia si ya existe, o crea una nueva
    const modal = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
    modal.show();
  } catch (error) {
    console.error(error);
  }
}
function subirImagenes(cliente, files) {
  const formData = new FormData();
  formData.append("numFuncion", 14);
  formData.append("cliente", cliente); //clave cliente

  // Añadir múltiples imágenes
  Array.from(files).forEach((file) => {
    formData.append("imagen[]", file);
  });
  fetch("../Servidor/PHP/../.php", {
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
function subirPDFsLineas(selectedFiles, meta = {}) {
  const fd = new FormData();
  fd.append('numFuncion', 33);

  const noInventario =
    document.getElementById('noInventario')?.value ||
    document.querySelector('[name="noInventario"]')?.value ||
    window.noInventario || '';

  if (!noInventario) throw new Error('Falta el noInventario en el front.');
  fd.append('noInventario', noInventario);

  // meta -> separa por línea/artículo en el servidor
  if (meta.tipo)   fd.append('tipo', String(meta.tipo));           // 'producto' | 'linea'
  if (meta.linea)  fd.append('linea', String(meta.linea));
  if (meta.cve_art)fd.append('cve_art', String(meta.cve_art));

  const files = Array.from(selectedFiles || []);
  if (!files.length) throw new Error('No hay archivos seleccionados.');
  for (const f of files) fd.append('pdfs[]', f, f.name);

  return fetch('enviarHistorico.php', { method: 'POST', body: fd })
    .then(async (r) => {
      const text = await r.text();
      let data = null; try { data = text ? JSON.parse(text) : null; } catch {}
      if (!r.ok) throw new Error((data?.message || data?.error || data?.msg) || `HTTP ${r.status} ${r.statusText}`);
      if (data?.success === true || data?.ok === true) return data;
      throw new Error(data?.message || data?.error || data?.msg || 'Respuesta JSON inesperada');
    });
}
// =====================
// Módulo PDFs del modal
// =====================
(() => {
  // Estado interno
  let selectedPDFs = [];
  let currentTarget = null;
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
  function setTarget(meta) {
    // meta: { tipo: 'linea'|'producto', linea?: '001', cve_art?: 'AA-1625' }
    const key = JSON.stringify({
      tipo: meta?.tipo || "linea",
      linea: meta?.linea || null,
      cve_art: meta?.cve_art || null,
    });

    if (currentTarget !== key) {
      // Si cambiaste de producto/línea, limpia selección previa
      resetAll();
      currentTarget = key;
    }
  }
  window.MDPDFs = {
    init,
    getSelected: () => selectedPDFs.slice(),
    reset: resetAll,
    setTarget, // ← NUEVO
  };

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
    const $noInventario =
      document.getElementById("noInventario") ||
      document.querySelector('[name="noInventario"]');

    // Si falta algo crítico, no inicializamos (evita errores en páginas sin el modal)
    if (
      !pdfFilesInput ||
      !btnAgregarPDF ||
      !contadorPDFs ||
      !guardarPDFsBtn ||
      !selectedFilesContainer ||
      !selectedFilesList ||
      !modalEl
    ) {
      // console.warn('[MDPDFs] Elementos del modal incompletos; init omitido.');
      return;
    }

    // Listeners (evitamos duplicarlos si ya existen)
    //detachAll(); // seguridad para no duplicar si vuelves a llamar init()

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
      if ( selectedPDFs.some((f) => f.name === file.name && f.size === file.size) ) {
        alert(`El archivo ${file.name} ya fue seleccionado.`);
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
    const isPDF = file.type === "application/pdf" || /\.pdf$/i.test(file.name);
    const card = document.createElement("div");
    card.className = "pdf-file-card";
    card.dataset.filename = file.name;

    let preview = "";
    if (!isPDF && file.type.startsWith("image/")) {
      const url = URL.createObjectURL(file);
      preview = `<img src="${url}" alt="${file.name}" style="width:60px;height:60px;object-fit:cover;border-radius:6px;">`;
    } else {
      preview = `<i class="bi bi-file-earmark-pdf pdf-file-icon"></i>`;
    }

    card.innerHTML = `
        <div class="pdf-file-info" style="display:flex;gap:12px;align-items:center;">
        ${preview}
        <div class="pdf-file-details">
            <span class="pdf-file-title">${truncateFileName(
              file.name,
              28
            )}</span>
            <span class="pdf-file-size">${(file.size / 1024 / 1024).toFixed(
              2
            )} MB</span>
        </div>
        </div>
        <button type="button" class="pdf-file-remove btn btn-link p-0" data-filename="${
          file.name
        }" aria-label="Quitar">
        <i class="bi bi-x"></i>
        </button>
    `;

    selectedFilesList.appendChild(card);

    // Quitar archivo
    card
      .querySelector(".pdf-file-remove")
      .addEventListener("click", function () {
        removePDF(this.dataset.filename);
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

  (function () {
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
  })();

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
    setTarget, // ← NUEVO
  };
  /*window.MDPDFs = {
    init,
    getSelected: () => selectedPDFs.slice(),
    reset: resetAll,
  };*/
})();
