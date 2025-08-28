<?php
session_start();
if (isset($_SESSION['usuario'])) {
    if ($_SESSION['usuario']['tipoUsuario'] == 'CLIENTE') {
        header('Location:Menu.php');
        exit();
    }
    $nombreUsuario = $_SESSION['usuario']["nombre"];
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

    <!-- My CSS -->
    <link rel="stylesheet" href="CSS/style.css">

    <link rel="stylesheet" href="CSS/selec.css">

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
                                <!-- <input type="text" id="noInventario" class="form-control inv-num" value="0001"> -->
                                <input type="text" id="noInventario" class="form-control inv-num" value="" disabled>
                            </div>

                            <div class="col-6 col-md-3 col-lg-2">
                                <label for="fechaInicio" class="form-label m-0">Fecha inicio:</label>
                                <input type="date" id="fechaInicio" class="form-control">
                            </div>

                            <div class="col-6 col-md-3 col-lg-2">
                                <label for="fechaFin" class="form-label m-0">Fecha fin:</label>
                                <input type="date" id="fechaFin" class="form-control">
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
    </section>
    <div id="resumenInventario" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Resumen del Inventario Fisico</h5>
                    <button type="button" class="btn-close custom-close" data-dismiss="modal" aria-label="Close"
                        id="cerrarModalHeader">
                        <span aria-hidden="true"></span><!-- &times; -->
                    </button>
                </div>
                <form>
                    <div class="form-row">
                        <input type="hidden" name="csrf_tokenModal" id="csrf_tokenModal" value="<?php echo $csrf_token; ?>">
                    </div>
                    <div>
                        <h5><input type="text" name="lineaSeleccionada" id="lineaSeleccionada" style="border: none;" readonly1></h5>
                    </div>

                    <table class="form-table">
                        <thead>
                            <tr>
                                <th>Clave</th>
                                <th>Articulo</th>
                                <th>SAE</th>
                                <th>Conteo</th>
                                <th>Diferencia</th>
                            </tr>
                        </thead>
                    </table>

                    <div class="form-buttons">
                        <button type="button" class="btn btn-primary" id="generararPDF">Generar PDF</button>
                        <button type="button" class="btn btn-save" id="exportarExcel">Exportar Excel</button>
                        <button type="button" class="btn btn-primary" id="otroConteo">Otro Conte</button>
                        <button type="button" class="btn btn-primary" id="finalizarInventario">Finalizar Inventario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- CONTENT -->
    </div>
    <!-- JS Para la confirmacion empresa -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/menu.js"></script>
    <script src="JS/app.js"></script>
    <script src="JS/script.js"></script>
    <script src="JS/inventarioLineas.js"></script>
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
                            const piezas = corr * cajas;

                            $row.find('.pres').text(piezas);
                            sum += piezas;
                        });
                        $card.find('.article-total').text(sum);
                    }

                    // Disparar recalc al cambiar cualquiera de los dos inputs
                    $card.on('input change', '.qty-input-corrugado, .qty-input-cajas', recalc);

                    $card.on('click', '.btn-add-row', function() {
                        const idx = $card.find('.row-line').length + 1;
                        const row = `
                            <div class="row-line row-line--spaced">
                            <div class="lote">
                                <input type="text" class="form-control" placeholder="Lote ${idx}">
                            </div>

                            <label class="field label">
                                <span>Corrugado:</span>
                                <input type="number"
                                    class="form-control qty-input-corrugado"
                                    value="0" min="0" step="1">
                            </label>

                            <label class="field label">
                                <span>Cajas por Corrugado:</span>
                                <input type="number"
                                    class="form-control qty-input-cajas"
                                    value="0" min="0" step="1">
                            </label>

                            <!--<div class="pres">—</div>-->

                            <div class="eliminar">
                                <button type="button"
                                        class="btn btn-danger btn-sm eliminarLote"
                                        title="Eliminar lote">
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

            // Tarjeta de un artículo (versión espaciosa)
            function buildArticleCard(item) {
                const lotes = (item.lotes || []).map((r, i) => `
                    <div class="row-line row-line--spaced">
                    <div class="lote">${escapeHtml(r.LOTE ?? `Lote ${i+1}`)}</div>

                    <label class="field label">
                        <span>Corrugado:</span>
                        <input type="number" class="form-control qty-input-corrugado" value="" min="0" step="1">
                    </label>

                    <label class="field label">
                        <span>Cajas por Corrugado:</span>
                        <input type="number" class="form-control qty-input-cajas" value="" min="0" step="1">
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
</body>

</html>
<!-- 
				<script>
			var empresa = '<?php // echo $nombreEmpresa 
                            ?>'
			console.log(empresa);
		</script>
		-->