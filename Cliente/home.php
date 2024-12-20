<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" >
    <title>Home</title>
</head>
<body>
    <div id="divContenedor">
        <form id="form">
            
            <h4>Inicio </h4>
            <div id="divUsuario">
                <label for="text">Usuario</label>
                <input type="text" name="usuario" id="usuario">
                <p class="error" id="error_correo">Ingresa Nombre Usuario</p>
            </div>
            <div id="divPassword">
                <label for="text">Contraseña</label>
                <input type="password" name="password" id="password">
                <p class="error" id="error_pass">Debes ingresar una contraseña</p>
            </div>
            <div id="divButton">
                <input type="submit" id="buttonSesion" name="buttonSesion">
            </div>

            <div id="divRegistar">
            </div>
        </form>

    </div>
    <script src="JS/java.js"></script>
</form>

</body>
</html>