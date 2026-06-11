<?php
session_start();
if (!isset($_SESSION['usuario_conectado'])) { die("Acceso denegado."); }
require 'conexion.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_estudio = $_GET['id'];
    $id_usuario = $_SESSION['id_usuario'];

    try {
        // CONSULTA CORREGIDA: Cambiada a ESTUDIO_MEDICO y Url_pdf
        $sql = "SELECT Url_pdf, Tipo_estudio FROM ESTUDIO_MEDICO WHERE id_estudio = :id AND id_paciente = :usuario";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':id', $id_estudio, PDO::PARAM_INT);
        $stmt->bindParam(':usuario', $id_usuario, PDO::PARAM_INT);
        $stmt->execute();
        
        $estudio = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($estudio) {
            $ruta_archivo = "uploads/pdf/" . $estudio['Url_pdf'];

            if (file_exists($ruta_archivo)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . str_replace(' ', '_', $estudio['Tipo_estudio']) . '.pdf"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($ruta_archivo));
                
                ob_clean();
                flush();
                readfile($ruta_archivo);
                exit;
            } else {
                echo "El archivo PDF físico no se encuentra en el servidor.";
            }
        } else {
            echo "Estudio no encontrado o no tienes permisos.";
        }
    } catch(PDOException $e) {
        echo "Error en el sistema de descargas.";
    }
} else {
    echo "Petición inválida.";
}
?>