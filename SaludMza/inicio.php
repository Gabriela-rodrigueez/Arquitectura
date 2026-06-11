<?php
session_start();
if (!isset($_SESSION['usuario_conectado'])) { header("Location: login.php"); exit(); }
require 'conexion.php';

$nombre_usuario = $_SESSION['nombre_usuario'];
$id_usuario = $_SESSION['id_usuario'];

$proximo_turno = null;
$puede_hacer_checkin = false;

try {
    // CONSULTA CORREGIDA: Ajustada a TURNO, MEDICO y CENTRO_SALUD de la nueva BD
    $sql = "SELECT t.id_turno, t.Fecha_hora, c.Nombre AS sede, m.Nombre AS med_nombre, m.Apellido AS med_apellido, m.Especialidad 
            FROM TURNO t
            INNER JOIN MEDICO m ON t.id_medico = m.id_medico
            INNER JOIN CENTRO_SALUD c ON t.id_centroSalud = c.id_centroSalud
            WHERE t.id_paciente = :usuario AND t.Fecha_hora >= NOW() AND t.Estado = 'Reservado'
            ORDER BY t.Fecha_hora ASC LIMIT 1";

    $stmt = $conexion->prepare($sql);
    $stmt->bindParam(':usuario', $id_usuario, PDO::PARAM_INT);
    $stmt->execute();
    $proximo_turno = $stmt->fetch(PDO::FETCH_ASSOC);

    // LÓGICA DEL CHECK-IN EXPRESS (15 minutos antes)
    if ($proximo_turno) {
        $fecha_turno = new DateTime($proximo_turno['Fecha_hora']);
        $ahora = new DateTime();
        
        $diferencia = $ahora->diff($fecha_turno);

        if ($diferencia->days == 0 && $diferencia->invert == 0 && $diferencia->h == 0 && $diferencia->i <= 15) {
            $puede_hacer_checkin = true;
        }
    }
} catch(PDOException $e) {
    $error_db = true; 
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - SaludMza</title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
</head>
<body class="bg-blue-50 text-slate-800 font-sans min-h-screen pb-20">

    <header class="w-full bg-blue-700 text-white rounded-b-3xl shadow-md px-6 pt-8 pb-10 mb-[-30px] relative z-10">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-blue-200 text-sm mb-1">¡Hola de nuevo!</p>
                <h1 class="text-2xl font-bold"><?php echo explode(' ', htmlspecialchars($nombre_usuario))[0]; ?></h1>
            </div>
            <a href="misdatos.php" class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center hover:bg-white/30 transition-colors">
                <span class="material-symbols-outlined">person</span>
            </a>
        </div>
    </header>

    <main class="w-full max-w-4xl mx-auto px-4 relative z-20 flex flex-col gap-6">

        <section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-2 mb-4 text-blue-700">
                <span class="material-symbols-outlined">calendar_clock</span>
                <h2 class="font-bold text-lg">Tu Próximo Turno</h2>
            </div>

            <?php if (isset($error_db)): ?>
                <p class="text-sm text-gray-500">Sincronizando agenda...</p>
            
            <?php elseif ($proximo_turno): ?>
                <div class="border-l-4 border-blue-500 pl-4 mb-4">
                    <p class="font-bold text-gray-800 text-lg">Dr/a. <?php echo htmlspecialchars($proximo_turno['med_nombre'] . " " . $proximo_turno['med_apellido']); ?></p>
                    <p class="text-blue-600 text-sm font-medium"><?php echo htmlspecialchars($proximo_turno['Especialidad']); ?></p>
                    <p class="text-sm text-gray-500 mt-2 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px]">schedule</span> 
                        <?php echo date('d/m/Y - H:i', strtotime($proximo_turno['Fecha_hora'])); ?> hs
                    </p>
                    <p class="text-sm text-gray-500 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px]">location_on</span> 
                        <?php echo htmlspecialchars($proximo_turno['sede']); ?>
                    </p>
                </div>

                <?php if ($puede_hacer_checkin): ?>
                    <form action="procesar_checkin.php" method="POST">
                        <input type="hidden" name="id_turno" value="<?php echo $proximo_turno['id_turno']; ?>">
                        <button type="submit" class="w-full bg-green-500 text-white font-bold py-3 rounded-xl shadow-md shadow-green-200 hover:bg-green-600 flex justify-center items-center gap-2 animate-pulse">
                            <span class="material-symbols-outlined">how_to_reg</span> Anunciar Llegada (Check-in)
                        </button>
                    </form>
                <?php else: ?>
                    <div class="bg-gray-50 rounded-xl p-3 text-center border border-gray-200">
                        <p class="text-xs text-gray-500 flex items-center justify-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">info</span>
                            El check-in se habilitará 15 min antes del turno.
                        </p>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="text-center py-4">
                    <p class="text-gray-500 text-sm mb-4">No tienes citas médicas programadas.</p>
                    <a href="turnos.php" class="inline-block border border-blue-600 text-blue-600 font-medium px-6 py-2 rounded-full hover:bg-blue-50 transition-colors text-sm">
                        Buscar un Turno
                    </a>
                </div>
            <?php endif; ?>
        </section>

        <section class="grid grid-cols-2 gap-4">
            <a href="resultados.php" class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center text-center hover:border-blue-300 transition-colors">
                <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mb-2">
                    <span class="material-symbols-outlined">clinical_notes</span>
                </div>
                <span class="text-sm font-bold text-gray-700">Resultados</span>
            </a>

            <a href="farmacia.php" class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center text-center hover:border-blue-300 transition-colors">
                <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mb-2">
                    <span class="material-symbols-outlined">vaccines</span>
                </div>
                <span class="text-sm font-bold text-gray-700">Farmacia</span>
            </a>

            <a href="grupo_familiar.php" class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center text-center hover:border-blue-300 transition-colors col-span-2">
                <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mb-2">
                    <span class="material-symbols-outlined">family_restroom</span>
                </div>
                <span class="text-sm font-bold text-gray-700">Mi Grupo Familiar</span>
                <span class="text-xs text-gray-400 mt-1">Gestionar dependientes</span>
            </a>
        </section>

    </main>
</body>
</html>