<?php
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;
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
    <!-- Script -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <title>MDConnecta</title>
</head>

<body>

    <div class="">
        <!-- SIDEBAR -->

        <!-- CONTENT -->
        <section id="content">
            <!-- NAVBAR -->

            <!-- MAIN -->
            <main class="text-center ">


                <div class="head-title">
                    <div class="left">
                        <h1>Crear Empresa</h1>
                        <ul class="breadcrumb">
                    </div>
                    <!-- 
      <ul class="box-info">
        <li>
          <i class='bx bxs-calendar-check' ></i>
          <span class="text">
            <h3>1020</h3>
            <p>New Order</p>
          </span>
        </li>
        <li>
          <i class='bx bxs-group' ></i>
          <span class="text">
            <h3>2834</h3>
            <p>Visitors</p>
          </span>
        </li>
        <li>
          <i class='bx bxs-dollar-circle' ></i>
          <span class="text">
            <h3>$2543</h3>
            <p>Total Sales</p>
          </span>
        </li>
      </ul>
-->
                    <div class="table-data">
                        <div class="order">
                            <div class="head">
                                <h3></h3>
                                <!--<i class='bx bx-search'></i> -->
                                <!-- <i class='bx bx-filter' > Campos Obligatorios </i> -->
                                <a class=''> Campos Obligatorios * </a>

                            </div>
                            <form onsubmit="return validateForm()">
                                <div class="form-row">
                                    <input type="hidden" name="csrf_tokenNew" id="csrf_tokenNew" value="<?php echo $csrf_token; ?>">
                                    <label for="noEmpresa">No. Empresa:</label>
                                    <select name="noEmpresaNew" id="noEmpresaNew" placeholder="Selecciona opcion" value="">
                                        <option selected disabled>Selecciona un numero de empresa</option>
                                        <option value="1">1</option>
                                        <option value="2">2</option>
                                        <option value="3">3</option>
                                        <option value="4">4</option>
                                        <option value="5">5</option>
                                        <option value="6">6</option>
                                        <option value="7">7</option>
                                        <option value="8">8</option>
                                        <!-- Agrega más opciones si es necesario -->
                                    </select>
                                </div>

                                <div class="form-row">
                                    <!-- <a class='bx bx-message-rounded-error'></a> -->
                                    <label for="razonSocial">Razón Social: <a class='bx'> *</a></label>
                                    <input class="input-m" type="text" name="razonSocialNew" id="razonSocialNew" values="">
                                </div>

                                <div class="form-row">
                                    <!-- <a class='bx bx-message-rounded-error'></a> -->
                                    <label for="rfc">RFC:<a class='bx'>*</a></label>
                                    <input class="input-m" type="text" name="rfcNew" id="rfcNew" placeholder="RFC" value="">
                                </div>

                                <div class="form-row">
                                    <!-- <a class='bx bx-message-rounded-error'></a> -->
                                    <label for="regimenFiscal">Régimen Fiscal: <a class='bx'>*</a></label>
                                    <select name="regimenFiscalNew" id="regimenFiscalNew" placeholder="Selecciona opcion" value="" disabled>
                                        <option selected disabled>Selecciona un regimen</option>
                                        <!-- Agrega más opciones si es necesario -->
                                    </select>
                                </div>

                                <div class="form-row">
                                    <label for="Calle">Calle:</label>
                                    <input class="input-m" type="text" name="calleNew" id="calleNew" placeholder="Calle">
                                </div>

                                <div class="form-row">
                                    <label for="numExterior">Num. Exterior:</label>
                                    <input class="input-small" type="text" name="numExteriorNew" id="numExteriorNew" placeholder="Num. Exterior">
                                    <label for="numInterior">Num. Interior:</label>
                                    <input class="input-small" type="text" name="numInteriorNew" id="numInteriorNew" placeholder="Num. Interior">
                                    <label for="entreCalle">Entre Calle:</label>
                                    <input class="input-m" type="text" name="entreCalleNew" id="entreCalleNew" placeholder="Entre Calle">
                                </div>

                                <div class="form-row">
                                    <label for="colonia">Colonia:</label>
                                    <input class="input-m" type="text" name="coloniaNew" id="coloniaNew" placeholder="Colonia">
                                    <label for="referencia">Referencia:</label>
                                    <input class="input-m" type="text" name="referenciaNew" id="referenciaNew" placeholder="Referencia">
                                </div>

                                <div class="form-row">
                                    <label for="pais">País:</label>
                                    <input class="input-m" type="text" name="paisNew" id="paisNew" placeholder="País">
                                    <label for="estado">Estado:</label>
                                    <input class="input-m" type="text" name="estadoNew" id="estadoNew" placeholder="Estado">
                                    <label for="municipio">Municipio:</label>
                                    <input class="input-m" type="text" name="municipioNew" id="municipioNew" placeholder="Municipio">
                                </div>

                                <div class="form-row">
                                    <!-- <a class='bx bx-message-rounded-error'></a> -->
                                    <label for="cp">Codigo Postal: <a class='bx'> *</a></label>
                                    <input class="input-m" type="text" name="codigoPostalNew" id="codigoPostalNew" placeholder="Codigo Postal">
                                </div>

                                <div class="form-row">
                                    <label for="poblacion">Población:</label>
                                    <input class="input-m" type="text" name="poblacionNew" id="poblacionNew" placeholder="Poblacion">
                                    <button class='bx bx-help-circle' id="Ayuda"></button>
                                </div>

                                <div class="form-buttons">
                                    <button type="submit" class="btn-save" id="guardarEmpresaNew">Guardar</button>
                                    <button type="button" class="btn-cancel" id="cancelarEmpresa">Cancelar</button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>


            </main>
            <!-- MAIN -->
        </section>

        <!-- CONTENT -->
    </div>
    </section>
    <!-- CONTENT -->
    <script>
        function debouncenNew(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }
        $(document).ready(function() {
            $("#rfcNew").on("input", debouncenNew(function() {
                obtenerRegimenNew();
            }, 300));
        });
    </script>
    <!-- JS Para la confirmacion empresa -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="JS/menu.js"></script>
    <script src="JS/app.js"></script>
    <script src="JS/script.js"></script>

</body>

</html>