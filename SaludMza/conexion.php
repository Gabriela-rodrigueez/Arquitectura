<?php
// Configuración de conexión a tu XAMPP local
$servidor = "localhost";
$usuario = "root";
$password = "root"; 
$base_datos = "saludmza_db";

try {
    // Conectamos directamente a la base de datos real
    $conexion = new PDO("mysql:host=$servidor;dbname=$base_datos;charset=utf8mb4", $usuario, $password);
    // Configuramos los errores para que levante excepciones en PHP si algo falla en SQL
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Opcional: Desactivar emulación de sentencias preparadas para mayor seguridad real
    $conexion->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

} catch(PDOException $e) {
    die("❌ Error crítico de conexión: " . $e->getMessage());
}
?>