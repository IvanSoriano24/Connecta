<?php
session_start();
if (!isset($_GET['cveArt'])) {
    echo "Clave del artículo no proporcionada.";
    exit();
}

$cveArt = $_GET['cveArt'];

// Llamada a tu backend para obtener los detalles del producto
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/Servidor/PHP/producto.php?numFuncion=1&cveArt=" . urlencode($cveArt));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

// Validar la respuesta
if (!$data || !isset($data['success']) || !$data['success']) {
    echo "No se pudo cargar la información del producto.";
    exit();
}

// Validar producto e imágenes
$producto = $data['producto'] ?? [];
$imagenes = $data['imagenes'] ?? [];

// Si el producto está vacío
if (empty($producto)) {
    echo "El producto no está disponible.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Producto</title>
    <link rel="stylesheet" href="CSS/articulos.css">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1><?php echo htmlspecialchars($producto['DESCR']); ?></h1>
        <p><strong>Existencia:</strong> <?php echo $producto['EXIST']; ?></p>
        <p><strong>Línea del Producto:</strong> <?php echo htmlspecialchars($producto['LIN_PROD']); ?></p>
        <p><strong>Unidad de Medida:</strong> <?php echo htmlspecialchars($producto['UNI_MED']); ?></p>

        <div class="row">
            <!-- Mostrar la primera imagen principal -->
            <div class="col-md-6">
                <img src="<?php echo htmlspecialchars($producto['IMAGEN_ML'] ?? 'https://via.placeholder.com/150'); ?>" class="img-fluid" alt="Imagen del Producto">
            </div>

            <!-- Mostrar todas las imágenes adicionales -->
            <div class="col-md-6">
                <h4>Imágenes Adicionales</h4>
                <?php if (count($imagenes) > 0): ?>
                    <?php foreach ($imagenes as $imagen): ?>
                        <img src="<?php echo htmlspecialchars($imagen); ?>" class="img-thumbnail mb-2" alt="Imagen del Producto">
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No hay imágenes adicionales disponibles.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
