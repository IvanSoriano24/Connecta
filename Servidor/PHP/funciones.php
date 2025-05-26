<?php
spl_autoload_register(function (string $class) {
    $prefix   = 'PhpCfdi\\Credentials\\';
    // Apunta al directorio donde están los .php de la librería
     $baseDir  = __DIR__ . '/../credentials-main/src/';

    // Si la clase no empieza con nuestro namespace, saltamos
    if (0 !== strpos($class, $prefix)) {
        return;
    }

    // Parte tras el namespace, e.g. "Credential"
    $relativeClass = substr($class, strlen($prefix));

    // Montamos la ruta al archivo
    $file = $baseDir
          . str_replace('\\', '/', $relativeClass)
          . '.php';

    if (is_file($file)) {
        require $file;
    }
});
?>