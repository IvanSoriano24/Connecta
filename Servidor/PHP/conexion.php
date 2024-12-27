<?php

function login($funcion){
    try{
        $tipUsuario = '';
        session_start();

        $firebaseApiKey = "AIzaSyCh8BFeIi4JcAAe-aW8Z2odIqdytw-wnDA";
        $firebaseProjectId = "mdconnecta-4aeb4";

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $usuario = $_POST['usuario'];
            $password = $_POST['password']; 

            // URL del API REST de Firebase Firestore
            $url = "https://firestore.googleapis.com/v1/projects/$firebaseProjectId/databases/(default)/documents/USUARIOS?key=$firebaseApiKey";
            // Realizar la solicitud GET para obtener los usuarios
            $response = file_get_contents($url);

            if ($response === false) {
                die("Error al conectarse con Firebase.");
            }

            // Decodificar la respuesta JSON
            $data = json_decode($response, true);
            // Validar las credenciales
            $usuarioValido = false;

            foreach ($data['documents'] as $document) {
                $fields = $document['fields'];
                $usuarioFirebase = $fields['usuario']['stringValue'];
                $passwordFirebase = $fields['password']['stringValue'];

                if ($usuarioFirebase === $usuario && $passwordFirebase === $password) {
                    // Si las credenciales coinciden, guardar datos en sesión
                    
                    $_SESSION['usuario'] = [
                        'id' => $document['name'], // ID del documento  //0
                        'apellido' => $fields['apellido']['stringValue'],  //0
                        'correo' => $fields['correo']['stringValue'],  //1
                        'descripcionUsuario' => $fields['descripcionUsuario']['stringValue'],  //2
                        'nombre' => $fields['nombre']['stringValue'],  //3
                        'password' => $passwordFirebase,  //4
                        'telefono' => $fields['telefono']['stringValue'],  //5
                        'tipoUsuario' => $fields['tipoUsuario']['stringValue'],  //6
                        'usuario' => $usuarioFirebase  //7
                    ];
                    $tipUsuario = $_SESSION['usuario']['tipoUsuario'];
                    $usuarioValido = true;
                    break;
                }
            }

            // Redirigir según el resultado de la validación
            if ($usuarioValido) {
                //$tipUsuario = 'CLIENTE';
                if($tipUsuario == 'CLIENTE'){
                    print_r("ola");
                    header("Location: ../../Cliente/infoEmpresa.php");
                    exit();
                }
                header("Location: ../../Cliente/menu.php"); // Redirigir al dashboard
                exit();
            } else {
                echo "Correo o contraseña incorrectos.";
            }
        }
    }catch (Exception $e) {
        echo $e->getMessage(); //En caso de fallo se muestra el error
    }
}
function cerrarSesion() {
    session_start();
    session_unset();
    session_destroy();
    echo "Sesión Finalizada";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $funcion = $_POST["numFuncion"] ?? null;
    switch ($funcion) {
        case 1:
             login($funcion); // Ejemplo, no incluido aquí
            break;
        case 2:
            cerrarSesion();
            break;
        default:
            echo "Función no válida.";
            break;
    }
} else {
    echo "Método no permitido.";
}

$funcion = $_POST["numFuncion"];
switch ($funcion) {
    case 1:
        login($funcion);
        break;
    case 2:
        cerrarSesion();
        break;
}
?>