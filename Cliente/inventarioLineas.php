<?php
session_start();
if (isset($_SESSION['usuario'])) {
    if ($_SESSION['usuario']['tipoUsuario'] == 'CLIENTE') {
        header('Location:Menu.php');
        exit();
    }
    $nombreUsuario = $_SESSION['usuario']["nombre"];
    $nombreApellido = "{$_SESSION['usuario']['nombre']} {$_SESSION['usuario']['apellido']}";
    $tipoUsuario = $_SESSION['usuario']["tipoUsuario"];
    $correo = $_SESSION['usuario']["correo"];
    if ($_SESSION['usuario']['tipoUsuario'] == 'ADMISTRADOR') {
        header('Location:Dashboard.php');
        exit();
    }

    $mostrarModal = isset($_SESSION['empresa']) ? false : true;

    //$empresa = $_SESSION['empresa']['razonSocial'];
    if (isset($_SESSION['empresa'])) {
        $empresa = $_SESSION['empresa']['razonSocial'];
        $idEmpresa = $_SESSION['empresa']['id'];
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
    }
    $csrf_token  = $_SESSION['csrf_token'];
} else {
    header('Location:../index.php');
}
/*
session_unset();
session_destroy(); */
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootsstrap  -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>

    <script src="JS/sideBar.js"></script>
    <!-- My CSS -->
    <link rel="stylesheet" href="CSS/style.css">

    <link rel="stylesheet" href="CSS/selec.css">
    <link rel="stylesheet" href="CSS/subirInventario.css">

    <title>MDConnecta</title>
    <link rel="icon" href="SRC/logoMDConecta.png" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* Encabezado */
        .inv-lines .inv-header h1 {
            font-weight: 700;
            font-size: 1.5rem;
        }

        .inv-lines .inv-header .dot {
            width: .45rem;
            height: .45rem;
            border-radius: 50%;
            background: #9ca3af;
            display: inline-block;
            margin-inline: .25rem;
        }

        .inv-lines .inv-header .status {
            color: #6b7280;
        }

        .inv-lines .inv-header .sep {
            color: #9ca3af;
            margin-inline: .5rem;
        }

        .inv-lines .inv-header .current {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }

        .inv-lines .inv-header .current:hover {
            text-decoration: underline;
        }

        /* Caja de filtros */
        .inv-lines .inv-toolbar {
            background: #f8f9fb;
            border-radius: 12px;
            padding: 14px 16px;
            border: 1px solid #e5e7eb;
        }

        .inv-num {
            text-align: center;
            font-weight: 700;
            letter-spacing: .12em;
            color: #c1121f;
        }

        /* Tarjetas de artículos */
        .inv-articles {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .article-card {
            border: 1px solid #dbe3f8;
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
        }

        .article-head {
            display: grid;
            grid-template-columns: 110px 1fr 70px 170px;
            gap: 12px;
            align-items: center;
            padding: 10px 14px;
            border-bottom: 2px solid #9bb5f3;
        }

        .article-head .code {
            font-weight: 600;
            color: #4b5563;
        }

        .article-head .name {
            color: #2563eb;
            text-decoration: none;
        }

        .article-head .name:hover {
            text-decoration: underline;
        }

        .article-head .unit {
            text-align: center;
            color: #6b7280;
        }

        .article-head .total {
            text-align: right;
            color: #6b7280;
        }

        .bg-primary-subtle {
            background: #e8f0ff !important;
        }

        /* Filas de lotes */
        .rows {
            padding: 4px 8px 10px;
        }

        .row-line {
            display: grid;
            grid-template-columns: 110px 1fr 170px 120px;
            gap: 12px;
            align-items: center;
            border-bottom: 1px solid #e5e7eb;
            padding: 10px 8px;
        }

        .row-line:last-of-type {
            border-bottom: none;
        }

        .row-line .lote {
            color: #6b7280;
        }

        .row-line .pres {
            color: #4b5563;
        }

        .qty-label {
            color: #6b7280;
            text-align: right;
        }

        .row-add {
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin: 8px;
            padding: 8px;
            display: flex;
            justify-content: flex-start;
        }

        .row-add .btn {
            width: 38px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            font-size: 20px;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .article-head {
                grid-template-columns: 90px 1fr 60px 140px;
            }

            .row-line {
                grid-template-columns: 90px 1fr 160px 110px;
            }
        }

        @media (max-width: 640px) {
            .article-head {
                grid-template-columns: 70px 1fr 50px 120px;
            }

            .row-line {
                grid-template-columns: 70px 1fr;
            }

            .qty-label {
                text-align: left;
            }

            .row-line .qty-label,
            .row-line .form-control {
                grid-column: 1 / -1;
            }
        }
    </style>
    <style>
        /* ===== Variante espaciosa para Inventario por Líneas ===== */

        /* Tarjeta */
        .article-card.spacious {
            border: 1px solid #dbe3f8;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 10px 24px rgba(17, 24, 39, .04);
        }

        /* Encabezado de la tarjeta */
        .article-head--spaced {
            padding: 18px 20px;
            gap: 18px;
            border-bottom: 2px solid #9bb5f3;
            display: grid;
            grid-template-columns: 140px 1fr 160px 200px;
            /* más ancho */
            align-items: center;
        }

        .article-head--spaced .code {
            font-weight: 700;
            color: #374151;
            letter-spacing: .3px;
        }

        .article-head--spaced .name {
            color: #2563eb;
            font-weight: 600;
            text-decoration: none;
        }

        .article-head--spaced .name:hover {
            text-decoration: underline;
        }

        .article-head--spaced .exist,
        .article-head--spaced .total {
            color: #6b7280;
        }

        /* Cuerpo (filas) */
        .rows--spaced {
            padding: 14px 14px 18px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            /* separación vertical entre filas */
        }

        /* Una fila */
        .row-line--spaced {
            background: #fafbff;
            border: 1px solid #e6ebff;
            border-radius: 12px;
            padding: 16px 14px;
            /* más alto */
            display: grid;
            grid-template-columns: 130px 1fr 1fr;
            /* lote + 2 campos */
            gap: 18px;
            /* separación entre columnas */
            align-items: center;
        }

        /* Etiquetas tipo “label + input” alineadas */
        .row-line--spaced .field {
            display: grid;
            grid-template-columns: 160px 1fr;
            /* ancho de etiqueta fijo + input fluido */
            align-items: center;
            gap: 12px;
            margin: 0;
            /* reset */
        }

        .row-line--spaced .field>span {
            color: #4b5563;
            font-weight: 500;
        }

        .qty-input {
            height: 44px;
            /* más alto */
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid #d1d5db;
        }

        .qty-input:focus {
            outline: none;
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(96, 165, 250, .25);
        }

        /* Área para agregar filas */
        .row-add--spaced {
            margin-top: 4px;
            background: #f3f4f6;
            border: 1px dashed #d1d5db;
            border-radius: 12px;
            padding: 12px;
        }

        .row-add--spaced .btn {
            width: 44px;
            height: 40px;
            font-size: 22px;
        }

        /* Separación entre tarjetas */
        .inv-articles .article-card.spacious {
            margin-top: 10px;
        }

        .inv-articles .article-card.spacious+.article-card.spacious {
            margin-top: 16px;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .article-head--spaced {
                grid-template-columns: 120px 1fr 140px 160px;
            }
        }

        @media (max-width: 720px) {
            .article-head--spaced {
                grid-template-columns: 100px 1fr;
            }

            .article-head--spaced .exist,
            .article-head--spaced .total {
                justify-self: start;
            }

            .row-line--spaced {
                grid-template-columns: 1fr;
                /* columna única en móvil */
            }

            .row-line--spaced .field {
                grid-template-columns: 1fr;
                /* etiqueta arriba, input abajo */
            }
        }

        #resumenContenido {
            max-height: 400px;
            /* ajusta altura del área de scroll */
            overflow-y: auto;
        }

        #resumenContenido thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: #f8f9fa;
        }

        #resumenContenido tfoot td {
            position: sticky;
            bottom: 0;
            z-index: 1;
        }

        .table-subtotal {
            background-color: #ffeeba !important;
            /* amarillo suave */
            font-weight: 600;
            --bs-table-bg: #d5d5d5;
            --bs-table-border-color: #d5d5d5;
            border-color: var(--bs-table-border-color);
        }

        .btn-primaryNormal {
            --bs-btn-color: #fff;
            --bs-btn-bg: #0d6efd;
            --bs-btn-border-color: #0d6efd;
            --bs-btn-hover-color: #fff;
            --bs-btn-hover-bg: #0b5ed7;
            --bs-btn-hover-border-color: #0a58ca;
            --bs-btn-focus-shadow-rgb: 49, 132, 253;
            --bs-btn-active-color: #fff;
            --bs-btn-active-bg: #0a58ca;
            --bs-btn-active-border-color: #0a53be;
            --bs-btn-active-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125);
            --bs-btn-disabled-color: #fff;
            --bs-btn-disabled-bg: #0d6efd;
            --bs-btn-disabled-border-color: #0d6efd;
        }
    </style>
</head>

<body>
    <div class="hero_area">
        <!-- SIDEBAR -->
        <?php include 'sidebar.php'; ?>
        <!-- CONTENT -->
        <section id="content">
            <!-- NAVBAR -->
            <?php include 'navbar.php'; ?>
            <!-- MAIN -->
            <main class="my-4 hero_area">
                <input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo $csrf_token; ?>">

                <div class="inv-lines container-xxl px-3 px-md-4">
                    <!-- Encabezado -->
                    <div class="inv-header d-flex align-items-center justify-content-between">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <h1 class="m-0">Inventario Físico</h1>
                            <span class="dot"></span>
                            <span class="status">Iniciado</span>
                            <span class="sep">|</span>
                            <a href="inventarioLineas.php" class="current">Por líneas</a>
                            <span><?= $_SESSION['usuario']['idReal']; ?></span>
                        </div>

                        <div class="d-flex gap-2">
                            <a href="inventarioFisico.php" class="btn btn-light">Atrás</a>
                            <button type="button" class="btn btn-primary" id="btnNext">Siguiente</button>
                        </div>
                    </div>

                    <!-- Filtros superiores -->
                    <div class="inv-toolbar mt-3">
                        <div class="row g-3 align-items-end">

                            <div class="col-12 col-sm-6 col-md-3">
                                <label for="lineaSelect" class="form-label m-0">Línea:</label>
                                <select name="lineaSelect" id="lineaSelect" class="form-select">
                                    <option value="" disabled selected>Seleccione una línea</option>
                                </select>
                            </div>

                            <div class="col-6 col-md-3 col-lg-2">
                                <label for="noInventario" class="form-label m-0">No. Inventario:</label>
                                <input type="text" id="noInventario" class="form-control inv-num" disabled>
                            </div>

                            <div class="col-6 col-md-3 col-lg-2">
                                <label for="fechaInicio" class="form-label m-0">Fecha inicio:</label>
                                <input type="date" id="fechaInicio" class="form-control" disabled>
                            </div>

                            <div class="col-6 col-md-3 col-lg-2" id="fechaFinContainer">
                                <label for="fechaFin" class="form-label m-0">Fecha fin:</label>
                                <input type="date" id="fechaFin" class="form-control" disabled>
                            </div>

                            <div class="col-6 col-md-3 col-lg-2">
                                <label class="form-label m-0">Conteo:</label>
                                <input id="conteoInput" class="form-control" disabled>
                            </div>

                            <div class="col-6 col-md-3 col-lg-2">
                                <label class="form-label m-0">Sub-Conteo:</label>
                                <input id="subconteoInput" class="form-control" disabled>
                            </div>

                            <div class="col-12 col-md-3 mt-2">
                                <span class="text-muted">Autoguardado desactivado</span>
                            </div>
                        </div>
                    </div>


                    <!-- Título de listado -->
                    <h5 class="text-center fw-semibold mt-4">Artículos:</h5>

                    <!-- CONTENEDOR DE ARTÍCULOS -->
                    <div id="articulos" class="inv-articles mt-2">
                        <!-- === /Ejemplo === -->
                    </div>
                </div>
            </main>

            <!-- MAIN -->
        </section>
        <!-- CONTENT -->
    </div>

    <div class="modal fade" id="resumenInventario" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Resumen del Inventario Físico</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <h5 id="lineaSeleccionada" class="mb-3"></h5>
                    <div id="resumenContenido"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-dark" id="generararPDF">Generar PDF</button>
                    <button type="button" class="btn btn-success" id="exportarExcel">Exportar Excel</button>
                    <button type="button" class="btn btn-danger" id="finalizarInventarioLinea">Guardar Línea</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal para subir PDFs -->
    <div id="modalPDF" class="modal fade" tabindex="-1" aria-hidden="true"> <!-- data-bs-backdrop="false" -->
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content pdf-modal-content">
                <div class="modal-header pdf-modal-header-style">
                    <h5 class="modal-title">Subir Archivos PDF</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formPDF" enctype="multipart/form-data">
                        <!-- 🔹 NUEVO: el front (subirPDFs) leerá este valor -->
                        <input type="hidden" id="pedidoId" name="pedidoId"
                            value="<?= htmlspecialchars($pedidoId ?? ($_GET['pedidoId'] ?? '')) ?>">

                        <div class="pdf-mb-3">
                            <label class="form-label">Selecciona de 1 a 4 archivos PDF (Máximo 5MB cada uno)</label>
                            <div class="pdf-file-input-container">
                                <!-- Botón accesible que abre el input real -->
                                <label for="pdfFiles" class="btn pdf-btn-primary pdf-custom-file-btn" id="btnAgregarPDF">
                                    <i class="bi bi-plus-circle"></i> Agregar archivo(s)
                                </label>

                                <!-- Input REAL (oculto de forma accesible; no uses d-none) -->
                                <input type="file" id="pdfFiles" name="pdfs[]" class="visually-hidden"
                                    accept="image/*,.pdf,application/pdf" multiple>
                            </div>

                            <div class="pdf-instruction-text">
                                Puedes seleccionar múltiples archivos manteniendo presionada la tecla Ctrl
                            </div>
                        </div>
                        <!-- Sección de archivos seleccionados -->
                        <div class="pdf-selected-files" id="selectedFilesContainer">
                            <div class="pdf-header-info">
                                <h6>Archivos seleccionados</h6>
                                <span class="pdf-file-status" id="contadorPDFs">0/4</span>
                            </div>
                            <div id="selectedFilesList"><!-- Los archivos seleccionados aparecerán aquí --></div>
                        </div>
                        <!-- Previews (oculta) -->
                        <div id="pdfPreviews" class="pdf-mt-3 d-none">
                            <div class="pdf-empty-state" id="emptyState">
                                <i class="bi bi-file-earmark-pdf"></i>
                                <p>No hay archivos seleccionados</p>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <!-- 🔹 Respaldo opcional del pedidoId en el botón -->
                    <button type="button" class="btn pdf-btn-primary" id="guardarPDFs"
                        data-pedido-id="<?= htmlspecialchars($pedidoId ?? ($_GET['pedidoId'] ?? '')) ?>"
                        disabled>Guardar PDFs</button>
                </div>
            </div>
        </div>
    </div>
    <img id="logoInventario" src="SRC/imagen-small.png" style="display:none;">

    <!-- CONTENT -->

    <!-- JS Para la confirmacion empresa -->
    <script src="JS/imagenesInventario.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/menu.js"></script>
    <script src="JS/app.js"></script>
    <script src="JS/script.js"></script>
    <script src="JS/inventarioLineas.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        const nombreUsuario = "<?php echo $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido']; ?>";
        const usuarioId = "<?php echo $_SESSION['usuario']['id']; ?>";
    </script>
    <script>
        (function() {
            const $linea = $('#lineaSelect');
            const $articulos = $('#articulos');
            const $mensaje = $('#msgArticulos');
            const $btnNext = $('#btnNext');
            const csrfToken = $('#csrf_token').val();

            resetUI();

            $linea.on('change', function() {
                const linea = $(this).val();
                if (!linea) {
                    resetUI();
                    return;
                }
                loadArticulos(linea);
                loadGuardados(linea);
            });

            function resetUI() {
                $btnNext.prop('disabled', true);
                $articulos.addClass('d-none').empty();
                $mensaje.removeClass('d-none').text('Selecciona una línea para ver sus artículos.');
            }

            function showLoading() {
                $mensaje.addClass('d-none');
                $articulos.removeClass('d-none').html(skeleton());
            }

            function showEmpty(text) {
                $btnNext.prop('disabled', true);
                $articulos.addClass('d-none').empty();
                $mensaje.removeClass('d-none').text(text || 'No hay artículos para esta línea.');
            }

            function showError(xhr) {
                console.error('Error AJAX inventario líneas:', xhr);
                showEmpty('Ocurrió un error al cargar los artículos. Intenta de nuevo.');
            }
            // Carga por línea -> numFuncion=4
            function loadArticulos(linea) {
                showLoading();
                $.ajax({
                        url: '../Servidor/PHP/inventario.php',
                        method: 'GET',
                        dataType: 'json',
                        headers: {
                            'X-CSRF-Token': csrfToken
                        },
                        data: {
                            numFuncion: '4',
                            linea: linea
                        }
                    })
                    .done(function(res) {
                        // La API puede venir como {success:true,data:[...]} o directamente [...]
                        const rows = Array.isArray(res?.data) ? res.data :
                            Array.isArray(res) ? res :
                            (res?.CVE_ART ? [res] : []);

                        if (!rows.length) {
                            showEmpty('No hay artículos para esta línea.');
                            return;
                        }

                        // 1) Normalizar: agrupar por CVE_ART -> items con lotes[]
                        const items = normalizeToArticles(rows);
                        if (!items.length) {
                            showEmpty();
                            return;
                        }

                        // 2) Render
                        renderArticulos(items);
                        $btnNext.prop('disabled', false);
                    })
                    .fail(showError);
            }
            //Mostar productos guardados de la linea
            function loadGuardados(linea) {
                const noInventario = document.getElementById("noInventario").value;
                $.ajax({
                        url: '../Servidor/PHP/inventario.php',
                        method: 'GET',
                        dataType: 'json',
                        headers: {
                            'X-CSRF-Token': csrfToken
                        },
                        data: {
                            numFuncion: '8', // ← tu endpoint que ahora regresa los guardados
                            linea: linea,
                            noInventario: noInventario
                        }
                    })
                    .done(function(res) {
                        // Estructura esperada:
                        // { success:true, linea:"002", locked:bool, productos:[{ cve_art, conteoTotal, lotes:[{corrugados,corrugadosPorCaja,lote,total}] }] }
                        if (!res || res.success !== true) return;
                        applyGuardadosToUI(res);
                    })
                    .fail(function(err) {
                        console.error('loadGuardados error:', err);
                    });
            }

            function applyGuardadosToUI(res) {
                const {
                    locked,
                    productos,
                    activa
                } = res;

                // 1) Aplicar por producto
                (productos || []).forEach(p => {
                    const code = String(p.cve_art || '');
                    if (!code) return;

                    // Busca la tarjeta del producto ya renderizada por loadArticulos()
                    const $card = $articulos.find(`.article-card[data-articulo="${cssEscape(code)}"]`);
                    if ($card.length === 0) {
                        // Si no vino en el catálogo actual, lo puedes ignorar o crear la tarjeta "huérfana"
                        // Por simplicidad, lo ignoramos aquí.
                        return;
                    }

                    // Construir filas desde lotes guardados
                    const rowsHtml = (p.lotes || []).map((r, i) => rowHtml({
                        lote: r.lote || `Lote ${i+1}`,
                        corr: Number(r.corrugados || 0),
                        cajas: Number(r.corrugadosPorCaja || 0),
                        sueltos: Number(r.sueltos || 0),
                        activa: activa
                    })).join('');

                    // Reemplazar filas (dejando el bloque de "agregar fila" al final)
                    const $rows = $card.find('.rows');
                    $rows.find('.row-line').remove(); // limpia filas previas
                    $rows.prepend(rowsHtml); // inserta guardados arriba

                    // Badge total
                    const sum = (p.lotes || []).reduce((acc, r) => acc + (Number(r.total || 0)), 0);
                    $card.find('.article-total').text(sum).removeClass('text-primary').addClass('text-success');
                    $card.attr('data-restored', '1');

                    // Pintar "guardado" en el status
                    $card.find('.save-status').text('Recuperado ✓').show();
                });

                // 2) Recalcular piezas por fila (para pintar la columna .pres)
                $articulos.find('.article-card').each(function() {
                    const $card = $(this);
                    recalcCard($card);
                });

                // 3) Si la línea está bloqueada, deshabilita inputs y botones
                if (locked === false) {
                    $articulos.find('input, button.btn-add-row, button.btn-save-article, button.eliminarLote')
                        .prop('disabled', true);
                    $('#btnCerrarLinea').prop('disabled', true);
                    // Opcional: aviso visual
                    $('#msgArticulos').removeClass('d-none').text('Línea bloqueada. Solo lectura.');
                    var tipoUsuario = '<?php echo $tipoUsuario ?>';
                    comparararConteos(tipoUsuario);
                }
            }
            // Utilidad para recalcular totales visuales en una tarjeta
            function recalcCard($card) {
                let sum = 0;
                $card.find('.row-line').each((_, el) => {
                    const $row = $(el);
                    const corr = Number($row.find('.qty-input-corrugado').val()) || 0;
                    const cajas = Number($row.find('.qty-input-cajas').val()) || 0;
                    const sueltos = Number($row.find('.qty-input-sueltos').val()) || 0;
                    const piezas = (corr * cajas) + sueltos;
                    $row.find('.pres').text(piezas); // ← PINTA la columna "piezas"
                    sum += piezas;
                });
                $card.find('.article-total').text(sum);
            }
            // Fila de lote (HTML) con valores
            function rowHtml({
                lote,
                corr,
                cajas,
                sueltos,
                activa
            }) {
                const bloqueado = !activa;
                const disabledAttr = bloqueado ? 'disabled' : '';

                return `
                    <div class="row-line">
                    <div class="lote">
                        ${lote ? escapeHtml(lote) : ''}
                    </div>

                    <label class="field label">
                        <span>Corrugado:</span>
                        <input type="number"
                            class="form-control qty-input-corrugado"
                            value="${Number(corr) || 0}"
                            min="0"
                            step="1"
                            ${disabledAttr}>
                    </label>

                    <label class="field label">
                        <span>Cajas por Corrugado:</span>
                        <input type="number"
                            class="form-control qty-input-cajas"
                            value="${Number(cajas) || 0}"
                            min="0"
                            step="1"
                            ${disabledAttr}>
                    </label>

                    <label class="field label">
                        <span>Cajas sueltas:</span>
                        <input type="number"
                            class="form-control qty-input-sueltos"
                            value="${Number(sueltos) || 0}"
                            min="0"
                            step="1"
                            ${disabledAttr}>
                    </label>
                    </div>
                `;
            }
            // Para seleccionar claves con guiones en selectores de atributo (seguro)
            function cssEscape(str) {
                return String(str).replace(/("|'|\\)/g, '\\$1');
            }
            // Agrupa filas planas en tarjetas con lotes
            // row esperado: {CVE_ART, DESCR, LIN_PROD, EXIST, ProductoLote, LOTE, CantidadLote}
            function normalizeToArticles(rows) {
                const map = {};
                rows.forEach(r => {
                    const key = r.CVE_ART || 'SIN-CODIGO';
                    if (!map[key]) {
                        map[key] = {
                            codigo: r.CVE_ART,
                            nombre: r.DESCR,
                            unidad: 'Pza.',
                            exist: Number(r.EXIST) || 0,
                            lotes: []
                        };
                    }
                    map[key].lotes.push({
                        LOTE: r.LOTE || `Lote ${map[key].lotes.length + 1}`,
                        presentacion: r.ProductoLote || '—',
                        CantidadLote: Number(r.CantidadLote) || 0
                    });
                });
                return Object.values(map);
            }
            // Pinta tarjetas
            function renderArticulos(articulos) {
                const html = articulos.map(buildArticleCard).join('');
                $articulos.removeClass('d-none').html(html);

                // Bind de eventos + totales
                $articulos.find('.article-card').each(function() {
                    const $card = $(this);

                    function recalc() {
                        let sum = 0;
                        $card.find('.row-line').each((_, el) => {
                            const $row = $(el);
                            const corr = Number($row.find('.qty-input-corrugado').val()) || 0;
                            const cajas = Number($row.find('.qty-input-cajas').val()) || 0;
                            const sueltos = Number($row.find('.qty-input-sueltos').val()) || 0;
                            const totalLote = Number($row.find('.qty-input-total').val((corr * cajas) + sueltos)) || 0;

                            const piezas = (corr * cajas) + sueltos;
                            sum += piezas;
                            //alert(sum);
                        });
                        $card.find('.article-total').text(sum);
                    }
                    // Disparar recalc al cambiar cualquiera de los dos inputs
                    $card.on('input change', '.qty-input-corrugado, .qty-input-cajas, .qty-input-sueltos', recalc);
                    $card.on('click', '.btn-add-row', function() {
                        const idx = $card.find('.row-line').length + 1;
                        const row = `
                        <div class="row-line row-line">
                            <div class="lote">
                            <input type="text" class="form-control" placeholder="Lote ${idx}">
                            </div>

                            <label class="field label">
                            <span>Corrugado:</span>
                            <input type="number" class="form-control qty-input-corrugado" value="0" min="0" step="1">
                            </label>

                            <label class="field label">
                            <span>Cajas por Corrugado:</span>
                            <input type="number" class="form-control qty-input-cajas" value="0" min="0" step="1">
                            </label>

                            <label class="field label">
                            <span>Cajas sueltas:</span>
                            <input type="number" class="form-control qty-input-sueltos" value="0" min="0" step="1">
                            </label>

                            <div class="eliminar">
                            <button type="button" class="btn btn-danger btn-sm eliminarLote" title="Eliminar lote">
                                <i class="bx bx-trash"></i>
                            </button>
                            </div>
                        </div>
                        `;
                        $card.find('.row-add').before(row);
                        recalc();
                    });
                    $card.on('click', '.eliminarLote', function() {
                        $(this).closest('.row-line').remove();
                        recalc();
                    });
                    recalc();
                });
            }
            // Delegación: click en Guardar producto
            $articulos.on('click', '.btn-save-article', async function() {
                const $btn = $(this);
                const $card = $btn.closest('.article-card');
                const csrf = $('#csrf_token').val();
                const linea = $('#lineaSelect').val();
                const noInv = $('#noInventario').val();
                const code = $card.data('articulo') || $.trim($card.find('.code').text());
                //const noEmp = (window.empresaActivaId || ($('#empresaActivaId').val())) ?? null; // ajusta si usas sesión

                // Validaciones rápidas
                if (!linea) return Swal.fire({
                    icon: 'warning',
                    title: 'Selecciona una línea'
                });
                if (!noInv) return Swal.fire({
                    icon: 'warning',
                    title: 'Falta No. Inventario'
                });
                /*if (!noEmp) return Swal.fire({
                    icon: 'warning',
                    title: 'Falta noEmpresa'
                });*/

                // Serializar tarjeta → payload
                const data = serializeArticleCard($card, {
                    linea,
                    noInventario: noInv,
                });

                // Validar que haya al menos 1 fila con piezas > 0
                const piezasTot = data.conteoTotal;
                if (piezasTot <= 0) {
                    return Swal.fire({
                        icon: 'info',
                        title: 'Sin piezas',
                        text: 'Captura corrugados/cajas antes de guardar.'
                    });
                }

                // Estado de guardado
                toggleSaving($btn, true);

                try {
                    const resp = await $.ajax({
                        url: '../Servidor/PHP/inventario.php',
                        method: 'POST',
                        dataType: 'json',
                        headers: {
                            'X-CSRF-Token': csrf
                        },
                        data: {
                            numFuncion: '5',
                            payload: JSON.stringify(data)
                        }
                    });

                    if (!resp || resp.success !== true) {
                        throw new Error(resp?.message || 'Error de servidor');
                    }
                    ////////////////////////////////////////////////////////////////
                    const files = window.MDPDFs?.getSelected?.() || [];
                    if (files.length) {
                        await subirPDFsLineas(files, {
                            tipo: 'producto',
                            linea,
                            cve_art: String(code)
                        });
                        window.MDPDFs?.reset?.();
                    }
                    ////////////////////////////////////////////////////////////////
                    markSaved($card, resp);
                    Swal.fire({
                        icon: 'success',
                        title: 'Guardado',
                        text: 'Producto guardado correctamente.'
                    });

                } catch (err) {
                    console.error('Guardar producto error:', err);
                    Swal.fire({
                        icon: 'error',
                        title: 'No se pudo guardar',
                        text: String(err.message || err)
                    });

                } finally {
                    toggleSaving($btn, false);
                }
            });
            // Cuando el usuario pulsa "Guardar Imágenes" en una tarjeta
            $articulos.on('click', '.btn-save-image', function() {
                const $card = $(this).closest('.article-card');
                const code = $card.data('articulo') || $.trim($card.find('.code').text());
                const linea = $('#lineaSelect').val();

                if (!linea) return Swal.fire({
                    icon: 'warning',
                    title: 'Selecciona una línea'
                });
                if (!code) return Swal.fire({
                    icon: 'warning',
                    title: 'No se detectó la clave del artículo'
                });

                // 1) Fija destino → esto limpia la selección si cambió de artículo
                window.MDPDFs?.setTarget?.({
                    tipo: 'producto',
                    linea,
                    cve_art: String(code)
                });

                // 2) Abre el modal
                abrirModalPdf();
            });
            // Serializa una tarjeta a objeto listo para guardar
            function serializeArticleCard($card, meta) {
                const codigo = $card.data('articulo');
                const exist = Number($card.data('exist') || 0);
                const nombre = $.trim($card.find('.name').text());
                const totalTxt = $.trim($card.find('.article-total').text());
                const conteo = Number(totalTxt || 0);

                // Lotes
                const lotes = [];
                $card.find('.row-line').each((_, el) => {
                    const $row = $(el);
                    const loteI = $row.find('.lote input').val(); // si es input (nuevos)
                    const loteT = $row.find('.lote').text().trim(); // si es texto (cargados)
                    const lote = (loteI ?? '').trim() || loteT || null;

                    const corr = Number($row.find('.qty-input-corrugado').val()) || 0;
                    const cajas = Number($row.find('.qty-input-cajas').val()) || 0;
                    const sueltos = Number($row.find('.qty-input-sueltos').val()) || 0;
                    const piezas = (corr * cajas) + sueltos;

                    // Solo guarda filas con algo de captura
                    if ((corr > 0 && cajas > 0) || (lote && piezas >= 0)) {
                        lotes.push({
                            lote,
                            corrugados: corr,
                            cajasPorCorrugado: cajas,
                            sueltos,
                            piezas
                        });
                    }
                });

                return {
                    // Metadatos de cabecera
                    linea: meta.linea,
                    noInventario: String(meta.noInventario),
                    noEmpresa: String(meta.noEmpresa),

                    // Producto
                    cve_art: String(codigo),
                    descr: nombre,
                    existSistema: exist,

                    // Captura
                    conteoTotal: conteo,
                    diferencia: conteo - exist, // útil para ajustes
                    lotes: lotes,

                    // Opcional: timestamp local
                    tsLocal: new Date().toISOString()
                };
            }
            // UI helpers
            function toggleSaving($btn, saving) {
                const $status = $btn.closest('.rows').find('.save-status');
                if (saving) {
                    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Guardando...');
                    $status.text('Guardando…').show();
                } else {
                    $btn.prop('disabled', false).html('<i class="bx bx-save"></i> Guardar producto');
                    $status.hide().text('');
                }
            }

            function toggleImage($btn, saving) {
                const $status = $btn.closest('.rows').find('.save-status');
                if (saving) {
                    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Guardando...');
                    $status.text('Guardando…').show();
                } else {
                    $btn.prop('disabled', false).html('<i class="bx bx-save"></i> Guardar producto');
                    $status.hide().text('');
                }
            }

            function markSaved($card, resp) {
                const $badge = $card.find('.article-total');
                $badge.removeClass('text-primary').addClass('text-success');
                const $status = $card.find('.save-status');
                $status.text('Guardado ✓').show();
            }
            /****/
            // Tarjeta de un artículo (versión espaciosa)
            function buildArticleCard(item) {
                const lotes = (item.lotes || []).map((r, i) => `
                <div class="row-line row-line">
                    <div class="lote">${escapeHtml(r.LOTE ?? `Lote ${i+1}`)}</div>

                    <label class="field label">
                    <span>Corrugado:</span>
                    <input type="number" class="form-control qty-input-corrugado" value="0" min="0" step="1">
                    </label>

                    <label class="field label">
                    <span>Cajas por Corrugado:</span>
                    <input type="number" class="form-control qty-input-cajas" value="0" min="0" step="1">
                    </label>

                    <label class="field label">
                    <span>Cajas sueltas:</span>
                    <input type="number" class="form-control qty-input-sueltos" value="0" min="0" step="1">
                    </label>

                    <label class="field label">
                    <span>Total:</span>
                    <input type="number" class="form-control qty-input-total" value="0" min="0" step="1">
                    </label>
                </div>
                `).join('');
                return `
                        <section class="article-card spacious" data-articulo="${escapeAttr(item.codigo)}" data-exist="${item.exist}">
                        <header class="article-head article-head--spaced">
                            <div class="code">${escapeHtml(item.codigo)}</div>
                            <a class="name" href="javascript:void(0)">${escapeHtml(item.nombre)}</a>
                            <div class="exist">Inventario: ${escapeHtml(item.exist)}</div>
                            <div class="total">
                            Conteo:
                            <span class="badge bg-primary-subtle text-primary fw-semibold article-total">0</span>
                            </div>
                            <!-- Botón Guardar del producto -->
                            <button type="button" class="btn btn-success btn-save-article">
                                <i class="bx bx-save"></i> Guardar producto
                            </button>
                            <span class="save-status ms-2 text-muted" style="display:none;"></span>
                            <button type="button" class="btn btn-success btn-save-image">
                                <i class="bi bi-image"></i> Guardar Imagenes
                            </button>
                            </div>
                        </header>

                        <div class="rows rows--spaced">
                            ${lotes}
                            <div class="row-add row-add--spaced">
                            <button type="button" class="btn btn-outline-primary btn-add-row" title="Agregar lote">
                                <i class="bx bx-plus"></i>
                            </button>
                            </div>
                        </div>
                        </section>
                `;
            }
            // Loader
            function skeleton() {
                return `
                <div class="article-card">
                    <header class="article-head">
                    <div class="placeholder-glow w-75"><span class="placeholder col-6"></span></div>
                    <div></div><div></div><div></div>
                    </header>
                    <div class="rows">
                    ${['','',''].map(() => `
                        <div class="row-line">
                        <div class="placeholder-glow col-2"><span class="placeholder col-8"></span></div>
                        <div class="placeholder-glow"><span class="placeholder col-10"></span></div>
                        <div class="placeholder-glow col-2"><span class="placeholder col-12"></span></div>
                        <div class="placeholder-glow col-2"><span class="placeholder col-12"></span></div>
                        </div>
                    `).join('')}
                    </div>
                </div>
                `;
            }
            // Helpers
            function escapeHtml(str) {
                return String(str ?? '').replace(/[&<>"']/g, m => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                } [m]));
            }

            function escapeAttr(str) {
                return escapeHtml(str).replace(/"/g, '&quot;');
            }
        })();
    </script>

    <script>
        (function() {
            const $btnGuardar = document.getElementById('guardarPedido'); // ← TU botón verde
            const $formPedido = document.getElementById('formPedido') || document.querySelector('form');
            const $metaPDFInput = document.getElementById('ordenCompraMeta');

            // Usa la función global subirPDFs(selectedFiles) ya definida en pdfs.js
            async function subirSeleccionSiAplica() {
                const files = (window.MDPDFs && window.MDPDFs.getSelected) ? window.MDPDFs.getSelected() : [];
                if (!files.length) return null; // no hay PDFs

                // Sube AQUÍ (NO en el modal). Esto llama a tu enviarHistorico.php internamente.
                const resp = await subirPDFsLineas(files);
                return resp || null;
            }

            $btnGuardar?.addEventListener('click', async function(e) {
                // si hay form y lo envías por submit, bloquea mientras subimos
                if ($formPedido) e.preventDefault();

                try {
                    Swal.fire({
                        title: 'Guardando…',
                        allowOutsideClick: false,
                        didOpen: Swal.showLoading,
                        returnFocus: false
                    });

                    // 1) Subir PDFs seleccionados (si hay)
                    const resultadoPDFs = await subirSeleccionSiAplica();

                    // 2) Pasa metadatos al backend (opcional: ajusta a lo que devuelva tu PHP)
                    if ($metaPDFInput) {
                        $metaPDFInput.value = JSON.stringify(resultadoPDFs || {});
                    }

                    // 3) Guardado general (tu flujo actual). Si es form normal:
                    if ($formPedido) $formPedido.submit();
                    // Si tú guardas por AJAX, llama aquí tu función que guarda en Firestore
                    // await guardarEnFirestore(...);

                    // 4) Limpia selección temporal del modal
                    window.MDPDFs?.reset?.();

                } catch (err) {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al guardar',
                        text: (err && err.message) || String(err),
                        returnFocus: false
                    });
                }
            });
        })();
    </script>
</body>

</html>
<!--
				<script>
			var empresa = '<?php // echo $nombreEmpresa
                            ?>'
			console.log(empresa);
		</script>
		-->