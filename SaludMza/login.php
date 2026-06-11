<?php
session_start();
// Si ya está logueado, lo mandamos al inicio
if (isset($_SESSION['usuario_conectado'])) {
    header("Location: inicio.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Iniciar Sesión - SaludMza</title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
</head>
<body class="bg-blue-50 text-slate-800 font-sans min-h-screen flex flex-col justify-center py-10 px-4">

    <main class="w-full max-w-md mx-auto bg-white rounded-xl shadow-md border border-gray-100 p-6 sm:p-8">
        
        <div class="flex flex-col items-center mb-6">
            <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mb-2">
                <span class="material-symbols-outlined text-4xl">login</span>
            </div>
            <h1 class="text-2xl font-bold text-blue-700 text-center">¡Hola de nuevo!</h1>
            <p class="text-sm text-gray-500 text-center mt-1">Ingresa a tu portal de salud provincial</p>
        </div>

        <?php
        if (isset($_SESSION['error_login'])) {
            echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 text-sm'>" . $_SESSION['error_login'] . "</div>";
            unset($_SESSION['error_login']); // Limpiamos el error
        }
        ?>

        <form action="validar_login.php" method="POST" class="flex flex-col gap-4">
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase">DNI o Documento</label>
                <input name="dni" class="w-full rounded-lg border-gray-200 mt-1 focus:ring-blue-500 focus:border-blue-500" type="text" required placeholder="Ej: 40123456">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase">Contraseña</label>
                <input name="contrasena" class="w-full rounded-lg border-gray-200 mt-1 focus:ring-blue-500 focus:border-blue-500" type="password" required placeholder="••••••••">
            </div>

            <button type="submit" class="w-full h-12 mt-4 bg-blue-600 text-white rounded-full font-bold shadow-sm hover:bg-blue-700 transition-colors">
                Iniciar Sesión
            </button>
            
            <p class="text-xs text-center text-gray-500 mt-4">¿No tienes cuenta provincial? <a href="registro.php" class="text-blue-600 underline font-medium">Regístrate aquí</a></p>
        </form>
    </main>
</body>
</html>