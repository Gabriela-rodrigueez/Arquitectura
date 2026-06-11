<?php
require 'conexion.php'; 
$mensaje = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $dni = trim($_POST['dni']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $rango_edad = $_POST['rango_edad'];
    $password = $_POST['contrasena'];

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    try {
        // CONSULTA CORREGIDA: Apunta exactamente a las columnas de USUARIO
        $sql = "INSERT INTO USUARIO (DNI, Nombre, Apellido, Email, Telefono, Rango_edad, Contrasenia) 
                VALUES (:dni, :nombre, :apellido, :email, :tel, :rango, :pass)";
        
        $stmt = $conexion->prepare($sql);
        
        $stmt->bindParam(':dni', $dni);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':apellido', $apellido);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':tel', $telefono);
        $stmt->bindParam(':rango', $rango_edad);
        $stmt->bindParam(':pass', $password_hash);

        if($stmt->execute()) {
            $mensaje = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4'>✅ Registro exitoso. ¡Inicia sesión!</div>";
        }
    } catch(PDOException $e) {
        if($e->getCode() == 23000) {
            $mensaje = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4'>❌ El DNI o Correo electrónico ya se encuentran registrados.</div>";
        } else {
            $mensaje = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4'>❌ Error al registrar: " . $e->getMessage() . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Registro - SaludMza</title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
</head>
<body class="bg-blue-50 text-slate-800 font-sans min-h-screen flex flex-col justify-center py-10 px-4">

    <main class="w-full max-w-md mx-auto bg-white rounded-xl shadow-md border border-gray-100 p-6 sm:p-8">
        
        <div class="flex flex-col items-center mb-6">
            <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mb-2">
                <span class="material-symbols-outlined text-4xl">how_to_reg</span>
            </div>
            <h1 class="text-2xl font-bold text-blue-700 text-center">Crear Cuenta</h1>
        </div>

        <?php echo $mensaje; ?>

        <form action="registro.php" method="POST" class="flex flex-col gap-4">
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase">Nombre</label>
                    <input name="nombre" class="w-full rounded-lg border-gray-200 mt-1" type="text" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase">Apellido</label>
                    <input name="apellido" class="w-full rounded-lg border-gray-200 mt-1" type="text" required>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase">DNI</label>
                <input name="dni" class="w-full rounded-lg border-gray-200 mt-1" type="text" required>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase">Correo Electrónico</label>
                <input name="email" class="w-full rounded-lg border-gray-200 mt-1" type="email" required>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase">Teléfono</label>
                <input name="telefono" class="w-full rounded-lg border-gray-200 mt-1" type="text">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase">Rango de Edad</label>
                <select name="rango_edad" class="w-full rounded-lg border-gray-200 mt-1">
                    <option value="Adulto">Adulto</option>
                    <option value="Adulto Mayor">Adulto Mayor</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase">Contraseña</label>
                <input name="contrasena" class="w-full rounded-lg border-gray-200 mt-1" type="password" required>
            </div>

            <button type="submit" class="w-full h-12 mt-4 bg-blue-600 text-white rounded-full font-bold shadow-sm hover:bg-blue-700 transition-colors">
                Crear Cuenta
            </button>
            
            <p class="text-xs text-center text-gray-500 mt-2">¿Ya tienes cuenta? <a href="login.php" class="text-blue-600 underline">Inicia Sesión</a></p>
        </form>
    </main>
</body>
</html>