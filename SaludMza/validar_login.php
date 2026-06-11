<?php
session_start();
require 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dni = trim($_POST['dni']);
    $contrasena = trim($_POST['contrasena']);

    try {
        // CONSULTA CORREGIDA: Busca en la tabla USUARIO real
        $sql = "SELECT id_usuario, Nombre, Apellido, Contrasenia FROM USUARIO WHERE DNI = :dni";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':dni', $dni, PDO::PARAM_STR);
        $stmt->execute();
        
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verificamos si existe el usuario y si coincide el hash de la contraseña
        if ($usuario && password_verify($contrasena, $usuario['Contrasenia'])) {
            $_SESSION['usuario_conectado'] = true;
            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['nombre_usuario'] = $usuario['Nombre'] . " " . $usuario['Apellido'];
            
            header("Location: inicio.php");
            exit();
        } else {
            $_SESSION['error_login'] = "DNI o contraseña incorrectos.";
            header("Location: login.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error_login'] = "Error del sistema de autenticación.";
        header("Location: login.php");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>