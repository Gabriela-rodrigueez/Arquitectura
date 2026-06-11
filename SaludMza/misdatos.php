<?php
session_start();
if (!isset($_SESSION['usuario_conectado'])) { header("Location: login.php"); exit(); }
require 'conexion.php';

$id_usuario = $_SESSION['id_usuario'];
$datos_usuario = null;
$cobertura = null;

try {
    // 1. Buscamos los datos personales del usuario logueado
    $sqlUser = "SELECT Nombre, Apellido, DNI, Email, Telefono, Rango_edad, Datos_salud FROM USUARIO WHERE id_usuario = :id";
    $stmtU = $conexion->prepare($sqlUser);
    $stmtU->bindParam(':id', $id_usuario, PDO::PARAM_INT);
    $stmtU->execute();
    $datos_usuario = $stmtU->fetch(PDO::FETCH_ASSOC);

    // 2. Buscamos si tiene una cobertura médica asociada (Muchos a Muchos)
    $sqlCob = "SELECT cm.Nombre_OS, uc.Nro_afiliado, uc.Plan, cm.Tipo 
               FROM USUARIO_COBERTURA uc
               INNER JOIN COBERTURA_MEDICA cm ON uc.id_cobertura = cm.id_cobertura
               WHERE uc.id_usuario = :id LIMIT 1";
    $stmtC = $conexion->prepare($sqlCob);
    $stmtC->bindParam(':id', $id_usuario, PDO::PARAM_INT);
    $stmtC->execute();
    $cobertura = $stmtC->fetch(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    echo "Error al cargar el perfil.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Mis Datos - SaludMza</title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
</head>
<body class="bg-blue-50 text-slate-800 font-sans min-h-screen pb-24">

    <header class="w-full bg-white border-b border-gray-200 shadow-sm flex justify-between items-center px-4 py-3 sticky top-0 z-40">
        <h1 class="text-xl font-bold text-blue-700">Mi Perfil Digital</h1>
        <a href="inicio.php" class="material-symbols-outlined text-gray-500 hover:text-blue-600 transition-colors">arrow_back</a>
    </header>

    <main class="w-full max-w-2xl mx-auto px-4 pt-6 flex flex-col gap-6">
        
        <section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <h2 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-1">
                <span class="material-symbols-outlined text-base">badge</span> Datos Personales
            </h2>
            <div class="flex flex-col gap-3">
                <div class="border-b pb-2">
                    <p class="text-xs text-gray-400">Nombre y Apellido</p>
                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($datos_usuario['Nombre'] . " " . $datos_usuario['Apellido']); ?></p>
                </div>
                <div class="border-b pb-2">
                    <p class="text-xs text-gray-400">Documento (DNI)</p>
                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($datos_usuario['DNI']); ?></p>
                </div>
                <div class="border-b pb-2">
                    <p class="text-xs text-gray-400">Correo Electrónico</p>
                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($datos_usuario['Email']); ?></p>
                </div>
                <div class="border-b pb-2">
                    <p class="text-xs text-gray-400">Teléfono de Contacto</p>
                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($datos_usuario['Telefono'] ?? 'No registrado'); ?></p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Grupo de Edad</p>
                    <span class="inline-block mt-1 px-3 py-1 bg-blue-100 text-blue-700 font-semibold text-xs rounded-full">
                        <?php echo htmlspecialchars($datos_usuario['Rango_edad']); ?>
                    </span>
                </div>
            </div>
        </section>

        <section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <h2 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-1">
                <span class="material-symbols-outlined text-base">credit_card</span> Cobertura Médica
            </h2>
            <?php if ($cobertura): ?>
                <div class="bg-gradient-to-r from-blue-700 to-indigo-800 text-white p-4 rounded-xl shadow-inner relative overflow-hidden">
                    <p class="text-xs text-blue-200 uppercase tracking-widest font-bold"><?php echo htmlspecialchars($cobertura['Tipo']); ?></p>
                    <p class="text-xl font-bold mt-1"><?php echo htmlspecialchars($cobertura['Nombre_OS']); ?></p>
                    <div class="flex justify-between mt-6 text-sm font-mono">
                        <div>
                            <p class="text-[10px] text-blue-200 font-sans">PLAN</p>
                            <p><?php echo htmlspecialchars($cobertura['Plan']); ?></p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] text-blue-200 font-sans">N° AFILIADO</p>
                            <p><?php echo htmlspecialchars($cobertura['Nro_afiliado']); ?></p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center py-4 border border-dashed rounded-xl border-gray-300">
                    <p class="text-gray-500 text-sm">No tienes una Obra Social o Prepaga cargada.</p>
                    <p class="text-xs text-gray-400">Atención bajo modalidad: Particular / Salud Pública.</p>
                </div>
            <?php endif; ?>
        </section>

        <section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <h2 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-2 flex items-center gap-1">
                <span class="material-symbols-outlined text-base text-red-500">health_and_safety</span> Información de Salud (Alergias/Antecedentes)
            </h2>
            <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                <p class="text-sm text-gray-700 italic">
                    <?php echo !empty($datos_usuario['Datos_salud']) ? htmlspecialchars($datos_usuario['Datos_salud']) : "Sin observaciones o condiciones preexistentes declaradas."; ?>
                </p>
            </div>
        </section>

        <a href="logout.php" class="w-full bg-red-50 hover:bg-red-100 border border-red-200 text-red-600 font-bold py-3 rounded-full text-center transition-colors block mt-4">
            Cerrar Sesión en este Dispositivo
        </a>

    </main>

    <nav class="w-full bg-white border-t border-gray-200 fixed bottom-0 left-0 py-2 z-50 shadow-lg">
        <ul class="flex justify-around items-center max-w-md mx-auto">
            <li>
                <a href="inicio.php" class="flex flex-col items-center text-gray-400 hover:text-blue-600">
                    <span class="material-symbols-outlined">home</span>
                    <span class="text-[10px] font-medium">Inicio</span>
                </a>
            </li>
            <li>
                <a href="misdatos.php" class="flex flex-col items-center text-blue-600">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1">person</span>
                    <span class="text-[10px] font-bold">Mis Datos</span>
                </a>
            </li>
        </ul>
    </nav>
</body>
</html>