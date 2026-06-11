<?php
session_start();

// Si el usuario ya inició sesión, lo mandamos directo al Dashboard (inicio.php)
if (isset($_SESSION['usuario_conectado']) && $_SESSION['usuario_conectado'] === true) {
    header("Location: inicio.php");
    exit();
} else {
    // Si no ha iniciado sesión, lo mandamos a la pantalla de Login
    header("Location: login.php");
    exit();
}
?>