<?php
session_start();
require '../Servidor/PHP/firebase.php'; // Archivo de configuración para Firebase

if (isset($_SESSION['usuario'])) {
    if ($_SESSION['usuario']['tipoUsuario'] == 'CLIENTE') {
        header('Location:Menu.php');
        exit();
    }
    $nombreUsuario = $_SESSION['usuario']["nombre"];
    $tipoUsuario = $_SESSION['usuario']["tipoUsuario"];
    if ($_SESSION['usuario']['tipoUsuario'] == 'ADMIISTRADOR') {
        header('Location:Dashboard.php');
        exit();
    }

    $mostrarModal = isset($_SESSION['empresa']) ? false : true;

    if (isset($_SESSION['empresa'])) {
        $empresa = $_SESSION['empresa']['razonSocial'];
        $idEmpresa = $_SESSION['empresa']['id'];
        $noEmpresa = $_SESSION['empresa']['noEmpresa'];
        $claveVendedor = $_SESSION['empresa']['claveVendedor'];
    }
} else {
    header('Location:../index.php');
    exit();
}

// Conectar a Firebase y obtener mensajes desde Firestore
$url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/BITACORA?key=$firebaseApiKey";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "Content-Type: application/json\r\n"
    ]
]);

$response = @file_get_contents($url, false, $context);

if ($response === false) {
    $mensajes = [];
    $errorMsg = "No se pudo conectar a la base de datos. Verifica la URL o las reglas de seguridad de Firebase.";
} else {
    $data = json_decode($response, true);
    $mensajes = [];
    if (isset($data['documents'])) {
        foreach ($data['documents'] as $document) {
            $fields = $document['fields'];
            $mensajes[] = [
                'id' => basename($document['name']),
                'titulo' => $fields['titulo']['stringValue'],
                'mensaje' => $fields['mensaje']['stringValue'],
                'fecha' => $fields['fecha']['stringValue'],
                'estado' => $fields['estado']['stringValue'],
                'aQuienVaDirigido' => $fields['aQuienVaDirigido']['stringValue']
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootsstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <!-- My CSS -->
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/selec.css">

    <title>AdminHub</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="hero_area">
        <!-- SIDEBAR -->
        <?php include 'sidebar.php'; ?>
        <!-- CONTENT -->
        <section id="content">
            <?php include 'navbar.php'; ?>
            <!-- MAIN -->
            <main class="text-center">
                <!-- CONTENT -->
                <div class="container mt-5">
                    <h1 class="text-center">Mensajes</h1>
                    <?php if (isset($errorMsg)): ?>
                        <div class="alert alert-danger text-center"><?php echo $errorMsg; ?></div>
                    <?php else: ?>
                        <table class="table table-bordered table-striped mt-3">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Mensaje</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Dirigido a</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($mensajes)): ?>
                                    <?php foreach ($mensajes as $mensaje): ?>
                                        <?php
                                        $mostrarMensaje = $mensaje['aQuienVaDirigido'] === 'TODOS' || 
                                                          $mensaje['aQuienVaDirigido'] === $tipoUsuario;
                                        ?>
                                        <?php if ($mostrarMensaje): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($mensaje['titulo']); ?></td>
                                                <td><?php echo htmlspecialchars($mensaje['mensaje']); ?></td>
                                                <td><?php echo htmlspecialchars($mensaje['fecha']); ?></td>
                                                <td><?php echo htmlspecialchars($mensaje['estado']); ?></td>
                                                <td><?php echo htmlspecialchars($mensaje['aQuienVaDirigido']); ?></td>
                                                <td>
                                                    <?php if ($mensaje['estado'] === 'Pendiente'): ?>
                                                        <button class="btn btn-success btn-sm" onclick="marcarAtendido('<?php echo $mensaje['id']; ?>')">Marcar como Atendido</button>
                                                    <?php else: ?>
                                                        <span class="text-muted">Atendido</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No hay mensajes disponibles.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </main>
        </section>
    </div>
    <script>
        function marcarAtendido(id) {
            const url = `https://firestore.googleapis.com/v1/projects/<?php echo $firebaseProjectId; ?>/databases/(default)/documents/BITACORA/${id}?key=<?php echo $firebaseApiKey; ?>`;
            const data = { fields: { estado: { stringValue: "Atendido" } } };

            fetch(url, {
                method: "PATCH",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(data),
            })
            .then(response => {
                if (response.ok) {
                    alert("Mensaje marcado como atendido.");
                    location.reload();
                } else {
                    alert("Error al actualizar el mensaje.");
                }
            })
            .catch(error => console.error("Error:", error));
        }
    </script>
</body>

</html>
