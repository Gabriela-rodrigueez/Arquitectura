<?php
session_start();
if (!isset($_SESSION['usuario_conectado'])) { header("Location: login.php"); exit(); }
require 'conexion.php';

$id_usuario = $_SESSION['id_usuario'];
$estudios = [];

try {
    // CONSULTA CORREGIDA: Cambiada a ESTUDIO_MEDICO vinculando con MEDICO emisor
    $sql = "SELECT e.id_estudio, e.Tipo_estudio, e.Fecha_emision, e.Url_pdf, m.Nombre AS med_nom, m.Apellido AS med_ape
            FROM ESTUDIO_MEDICO e
            INNER JOIN MEDICO m ON e.id_medico_emisor = m.id_medico
            WHERE e.id_paciente = :usuario 
            ORDER BY e.Fecha_emision DESC";
            
    $stmt = $conexion->prepare($sql);
    $stmt->bindParam(':usuario', $id_usuario, PDO::PARAM_INT);
    $stmt->execute();
    $estudios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error_estudios = "Error al cargar el historial digital médico.";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Resultados - SaludMza</title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
</head>
<body class="bg-blue-50 text-slate-800 font-sans min-h-screen pb-20">

    <header class="w-full sticky top-0 z-40 bg-white border-b border-gray-200 shadow-sm flex justify-between items-center px-4 py-3">
        <h1 class="text-xl font-bold text-blue-700">Historial Médico</h1>
        <a href="inicio.php" class="material-symbols-outlined text-gray-500">home</a>
    </header>

    <main class="w-full max-w-4xl mx-auto px-4 pt-6 flex flex-col gap-6">
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h2 class="text-lg font-semibold text-gray-800">Mis Estudios Digitales</h2>
            <p class="text-xs text-gray-400 mt-1">Aquí puedes ver y descargar tus informes médicos emitidos en formato PDF.</p>
        </div>

        <section class="flex flex-col gap-4">
            
            <?php if (isset($error_estudios)): ?>
                <div class="bg-red-100 border border-red-200 text-red-700 px-4 py-4 rounded-xl text-sm flex items-center gap-3">
                    <span class="material-symbols-outlined text-2xl">error</span>
                    <p><?php echo $error_estudios; ?></p>
                </div>

            <?php elseif (empty($estudios)): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-10 flex flex-col items-center text-center">
                    <span class="material-symbols-outlined text-gray-300 text-5xl mb-2">clinical_notes</span>
                    <h3 class="font-semibold text-gray-700">Sin estudios registrados</h3>
                    <p class="text-sm text-gray-500">Aún no se han cargado resultados de laboratorio o imágenes para tu cuenta.</p>
                </div>

            <?php else: ?>
                <?php foreach ($estudios as $estudio): ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex justify-between items-center hover:border-blue-300 transition-colors">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center">
                                <span class="material-symbols-outlined text-2xl">description</span>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-800 text-base"><?php echo htmlspecialchars($estudio['Tipo_estudio']); ?></h4>
                                <p class="text-xs text-gray-500">Solicitado por: Dr/a. <?php echo htmlspecialchars($estudio['med_nom'] . " " . $estudio['med_ape']); ?></p>
                                <p class="text-[11px] text-gray-400 mt-1 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">calendar_today</span> 
                                    <?php echo date('d/m/Y', strtotime($estudio['Fecha_emision'])); ?>
                                </p>
                            </div>
                        </div>
                        
                        <a href="descargar_resultado.php?id=<?php echo $estudio['id_estudio']; ?>" class="w-10 h-10 bg-gray-50 hover:bg-blue-600 hover:text-white rounded-full flex items-center justify-center text-blue-600 transition-all shadow-sm">
                            <span class="material-symbols-outlined">download</span>
                        </a>
                    </div>
                <?php endforeach; ?>
                
            <?php endif; ?>

        </section>
    </main>
</body>
</html>