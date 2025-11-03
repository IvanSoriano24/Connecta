<?php
// Establecer headers UTF-8 para respuestas JSON
header('Content-Type: application/json; charset=utf-8');
mb_internal_encoding('UTF-8');

session_start();
require 'firebase.php';

// Validar sesión
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Función helper para obtener documento de Firebase
function obtenerDocumentoFirebase($firebaseProjectId, $firebaseApiKey, $collection, $documentId) {
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/$collection/$documentId?key=$firebaseApiKey";
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'header' => "Content-Type: application/json\r\n"
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return null;
    }
    
    $data = json_decode($response, true);
    if (!isset($data['fields'])) {
        return null;
    }
    
    // Convertir campos de Firestore a array simple
    $fields = [];
    foreach ($data['fields'] as $key => $value) {
        if (isset($value['stringValue'])) {
            $fields[$key] = $value['stringValue'];
        } elseif (isset($value['integerValue'])) {
            $fields[$key] = (int)$value['integerValue'];
        } elseif (isset($value['doubleValue'])) {
            $fields[$key] = (float)$value['doubleValue'];
        } elseif (isset($value['booleanValue'])) {
            $fields[$key] = (bool)$value['booleanValue'];
        }
    }
    
    return $fields;
}

// Función para obtener conexión SAE
function obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae) {
    try {
        // Buscar en la colección CONEXIONES como lo hacen otros archivos
        $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/CONEXIONES?key=$firebaseApiKey";
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
                'header' => "Content-Type: application/json\r\n"
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            return ['success' => false, 'message' => 'Error al obtener conexiones de Firebase'];
        }
        
        $data = json_decode($response, true);
        if (!isset($data['documents'])) {
            return ['success' => false, 'message' => 'No se encontraron conexiones'];
        }
        
        // Buscar la conexión por noEmpresa
        $conexionDoc = null;
        // Asegurar que noEmpresa sea un entero para comparación
        $noEmpresaInt = (int)$noEmpresa;
        foreach ($data['documents'] as $document) {
            $fields = $document['fields'];
            // Intentar obtener noEmpresa de diferentes formas posibles
            $empNoEmpresa = null;
            if (isset($fields['noEmpresa']['integerValue'])) {
                $empNoEmpresa = (int)$fields['noEmpresa']['integerValue'];
            } elseif (isset($fields['noEmpresa']['stringValue'])) {
                $empNoEmpresa = (int)$fields['noEmpresa']['stringValue'];
            }
            
            if ($empNoEmpresa !== null && $empNoEmpresa === $noEmpresaInt) {
                $conexionDoc = $fields;
                break;
            }
        }
        
        if (!$conexionDoc) {
            return ['success' => false, 'message' => 'No se encontró la conexión para la empresa'];
        }

        $servidor = isset($conexionDoc['host']['stringValue']) ? $conexionDoc['host']['stringValue'] : null;
        $usuario = isset($conexionDoc['usuario']['stringValue']) ? $conexionDoc['usuario']['stringValue'] : null;
        $contrasena = isset($conexionDoc['password']['stringValue']) ? $conexionDoc['password']['stringValue'] : null;
        $baseDatos = isset($conexionDoc['nombreBase']['stringValue']) ? $conexionDoc['nombreBase']['stringValue'] : null;

        if (!$servidor || !$usuario || !$baseDatos) {
            return ['success' => false, 'message' => 'Datos de conexión incompletos'];
        }

        $conn = new PDO(
            "sqlsrv:Server=$servidor;Database=$baseDatos",
            $usuario,
            $contrasena
        );
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return [
            'success' => true,
            'data' => [
                'conn' => $conn,
                'claveSae' => $claveSae,
                'nombreBase' => $baseDatos,
                'host' => $servidor,
                'usuario' => $usuario,
                'password' => $contrasena
            ]
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error de conexión: ' . $e->getMessage()];
    }
}

// ==================== CASE 1: MOSTRAR MUESTRAS ====================
function mostrarMuestras($conexionData, $filtroFecha, $estadoMuestra, $filtroVendedor, $searchTerm, $offset, $limit) {
    try {
        $conn = $conexionData['conn'];
        $claveSae = $conexionData['claveSae'];

        // Construir nombres de tablas dinámicamente
        $tablaFactp = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $tablaClie = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $tablaVend = "[{$conexionData['nombreBase']}].[dbo].[VEND" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

        // Construir condiciones WHERE
        $where = ["TIP_DOC = 'M'"];

        // Filtro por estado
        if ($estadoMuestra === 'Activas') {
            $where[] = "STATUS = 'E'";
        } elseif ($estadoMuestra === 'Entregadas') {
            $where[] = "STATUS = 'T'";
        } elseif ($estadoMuestra === 'Canceladas') {
            $where[] = "STATUS = 'C'";
        }

        // Filtro por fecha
        if ($filtroFecha === 'Hoy') {
            $where[] = "CONVERT(date, FECHA_DOC) = CONVERT(date, GETDATE())";
        } elseif ($filtroFecha === 'Mes') {
            $where[] = "MONTH(FECHA_DOC) = MONTH(GETDATE()) AND YEAR(FECHA_DOC) = YEAR(GETDATE())";
        } elseif ($filtroFecha === 'Mes Anterior') {
            $where[] = "MONTH(FECHA_DOC) = MONTH(DATEADD(MONTH, -1, GETDATE())) AND YEAR(FECHA_DOC) = YEAR(DATEADD(MONTH, -1, GETDATE()))";
        }

        // Filtro por vendedor
        if (!empty($filtroVendedor)) {
            $where[] = "CVE_VEND = '$filtroVendedor'";
        }

        // Búsqueda
        if (!empty($searchTerm)) {
            $where[] = "(CVE_DOC LIKE '%$searchTerm%' OR CVE_CLPV LIKE '%$searchTerm%')";
        }

        $whereClause = implode(' AND ', $where);

        // Contar total
        $sqlCount = "SELECT COUNT(*) as total FROM $tablaFactp WHERE $whereClause";
        $stmtCount = $conn->query($sqlCount);
        $total = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];

        // Obtener registros con paginación
        $sql = "
            SELECT TOP ($limit)
                f.CVE_DOC as Clave,
                f.CVE_CLPV as Cliente,
                c.NOMBRE as Nombre,
                CONVERT(varchar, f.FECHA_DOC, 23) as FechaElaboracion,
                v.NOMBRE as NombreVendedor
            FROM $tablaFactp f
            LEFT JOIN $tablaClie c ON f.CVE_CLPV = c.CLAVE
            LEFT JOIN $tablaVend v ON f.CVE_VEND = v.CVE_VEND
            WHERE $whereClause
            AND f.CVE_DOC NOT IN (
                SELECT TOP ($offset) CVE_DOC
                FROM $tablaFactp
                WHERE $whereClause
                ORDER BY FECHA_DOC DESC, CVE_DOC DESC
            )
            ORDER BY f.FECHA_DOC DESC, f.CVE_DOC DESC
        ";

        $stmt = $conn->query($sql);
        $muestras = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $muestras,
            'total' => $total
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

// ==================== CASE 2: MOSTRAR MUESTRA ESPECÍFICA ====================
function mostrarMuestraEspecifica($muestraID, $conexionData) {
    try {
        $conn = $conexionData['conn'];
        $claveSae = $conexionData['claveSae'];

        $tablaFactp = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $tablaClie = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

        $sql = "
            SELECT
                f.CVE_DOC,
                f.CVE_CLPV,
                c.NOMBRE as NOMBRE_CLIENTE,
                CONVERT(varchar, f.FECHA_DOC, 23) as FECHA_DOC,
                CONVERT(varchar, f.FECHA_ENT, 23) as FECHA_ENT,
                f.OBSERV,
                f.STATUS
            FROM $tablaFactp f
            LEFT JOIN $tablaClie c ON f.CVE_CLPV = c.CLAVE
            WHERE f.CVE_DOC = ? AND f.TIP_DOC = 'M'
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$muestraID]);
        $muestra = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($muestra) {
            echo json_encode(['success' => true, 'data' => $muestra], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'message' => 'Muestra no encontrada'], JSON_UNESCAPED_UNICODE);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

// ==================== FUNCIÓN: OBTENER FOLIO DESDE FIREBASE FOLIOS ====================
function obtenerFolioDesdeFirebase($firebaseProjectId, $firebaseApiKey, $noEmpresa) {
    $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/FOLIOS?key=$firebaseApiKey";
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Content-Type: application/json\r\n"
        ]
    ]);

    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return null;
    }

    $foliosData = json_decode($response, true);
    if (!isset($foliosData['documents'])) {
        return null;
    }

    // Buscar el documento donde documento="muestras" y noEmpresa coincide
    foreach ($foliosData['documents'] as $document) {
        $fields = $document['fields'];
        $docDocumento = isset($fields['documento']['stringValue']) ? $fields['documento']['stringValue'] : null;
        $docNoEmpresa = isset($fields['noEmpresa']['integerValue']) ? (int)$fields['noEmpresa']['integerValue'] : null;
        
        if ($docDocumento === 'muestras' && $docNoEmpresa === $noEmpresa) {
            // Extraer el ID del documento
            $fullName = $document['name'];
            $parts = explode('/', $fullName);
            $docId = end($parts);
            
            // Obtener folioSiguiente o folioInicial
            $folioSiguiente = isset($fields['folioSiguiente']['integerValue']) ? (int)$fields['folioSiguiente']['integerValue'] : null;
            $folioInicial = isset($fields['folioInicial']['integerValue']) ? (int)$fields['folioInicial']['integerValue'] : 1;
            
            // Si existe folioSiguiente, usarlo; si no, usar folioInicial o 1
            $folio = $folioSiguiente ? $folioSiguiente : ($folioInicial ? $folioInicial : 1);
            
            return [
                'documentId' => $docId,
                'folio' => $folio,
                'folioSiguiente' => $folioSiguiente,
                'folioInicial' => $folioInicial
            ];
        }
    }
    
    // Si no existe el documento, crear uno nuevo con folio 1
    return [
        'documentId' => null,
        'folio' => 1,
        'folioSiguiente' => null,
        'folioInicial' => 1
    ];
}

// ==================== FUNCIÓN: ACTUALIZAR FOLIO EN FIREBASE ====================
function actualizarFolioFirebase($firebaseProjectId, $firebaseApiKey, $docId, $nuevoFolio) {
    if (!$docId) {
        // Crear nuevo documento si no existe
        $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/FOLIOS?key=$firebaseApiKey";
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $fields = [
            'documento' => ['stringValue' => 'muestras'],
            'noEmpresa' => ['integerValue' => $noEmpresa],
            'folioSiguiente' => ['integerValue' => $nuevoFolio],
            'folioInicial' => ['integerValue' => 1]
        ];
        
        $payload = json_encode(['fields' => $fields]);
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $payload
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        return $response !== false;
    } else {
        // Actualizar documento existente
        $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/FOLIOS/$docId?updateMask.fieldPaths=folioSiguiente&key=$firebaseApiKey";
        $data = [
            'fields' => [
                'folioSiguiente' => ['integerValue' => $nuevoFolio]
            ]
        ];
        
        $context = stream_context_create([
            'http' => [
                'method' => 'PATCH',
                'header' => "Content-Type: application/json\r\n",
                'content' => json_encode($data)
            ]
        ]);

        $response = @file_get_contents($url, false, $context);
        return $response !== false;
    }
}

// ==================== CASE 3: OBTENER FOLIO SIGUIENTE O PARTIDAS ====================
function obtenerFolioSiguiente($conexionData) {
    global $firebaseProjectId, $firebaseApiKey;
    
    try {
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $folioInfo = obtenerFolioDesdeFirebase($firebaseProjectId, $firebaseApiKey, $noEmpresa);
        
        if (!$folioInfo) {
            echo json_encode(['success' => false, 'message' => 'Error al obtener folio desde Firebase']);
            return;
        }
        
        echo json_encode(['success' => true, 'folio' => $folioInfo['folio']]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function obtenerPartidasMuestra($muestraID, $conexionData) {
    try {
        $conn = $conexionData['conn'];
        $claveSae = $conexionData['claveSae'];

        $tablaParFactp = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $tablaInve = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

        $sql = "
            SELECT
                p.NUM_PAR,
                p.CVE_ART,
                i.DESCR,
                p.CANT,
                p.UNI_VENTA
            FROM $tablaParFactp p
            LEFT JOIN $tablaInve i ON p.CVE_ART = i.CVE_ART
            WHERE p.CVE_DOC = ?
            ORDER BY p.NUM_PAR
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$muestraID]);
        $partidas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $partidas]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// ==================== CASE 4: BUSCAR CLIENTE ====================
function buscarCliente($termino, $conexionData) {
    try {
        $conn = $conexionData['conn'];
        $claveSae = $conexionData['claveSae'];

        $tablaClie = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

        $sql = "
            SELECT TOP 10 CLAVE, NOMBRE
            FROM $tablaClie
            WHERE (CLAVE LIKE ? OR NOMBRE LIKE ?)
            AND STATUS = 'ALTA'
            ORDER BY NOMBRE
        ";

        $stmt = $conn->prepare($sql);
        $busqueda = "%$termino%";
        $stmt->execute([$busqueda, $busqueda]);
        $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $clientes], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

// ==================== CASE 5: BUSCAR PRODUCTOS ====================
function buscarProductos($termino, $conexionData) {
    try {
        $conn = $conexionData['conn'];
        $claveSae = $conexionData['claveSae'];

        $tablaInve = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $tablaMult = "[{$conexionData['nombreBase']}].[dbo].[MULT" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

        $sql = "
            SELECT TOP 10
                i.CVE_ART,
                i.DESCR,
                i.UNI_VENTA,
                ISNULL(m.EXIST, 0) as EXIST
            FROM $tablaInve i
            LEFT JOIN $tablaMult m ON i.CVE_ART = m.CVE_ART AND m.CVE_ALM = '2'
            WHERE (i.CVE_ART LIKE ? OR i.DESCR LIKE ?)
            AND i.STATUS = 'ALTA'
            ORDER BY i.DESCR
        ";

        $stmt = $conn->prepare($sql);
        $busqueda = "%$termino%";
        $stmt->execute([$busqueda, $busqueda]);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'productos' => $productos], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

// ==================== FUNCIONES AUXILIARES PARA MOVIMIENTOS SAE ====================
function insertarMovimientosMINVE($conexionData, $folio, $partidas, $claveSae, $clienteClave, $vendedor, $conn) {
    // Construir nombres de tablas dinámicamente
    $tablaMult = "[{$conexionData['nombreBase']}].[dbo].[MULT" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaMinve = "[{$conexionData['nombreBase']}].[dbo].[MINVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaControl = "[{$conexionData['nombreBase']}].[dbo].[TBLCONTROL" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    
    // Obtener NUM_MOV siguiente (ID_TABLA = 44)
    $sqlUltimos = "SELECT ULT_CVE + 1 AS NUM_MOV FROM $tablaControl WHERE ID_TABLA = 44";
    $stmtUltimos = sqlsrv_query($conn, $sqlUltimos);
    if ($stmtUltimos === false) {
        throw new Exception("Error al obtener NUM_MOV: " . print_r(sqlsrv_errors(), true));
    }
    $ultimos = sqlsrv_fetch_array($stmtUltimos, SQLSRV_FETCH_ASSOC);
    $numMov = $ultimos['NUM_MOV'];
    
    // Obtener CVE_FOLIO siguiente (ID_TABLA = 32)
    $sqlFolio = "SELECT ULT_CVE + 1 AS CVE_FOLIO FROM $tablaControl WHERE ID_TABLA = 32";
    $stmtFolio = sqlsrv_query($conn, $sqlFolio);
    if ($stmtFolio === false) {
        throw new Exception("Error al obtener CVE_FOLIO: " . print_r(sqlsrv_errors(), true));
    }
    $folioData = sqlsrv_fetch_array($stmtFolio, SQLSRV_FETCH_ASSOC);
    $cveFolio = $folioData['CVE_FOLIO'];
    
    $folioDoc = str_pad($folio, 10, '0', STR_PAD_LEFT);
    $folioDoc = str_pad($folioDoc, 20, ' ', STR_PAD_LEFT);
    $cantMov = 0;
    
    foreach ($partidas as $partida) {
        $cveArt = str_pad($partida['CVE_ART'], 20, ' ', STR_PAD_LEFT);
        $almacen = '2'; // Multialmacen 2 para muestras
        $cantidad = $partida['CANT'];
        
        // Obtener existencia actual del almacén 2
        $sqlExist = "SELECT EXIST FROM $tablaMult WHERE CVE_ART = ? AND CVE_ALM = ?";
        $paramsExist = [$cveArt, $almacen];
        $stmtExist = sqlsrv_query($conn, $sqlExist, $paramsExist);
        if ($stmtExist === false) {
            throw new Exception("Error al obtener existencia: " . print_r(sqlsrv_errors(), true));
        }
        $existData = sqlsrv_fetch_array($stmtExist, SQLSRV_FETCH_ASSOC);
        $existencia = $existData ? $existData['EXIST'] : 0;
        
        // Insertar en MINVE
        $fechaDocu = date('Y-m-d');
        $fechaElab = date('Y-m-d H:i:s');
        $cveCpto = 52; // Concepto para muestras (ajustar según necesidad)
        $tipoDoc = 'M'; // Tipo de documento M = Muestra
        
        $sqlInsert = "INSERT INTO $tablaMinve 
            (CVE_ART, ALMACEN, NUM_MOV, CVE_CPTO, FECHA_DOCU, TIPO_DOC, REFER, CLAVE_CLPV, VEND, CANT, 
            CANT_COST, PRECIO, COSTO, REG_SERIE, UNI_VENTA, EXIST_G, EXISTENCIA, FACTOR_CON, 
            FECHAELAB, CVE_FOLIO, SIGNO, COSTEADO, COSTO_PROM_INI, COSTO_PROM_FIN, COSTO_PROM_GRAL, 
            DESDE_INVE, MOV_ENLAZADO, DOCUMENTO) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $uniVenta = $partida['UNI_VENTA'] ?? '';
        $costo = 0; // Costo para muestras
        $precio = 0; // Precio para muestras
        
        $paramsInsert = [
            $cveArt,              // 1. CVE_ART
            $almacen,             // 2. ALMACEN
            $numMov,              // 3. NUM_MOV
            $cveCpto,             // 4. CVE_CPTO
            $fechaDocu,           // 5. FECHA_DOCU
            $tipoDoc,             // 6. TIPO_DOC
            $folioDoc,            // 7. REFER
            $clienteClave,        // 8. CLAVE_CLPV
            $vendedor,            // 9. VEND
            $cantidad,            // 10. CANT
            0,                    // 11. CANT_COST
            $precio,              // 12. PRECIO
            $costo,               // 13. COSTO
            0,                    // 14. REG_SERIE
            $uniVenta,            // 15. UNI_VENTA
            $existencia,          // 16. EXIST_G
            $existencia,          // 17. EXISTENCIA
            1,                    // 18. FACTOR_CON
            $fechaElab,           // 19. FECHAELAB
            $cveFolio,            // 20. CVE_FOLIO
            -1,                   // 21. SIGNO
            'S',                  // 22. COSTEADO
            0,                    // 23. COSTO_PROM_INI
            0,                    // 24. COSTO_PROM_FIN
            0,                    // 25. COSTO_PROM_GRAL
            'N',                  // 26. DESDE_INVE
            0,                    // 27. MOV_ENLAZADO
            $folio                // 28. DOCUMENTO = folio de la muestra
        ];
        
        $stmtInsert = sqlsrv_query($conn, $sqlInsert, $paramsInsert);
        if ($stmtInsert === false) {
            throw new Exception("Error al insertar en MINVE: " . print_r(sqlsrv_errors(), true));
        }
        
        $numMov++;
        $cantMov++;
        sqlsrv_free_stmt($stmtInsert);
        sqlsrv_free_stmt($stmtExist);
    }
    
    sqlsrv_free_stmt($stmtUltimos);
    sqlsrv_free_stmt($stmtFolio);
    
    return ['totalMovimientos' => $cantMov, 'cveFolio' => $cveFolio];
}

function actualizarTBL_CONTROL($conexionData, $claveSae, $totalMovimientos, $conn) {
    $tablaControl = "[{$conexionData['nombreBase']}].[dbo].[TBLCONTROL" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    
    // Actualizar ID_TABLA = 44 (NUM_MOV)
    $sql = "UPDATE $tablaControl SET ULT_CVE = ULT_CVE + ? WHERE ID_TABLA = 44";
    $params = [$totalMovimientos];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        throw new Exception("Error al actualizar TBLCONTROL (ID_TABLA = 44): " . print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($stmt);
    
    // Actualizar ID_TABLA = 32 (CVE_FOLIO)
    $sql = "UPDATE $tablaControl SET ULT_CVE = ULT_CVE + 1 WHERE ID_TABLA = 32";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        throw new Exception("Error al actualizar TBLCONTROL (ID_TABLA = 32): " . print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($stmt);
}

function insertarBITA($conexionData, $folio, $claveSae, $clienteClave, $conn) {
    $tablaBita = "[{$conexionData['nombreBase']}].[dbo].[BITA" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    $tablaControl = "[{$conexionData['nombreBase']}].[dbo].[TBLCONTROL" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
    
    // Obtener CVE_BITA siguiente
    $sqlUltimaBita = "SELECT ISNULL(MAX(CVE_BITA), 0) + 1 AS CVE_BITA FROM $tablaBita";
    $stmtUltimaBita = sqlsrv_query($conn, $sqlUltimaBita);
    if ($stmtUltimaBita === false) {
        throw new Exception("Error al obtener CVE_BITA: " . print_r(sqlsrv_errors(), true));
    }
    $bitaData = sqlsrv_fetch_array($stmtUltimaBita, SQLSRV_FETCH_ASSOC);
    $cveBita = $bitaData['CVE_BITA'];
    
    $observaciones = "Muestra No.[$folio]";
    $actividad = str_pad(5, 5, ' ', STR_PAD_LEFT); // Actividad para muestras (ajustar según necesidad)
    
    $sqlInsert = "INSERT INTO $tablaBita 
        (CVE_BITA, CVE_CAMPANIA, STATUS, CVE_CLIE, CVE_USUARIO, NOM_USUARIO, OBSERVACIONES, FECHAHORA, CVE_ACTIVIDAD) 
        VALUES (?, '_SAE_', 'F', ?, 1, 'ADMINISTRADOR', ?, ?, ?)";
    
    $paramsInsert = [
        $cveBita,
        $clienteClave,
        $observaciones,
        date('Y-m-d H:i:s'),
        $actividad
    ];
    
    $stmtInsert = sqlsrv_query($conn, $sqlInsert, $paramsInsert);
    if ($stmtInsert === false) {
        throw new Exception("Error al insertar en BITA: " . print_r(sqlsrv_errors(), true));
    }
    
    sqlsrv_free_stmt($stmtUltimaBita);
    sqlsrv_free_stmt($stmtInsert);
    
    return $cveBita;
}

// ==================== CASE 8: GUARDAR/ACTUALIZAR MUESTRA ====================
function guardarMuestra($conexionData, $datos, $accion) {
    global $firebaseProjectId, $firebaseApiKey;
    
    try {
        // Convertir conexión PDO a sqlsrv para compatibilidad
        $serverName = $conexionData['host'] ?? null;
        $usuario = $conexionData['usuario'] ?? null;
        $password = $conexionData['password'] ?? null;
        $nombreBase = $conexionData['nombreBase'] ?? null;
        $claveSae = $conexionData['claveSae'];

        if (!$serverName || !$usuario || !$nombreBase) {
            // Si no tenemos datos de conexión directos, usar sqlsrv desde PDO
            $connPDO = $conexionData['conn'];
            // Necesitamos crear conexión sqlsrv
            $connectionInfo = [
                "Database" => $nombreBase,
                "UID" => $usuario,
                "PWD" => $password,
                "CharacterSet" => "UTF-8",
                "TrustServerCertificate" => true
            ];
            $conn = sqlsrv_connect($serverName, $connectionInfo);
        } else {
            $connectionInfo = [
                "Database" => $nombreBase,
                "UID" => $usuario,
                "PWD" => $password,
                "CharacterSet" => "UTF-8",
                "TrustServerCertificate" => true
            ];
            $conn = sqlsrv_connect($serverName, $connectionInfo);
        }
        
        if ($conn === false) {
            throw new Exception("Error al conectar con la base de datos: " . print_r(sqlsrv_errors(), true));
        }
        
        sqlsrv_begin_transaction($conn);

        $datosObj = json_decode($datos, true);
        $clienteClave = str_pad($datosObj['clienteClave'] ?? '', 10, ' ', STR_PAD_LEFT);
        $fecha = $datosObj['fecha'] ?? date('Y-m-d');
        $fechaEntrega = $datosObj['fechaEntrega'] ?? $fecha;
        $observaciones = $datosObj['observaciones'] ?? '';
        $partidas = $datosObj['partidas'] ?? [];
        $vendedor = $datosObj['vendedor'] ?? '1';
        $envioData = $datosObj['envioData'] ?? [];
        
        // Construir nombres de tablas dinámicamente
        $tablaFactp = "[{$nombreBase}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $tablaParFactp = "[{$nombreBase}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $tablaInve = "[{$nombreBase}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $tablaMult = "[{$nombreBase}].[dbo].[MULT" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

        if ($accion === 'crear') {
            // 1. Obtener folio desde Firebase FOLIOS
            $noEmpresa = $_SESSION['empresa']['noEmpresa'];
            $folioInfo = obtenerFolioDesdeFirebase($firebaseProjectId, $firebaseApiKey, $noEmpresa);
            
            if (!$folioInfo) {
                throw new Exception('Error al obtener folio desde Firebase');
            }
            
            $folio = $folioInfo['folio'];
            $folioDocId = $folioInfo['documentId'];
            
            // 2. Preparar datos para Firebase MUESTRAS
            $folioFormateado = str_pad($folio, 10, '0', STR_PAD_LEFT);
            $folioFormateado = str_pad($folioFormateado, 20, ' ', STR_PAD_LEFT);
            
            // 3. Insertar en SAE (FACTP y PAR_FACTP)
            $cveDoc = str_pad($folio, 20, ' ', STR_PAD_LEFT);

            $sqlInsert = "
                INSERT INTO $tablaFactp (
                    TIP_DOC, CVE_DOC, CVE_CLPV, FECHA_DOC, FECHA_ENT,
                    STATUS, FOLIO, OBSERV, CAN_TOT, IMPORTE,
                    CVE_VEND, NUM_ALMA
                ) VALUES (
                    'M', ?, ?, ?, ?,
                    'E', ?, ?, 0, 0,
                    ?, '1'
                )
            ";

            $paramsInsert = [
                $cveDoc,
                $clienteClave,
                $fecha,
                $fechaEntrega,
                $folio,
                $observaciones,
                $vendedor
            ];
            
            $stmtInsert = sqlsrv_query($conn, $sqlInsert, $paramsInsert);
            if ($stmtInsert === false) {
                throw new Exception("Error al insertar encabezado: " . print_r(sqlsrv_errors(), true));
            }
            
            // 4. Insertar partidas y actualizar inventario
            $numPar = 1;
            foreach ($partidas as $partida) {
                $cveArt = str_pad($partida['CVE_ART'], 20, ' ', STR_PAD_LEFT);

                $sqlPartida = "
                    INSERT INTO $tablaParFactp (
                        CVE_DOC, NUM_PAR, CVE_ART, CANT, UNI_VENTA,
                        PREC, TOT_PARTIDA
                    ) VALUES (?, ?, ?, ?, ?, 0, 0)
                ";

                $paramsPartida = [
                    $cveDoc,
                    $numPar,
                    $cveArt,
                    $partida['CANT'],
                    $partida['UNI_VENTA'] ?? ''
                ];
                
                $stmtPartida = sqlsrv_query($conn, $sqlPartida, $paramsPartida);
                if ($stmtPartida === false) {
                    throw new Exception("Error al insertar partida: " . print_r(sqlsrv_errors(), true));
                }
                
                // Actualizar INVE (almacén general)
                $sqlUpdateInve = "UPDATE $tablaInve SET EXIST = EXIST - ? WHERE CVE_ART = ?";
                $paramsUpdate = [$partida['CANT'], $cveArt];
                $stmtUpdateInve = sqlsrv_query($conn, $sqlUpdateInve, $paramsUpdate);
                if ($stmtUpdateInve === false) {
                    throw new Exception("Error al actualizar INVE: " . print_r(sqlsrv_errors(), true));
                }
                
                // Actualizar MULT almacén 2 (de donde se sacan productos para comandas)
                $sqlUpdateMult = "UPDATE $tablaMult SET EXIST = EXIST - ? WHERE CVE_ART = ? AND CVE_ALM = '2'";
                $stmtUpdateMult = sqlsrv_query($conn, $sqlUpdateMult, $paramsUpdate);
                if ($stmtUpdateMult === false) {
                    throw new Exception("Error al actualizar MULT: " . print_r(sqlsrv_errors(), true));
                }

                $numPar++;
                sqlsrv_free_stmt($stmtPartida);
                sqlsrv_free_stmt($stmtUpdateInve);
                sqlsrv_free_stmt($stmtUpdateMult);
            }
            
            // 5. Insertar movimientos en MINVE
            $movimientos = insertarMovimientosMINVE($conexionData, $folio, $partidas, $claveSae, $clienteClave, $vendedor, $conn);
            
            // 6. Actualizar TBL_CONTROL
            actualizarTBL_CONTROL($conexionData, $claveSae, $movimientos['totalMovimientos'], $conn);
            
            // 7. Insertar en BITA
            $cveBita = insertarBITA($conexionData, $folio, $claveSae, $clienteClave, $conn);
            
            // 8. Guardar en Firebase MUESTRAS
            $fields = [
                'folio' => ['integerValue' => (int)$folio],
                'noEmpresa' => ['integerValue' => (int)$noEmpresa],
                'claveCliente' => ['stringValue' => trim($clienteClave)],
                'nombreCliente' => ['stringValue' => $datosObj['nombreCliente'] ?? ''],
                'fecha' => ['stringValue' => $fecha],
                'fechaEntrega' => ['stringValue' => $fechaEntrega],
                'observaciones' => ['stringValue' => $observaciones],
                'vendedor' => ['stringValue' => $vendedor],
                'claveSae' => ['stringValue' => $claveSae],
                'status' => ['stringValue' => 'Activa'],
                'fechaHoraElaboracion' => ['stringValue' => date('Y-m-d H:i:s')],
                'productos' => [
                    'arrayValue' => [
                        'values' => array_map(function($p) {
                            return [
                                'mapValue' => [
                                    'fields' => [
                                        'clave' => ['stringValue' => $p['CVE_ART'] ?? ''],
                                        'descripcion' => ['stringValue' => $p['descripcion'] ?? ''],
                                        'cantidad' => ['integerValue' => (int)($p['CANT'] ?? 0)],
                                        'unidad' => ['stringValue' => $p['UNI_VENTA'] ?? '']
                                    ]
                                ]
                            ];
                        }, $partidas)
                    ]
                ],
                'datosEnvio' => [
                    'mapValue' => [
                        'fields' => [
                            'nombreContacto' => ['stringValue' => $envioData['nombreContacto'] ?? ''],
                            'companiaContacto' => ['stringValue' => $envioData['compañiaContacto'] ?? ''],
                            'telefonoContacto' => ['stringValue' => $envioData['telefonoContacto'] ?? ''],
                            'correoContacto' => ['stringValue' => $envioData['correoContacto'] ?? ''],
                            'direccion1Contacto' => ['stringValue' => $envioData['direccion1Contacto'] ?? ''],
                            'direccion2Contacto' => ['stringValue' => $envioData['direccion2Contacto'] ?? ''],
                            'codigoContacto' => ['stringValue' => $envioData['codigoContacto'] ?? ''],
                            'estadoContacto' => ['stringValue' => $envioData['estadoContacto'] ?? ''],
                            'municipioContacto' => ['stringValue' => $envioData['municipioContacto'] ?? '']
                        ]
                    ]
                ]
            ];
            
            $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/MUESTRAS?key=$firebaseApiKey";
            $payload = json_encode(['fields' => $fields]);
            $options = [
                'http' => [
                    'header' => "Content-Type: application/json\r\n",
                    'method' => 'POST',
                    'content' => $payload
                ]
            ];
            $context = stream_context_create($options);
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                throw new Exception('Error al guardar muestra en Firebase');
            }
            
            // 9. Actualizar folio en Firebase FOLIOS
            $nuevoFolio = $folio + 1;
            if (!actualizarFolioFirebase($firebaseProjectId, $firebaseApiKey, $folioDocId, $nuevoFolio)) {
                throw new Exception('Error al actualizar folio en Firebase');
            }
            
            sqlsrv_commit($conn);
            sqlsrv_close($conn);
            
            echo json_encode([
                'success' => true,
                'message' => 'Muestra creada correctamente',
                'folio' => $folio
            ]);

        } elseif ($accion === 'editar') {
            // TODO: Implementar edición de muestra con Firebase y movimientos SAE
            // Por ahora solo actualización básica en SAE
            $muestraID = $datosObj['muestraID'];
            $cveDoc = str_pad($muestraID, 20, ' ', STR_PAD_LEFT);

            // Devolver inventario de partidas antiguas
            $sqlOldPartidas = "SELECT CVE_ART, CANT FROM $tablaParFactp WHERE CVE_DOC = ?";
            $paramsOld = [$cveDoc];
            $stmtOld = sqlsrv_query($conn, $sqlOldPartidas, $paramsOld);
            if ($stmtOld === false) {
                throw new Exception("Error al obtener partidas antiguas: " . print_r(sqlsrv_errors(), true));
            }
            
            $oldPartidas = [];
            while ($row = sqlsrv_fetch_array($stmtOld, SQLSRV_FETCH_ASSOC)) {
                $oldPartidas[] = $row;
            }
            sqlsrv_free_stmt($stmtOld);

            foreach ($oldPartidas as $old) {
                $cveArt = $old['CVE_ART'];
                $cant = $old['CANT'];
                
                // Devolver a INVE
                $sqlRestoreInve = "UPDATE $tablaInve SET EXIST = EXIST + ? WHERE CVE_ART = ?";
                $paramsRestore = [$cant, $cveArt];
                $stmtRestore = sqlsrv_query($conn, $sqlRestoreInve, $paramsRestore);
                if ($stmtRestore === false) {
                    throw new Exception("Error al restaurar INVE: " . print_r(sqlsrv_errors(), true));
                }
                
                // Devolver a MULT almacén 2
                $sqlRestoreMult = "UPDATE $tablaMult SET EXIST = EXIST + ? WHERE CVE_ART = ? AND CVE_ALM = '2'";
                $stmtRestoreMult = sqlsrv_query($conn, $sqlRestoreMult, $paramsRestore);
                if ($stmtRestoreMult === false) {
                    throw new Exception("Error al restaurar MULT: " . print_r(sqlsrv_errors(), true));
                }
                
                sqlsrv_free_stmt($stmtRestore);
                sqlsrv_free_stmt($stmtRestoreMult);
            }

            // Eliminar partidas antiguas
            $sqlDeletePartidas = "DELETE FROM $tablaParFactp WHERE CVE_DOC = ?";
            $stmtDelete = sqlsrv_query($conn, $sqlDeletePartidas, [$cveDoc]);
            if ($stmtDelete === false) {
                throw new Exception("Error al eliminar partidas antiguas: " . print_r(sqlsrv_errors(), true));
            }
            sqlsrv_free_stmt($stmtDelete);

            // Actualizar encabezado
            $sqlUpdate = "UPDATE $tablaFactp SET FECHA_ENT = ?, OBSERV = ? WHERE CVE_DOC = ?";
            $paramsUpdate = [$fechaEntrega, $observaciones, $cveDoc];
            $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);
            if ($stmtUpdate === false) {
                throw new Exception("Error al actualizar encabezado: " . print_r(sqlsrv_errors(), true));
            }
            sqlsrv_free_stmt($stmtUpdate);

            // Insertar nuevas partidas
            $numPar = 1;
            foreach ($partidas as $partida) {
                $cveArt = str_pad($partida['CVE_ART'], 20, ' ', STR_PAD_LEFT);

                $sqlPartida = "
                    INSERT INTO $tablaParFactp (
                        CVE_DOC, NUM_PAR, CVE_ART, CANT, UNI_VENTA,
                        PREC, TOT_PARTIDA
                    ) VALUES (?, ?, ?, ?, ?, 0, 0)
                ";

                $paramsPartida = [
                    $cveDoc,
                    $numPar,
                    $cveArt,
                    $partida['CANT'],
                    $partida['UNI_VENTA'] ?? ''
                ];
                
                $stmtPartida = sqlsrv_query($conn, $sqlPartida, $paramsPartida);
                if ($stmtPartida === false) {
                    throw new Exception("Error al insertar partida: " . print_r(sqlsrv_errors(), true));
                }

                // Restar del inventario
                $paramsUpdateInv = [$partida['CANT'], $cveArt];
                $sqlUpdateInve = "UPDATE $tablaInve SET EXIST = EXIST - ? WHERE CVE_ART = ?";
                $stmtUpdateInve = sqlsrv_query($conn, $sqlUpdateInve, $paramsUpdateInv);
                if ($stmtUpdateInve === false) {
                    throw new Exception("Error al actualizar INVE: " . print_r(sqlsrv_errors(), true));
                }

                $sqlUpdateMult = "UPDATE $tablaMult SET EXIST = EXIST - ? WHERE CVE_ART = ? AND CVE_ALM = '2'";
                $stmtUpdateMult = sqlsrv_query($conn, $sqlUpdateMult, $paramsUpdateInv);
                if ($stmtUpdateMult === false) {
                    throw new Exception("Error al actualizar MULT: " . print_r(sqlsrv_errors(), true));
                }

                $numPar++;
                sqlsrv_free_stmt($stmtPartida);
                sqlsrv_free_stmt($stmtUpdateInve);
                sqlsrv_free_stmt($stmtUpdateMult);
            }

            sqlsrv_commit($conn);
            sqlsrv_close($conn);
            
            echo json_encode(['success' => true, 'message' => 'Muestra actualizada correctamente']);
        }
    } catch (Exception $e) {
        if (isset($conn) && $conn !== false) {
            sqlsrv_rollback($conn);
            sqlsrv_close($conn);
        }
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// ==================== CASE 10: CANCELAR MUESTRA ====================
function cancelarMuestra($muestraID, $conexionData) {
    try {
        $claveSae = $conexionData['claveSae'];
        $nombreBase = $conexionData['nombreBase'];
        $serverName = $conexionData['host'];
        $usuario = $conexionData['usuario'];
        $password = $conexionData['password'];
        
        $connectionInfo = [
            "Database" => $nombreBase,
            "UID" => $usuario,
            "PWD" => $password,
            "CharacterSet" => "UTF-8",
            "TrustServerCertificate" => true
        ];
        $conn = sqlsrv_connect($serverName, $connectionInfo);
        
        if ($conn === false) {
            throw new Exception("Error al conectar con la base de datos: " . print_r(sqlsrv_errors(), true));
        }

        sqlsrv_begin_transaction($conn);

        $cveDoc = str_pad($muestraID, 20, ' ', STR_PAD_LEFT);
        
        // Construir nombres de tablas dinámicamente
        $tablaParFactp = "[{$nombreBase}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $tablaFactp = "[{$nombreBase}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $tablaInve = "[{$nombreBase}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $tablaMult = "[{$nombreBase}].[dbo].[MULT" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

        // Devolver inventario
        $sqlPartidas = "SELECT CVE_ART, CANT FROM $tablaParFactp WHERE CVE_DOC = ?";
        $paramsPartidas = [$cveDoc];
        $stmtPartidas = sqlsrv_query($conn, $sqlPartidas, $paramsPartidas);
        if ($stmtPartidas === false) {
            throw new Exception("Error al obtener partidas: " . print_r(sqlsrv_errors(), true));
        }
        
        $partidas = [];
        while ($row = sqlsrv_fetch_array($stmtPartidas, SQLSRV_FETCH_ASSOC)) {
            $partidas[] = $row;
        }
        sqlsrv_free_stmt($stmtPartidas);

        foreach ($partidas as $partida) {
            $paramsRestore = [$partida['CANT'], $partida['CVE_ART']];
            
            $sqlRestoreInve = "UPDATE $tablaInve SET EXIST = EXIST + ? WHERE CVE_ART = ?";
            $stmtRestore = sqlsrv_query($conn, $sqlRestoreInve, $paramsRestore);
            if ($stmtRestore === false) {
                throw new Exception("Error al restaurar INVE: " . print_r(sqlsrv_errors(), true));
            }

            $sqlRestoreMult = "UPDATE $tablaMult SET EXIST = EXIST + ? WHERE CVE_ART = ? AND CVE_ALM = '2'";
            $stmtRestoreMult = sqlsrv_query($conn, $sqlRestoreMult, $paramsRestore);
            if ($stmtRestoreMult === false) {
                throw new Exception("Error al restaurar MULT: " . print_r(sqlsrv_errors(), true));
            }
            
            sqlsrv_free_stmt($stmtRestore);
            sqlsrv_free_stmt($stmtRestoreMult);
        }

        // Cancelar muestra
        $sqlCancel = "UPDATE $tablaFactp SET STATUS = 'C' WHERE CVE_DOC = ?";
        $stmtCancel = sqlsrv_query($conn, $sqlCancel, [$cveDoc]);
        if ($stmtCancel === false) {
            throw new Exception("Error al cancelar muestra: " . print_r(sqlsrv_errors(), true));
        }
        sqlsrv_free_stmt($stmtCancel);

        sqlsrv_commit($conn);
        sqlsrv_close($conn);
        
        echo json_encode(['success' => true, 'message' => 'Muestra cancelada correctamente']);
    } catch (Exception $e) {
        if (isset($conn) && $conn !== false) {
            sqlsrv_rollback($conn);
            sqlsrv_close($conn);
        }
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// ==================== CASE 26: GENERAR PDF ====================
function generarPDFMuestra($muestraID, $conexionData) {
    try {
        $conn = $conexionData['conn'];
        $claveSae = $conexionData['claveSae'];

        $tablaFactp = "[{$conexionData['nombreBase']}].[dbo].[FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $tablaClie = "[{$conexionData['nombreBase']}].[dbo].[CLIE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $tablaParFactp = "[{$conexionData['nombreBase']}].[dbo].[PAR_FACTP" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $tablaInve = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";

        // Obtener datos de la muestra
        $sql = "
            SELECT
                f.CVE_DOC, f.FOLIO,
                c.NOMBRE as CLIENTE,
                CONVERT(varchar, f.FECHA_DOC, 103) as FECHA,
                f.OBSERV
            FROM $tablaFactp f
            LEFT JOIN $tablaClie c ON f.CVE_CLPV = c.CLAVE
            WHERE f.CVE_DOC = ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$muestraID]);
        $muestra = $stmt->fetch(PDO::FETCH_ASSOC);

        // Obtener partidas
        $sqlPartidas = "
            SELECT
                p.CVE_ART, i.DESCR, p.CANT, p.UNI_VENTA
            FROM $tablaParFactp p
            LEFT JOIN $tablaInve i ON p.CVE_ART = i.CVE_ART
            WHERE p.CVE_DOC = ?
            ORDER BY p.NUM_PAR
        ";

        $stmtPartidas = $conn->prepare($sqlPartidas);
        $stmtPartidas->execute([$muestraID]);
        $partidas = $stmtPartidas->fetchAll(PDO::FETCH_ASSOC);

        // Generar PDF simple (aquí deberías usar una librería como TCPDF o FPDF)
        // Por ahora retornamos los datos en JSON
        echo json_encode([
            'success' => true,
            'data' => [
                'muestra' => $muestra,
                'partidas' => $partidas
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// ==================== CASE 22: OBTENER ESTADOS ====================
function obtenerEstados() {
    $filePath = "../../Complementos/CAT_ESTADOS.xml";
    if (!file_exists($filePath)) {
        echo json_encode(['success' => false, 'message' => "El archivo no existe en la ruta: $filePath"], JSON_UNESCAPED_UNICODE);
        return;
    }

    $xmlContent = file_get_contents($filePath);
    if ($xmlContent === false) {
        echo json_encode(['success' => false, 'message' => "Error al leer el archivo XML en $filePath"], JSON_UNESCAPED_UNICODE);
        return;
    }

    try {
        $estados = new SimpleXMLElement($xmlContent);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        return;
    }

    $data = [];
    foreach ($estados->row as $row) {
        if ((string)$row['Pais'] === 'MEX') {
            $data[] = [
                'Clave' => (string)$row['Clave'],
                'Pais' => (string)$row['Pais'],
                'Descripcion' => (string)$row['Descripcion']
            ];
        }
    }

    // Ordenar por Descripción alfabéticamente
    usort($data, function ($a, $b) {
        return strcmp($a['Descripcion'] ?? '', $b['Descripcion'] ?? '');
    });

    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
}

// ==================== CASE 23: OBTENER MUNICIPIOS ====================
function obtenerMunicipios($estadoSeleccionado) {
    // Intentar ambos nombres posibles del archivo
    $filePath = "../../Complementos/CAT_MUNICIPIOS.xml";
    if (!file_exists($filePath)) {
        $filePath = "../../Complementos/CAT_MUNICIPIO.xml";
    }
    if (!file_exists($filePath)) {
        echo json_encode(['success' => false, 'message' => "El archivo no existe en la ruta: $filePath"], JSON_UNESCAPED_UNICODE);
        return;
    }

    $xmlContent = file_get_contents($filePath);
    if ($xmlContent === false) {
        echo json_encode(['success' => false, 'message' => "Error al leer el archivo XML en $filePath"], JSON_UNESCAPED_UNICODE);
        return;
    }

    try {
        $municipios = new SimpleXMLElement($xmlContent);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        return;
    }

    $data = [];
    foreach ($municipios->row as $row) {
        if ((string)$row['Estado'] === $estadoSeleccionado) {
            $data[] = [
                'Clave' => (string)$row['Clave'],
                'Estado' => (string)$row['Estado'],
                'Descripcion' => (string)$row['Descripcion']
            ];
        }
    }

    // Ordenar por Descripción alfabéticamente
    usort($data, function ($a, $b) {
        return strcmp($a['Descripcion'] ?? '', $b['Descripcion'] ?? '');
    });

    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
}

// ==================== CASE 33: OBTENER VENDEDORES ====================
function obtenerVendedores($conexionData) {
    try {
        $conn = $conexionData['conn'];
        $claveSae = $conexionData['claveSae'];

        $tablaVend = "[{$conexionData['nombreBase']}].[dbo].[VEND" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
        $sql = "SELECT CVE_VEND, NOMBRE FROM $tablaVend WHERE STATUS = 'ALTA' ORDER BY NOMBRE";
        $stmt = $conn->query($sql);
        $vendedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $vendedores]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// ==================== SWITCH PRINCIPAL ====================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['numFuncion'])) {
    $funcion = $_POST['numFuncion'];
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['numFuncion'])) {
    $funcion = $_GET['numFuncion'];
} else {
    echo json_encode(['success' => false, 'message' => 'Error al realizar la petición.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Casos que NO requieren conexión a SAE (usa archivos XML)
if ($funcion == 22) {
    // Obtener estados desde XML
    obtenerEstados();
    exit;
}

if ($funcion == 23) {
    // Obtener municipios desde XML
    $estadoSeleccionado = $_POST['estado'] ?? null;
    if ($estadoSeleccionado) {
        obtenerMunicipios($estadoSeleccionado);
    } else {
        echo json_encode(['success' => false, 'message' => 'Estado no proporcionado'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// Para todos los demás casos, se requiere conexión
// Obtener conexión
if (!isset($_SESSION['empresa']['noEmpresa'])) {
    echo json_encode(['success' => false, 'message' => 'No se ha definido la empresa en la sesión'], JSON_UNESCAPED_UNICODE);
    exit;
}

$noEmpresa = isset($_SESSION['empresa']['noEmpresa']) ? (int)$_SESSION['empresa']['noEmpresa'] : null;
$claveSae = isset($_SESSION['empresa']['claveSae']) ? $_SESSION['empresa']['claveSae'] : null;

// Las variables de Firebase están definidas en firebase.php que se cargó arriba
// Verificar que estén disponibles
if (!isset($firebaseProjectId) || !isset($firebaseApiKey)) {
    echo json_encode(['success' => false, 'message' => 'Error: Variables de Firebase no están disponibles'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$noEmpresa || !$claveSae) {
    echo json_encode(['success' => false, 'message' => 'Error: Datos de empresa no disponibles en sesión'], JSON_UNESCAPED_UNICODE);
    exit;
}

$conexionResult = obtenerConexion($noEmpresa, $firebaseProjectId, $firebaseApiKey, $claveSae);

if (!$conexionResult['success']) {
    // Usar JSON_UNESCAPED_UNICODE para mantener caracteres especiales
    echo json_encode($conexionResult, JSON_UNESCAPED_UNICODE);
    exit;
}

$conexionData = $conexionResult['data'];

// Switch de funciones
switch ($funcion) {
    case 1: // Mostrar muestras
        $filtroFecha = $_POST['filtroFecha'] ?? 'Hoy';
        $estadoMuestra = $_POST['estadoMuestra'] ?? 'Activas';
        $filtroVendedor = $_POST['filtroVendedor'] ?? null;
        $searchTerm = $_POST['searchTerm'] ?? '';
        $offset = $_POST['offset'] ?? 0;
        $limit = $_POST['limit'] ?? 10;
        mostrarMuestras($conexionData, $filtroFecha, $estadoMuestra, $filtroVendedor, $searchTerm, $offset, $limit);
        break;

    case 2: // Mostrar muestra específica
        $muestraID = $_POST['muestraID'] ?? null;
        if ($muestraID) {
            mostrarMuestraEspecifica($muestraID, $conexionData);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID de muestra no proporcionado']);
        }
        break;

    case 3: // Obtener folio siguiente o partidas
        $accion = $_POST['accion'] ?? null;
        if ($accion === 'obtenerFolioSiguiente') {
            obtenerFolioSiguiente($conexionData);
        } elseif ($accion === 'obtenerPartidas') {
            $muestraID = $_POST['muestraID'] ?? null;
            if ($muestraID) {
                obtenerPartidasMuestra($muestraID, $conexionData);
            }
        }
        break;

    case 4: // Buscar cliente
        $termino = $_POST['termino'] ?? '';
        buscarCliente($termino, $conexionData);
        break;

    case 5: // Buscar productos (puede venir de GET o POST)
        $termino = $_POST['termino'] ?? $_GET['termino'] ?? '';
        // Si no hay término, devolver todos los productos (primeros 100)
        if (empty($termino)) {
            $conn = $conexionData['conn'];
            $claveSae = $conexionData['claveSae'];
            $tablaInve = "[{$conexionData['nombreBase']}].[dbo].[INVE" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
            $tablaMult = "[{$conexionData['nombreBase']}].[dbo].[MULT" . str_pad($claveSae, 2, "0", STR_PAD_LEFT) . "]";
            $sql = "SELECT TOP 100 i.CVE_ART, i.DESCR, i.UNI_VENTA, ISNULL(m.EXIST, 0) as EXIST
                    FROM $tablaInve i
                    LEFT JOIN $tablaMult m ON i.CVE_ART = m.CVE_ART AND m.CVE_ALM = '2'
                    WHERE i.STATUS = 'ALTA'
                    ORDER BY i.DESCR";
            try {
                $stmt = $conn->query($sql);
                $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'productos' => $productos], JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
        } else {
        buscarProductos($termino, $conexionData);
        }
        break;

    case 8: // Guardar/actualizar muestra
        $datos = $_POST['datos'] ?? null;
        $accion = $_POST['accion'] ?? 'crear';
        if ($datos) {
            // El frontend envía todos los datos en un solo JSON
            guardarMuestra($conexionData, $datos, $accion);
        } else {
            echo json_encode(['success' => false, 'message' => 'Datos no proporcionados']);
        }
        break;

    case 10: // Cancelar muestra
        $muestraID = $_POST['muestraID'] ?? null;
        if ($muestraID) {
            cancelarMuestra($muestraID, $conexionData);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID de muestra no proporcionado']);
        }
        break;

    case 26: // Generar PDF
        $muestraID = $_GET['muestraID'] ?? null;
        if ($muestraID) {
            generarPDFMuestra($muestraID, $conexionData);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID de muestra no proporcionado']);
        }
        break;

    case 33: // Obtener vendedores
        obtenerVendedores($conexionData);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Función no reconocida'], JSON_UNESCAPED_UNICODE);
        break;
}
?>
