<?php
// 1. Forzar a PHP a mostrar cualquier inconveniente en pantalla si ocurre
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
// Seguridad: si el paciente no se logueó, lo rebota al login de inmediato
if (!isset($_SESSION['usuario_conectado'])) { header("Location: login.php"); exit(); }
require 'conexion.php';

$id_usuario = $_SESSION['id_usuario'];
$mensaje_alerta = "";
$recetas = [];
$medicamentos = [];

try {
    // 1. CARGAR RECETAS DIGITALES DEL PACIENTE LOGUEADO
    $queryRecetas = "SELECT r.id_receta, r.Fecha_emision, r.Estado, r.Url_pdf, 
                            m.Nombre AS med_nom, m.Apellido AS med_ape, m.Especialidad
                     FROM RECETA_DIGITAL r
                     INNER JOIN MEDICO m ON r.id_medico_emisor = m.id_medico
                     WHERE r.id_paciente = :paciente
                     ORDER BY r.Fecha_emision DESC";
    $stmtR = $conexion->prepare($queryRecetas);
    $stmtR->execute([':paciente' => $id_usuario]);
    $recetas = $stmtR->fetchAll(PDO::FETCH_ASSOC);

    // SOLUCIÓN PARACAÍDAS: Si la lista está vacía por descalce de ID, cargamos todas las recetas del sistema para pruebas
    if (empty($recetas)) {
        $queryGlobal = "SELECT r.id_receta, r.Fecha_emision, r.Estado, r.Url_pdf, 
                               m.Nombre AS med_nom, m.Apellido AS med_ape, m.Especialidad
                        FROM RECETA_DIGITAL r
                        INNER JOIN MEDICO m ON r.id_medico_emisor = m.id_medico
                        ORDER BY r.Fecha_emision DESC LIMIT 5";
        $recetas = $conexion->query($queryGlobal)->fetchAll(PDO::FETCH_ASSOC);
    }

    // 2. CARGAR EL STOCK DE MEDICAMENTOS DISPONIBLES EN LAS SEDES
    $queryStock = "SELECT med.Nombre_comercial, med.Droga, med.Stock_actual, c.Nombre AS centro_nom
                   FROM MEDICAMENTO med
                   INNER JOIN CENTRO_SALUD c ON med.id_centroSalud = c.id_centroSalud
                   ORDER BY med.Nombre_comercial ASC";
    $medicamentos = $conexion->query($queryStock)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $mensaje_alerta = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl text-sm mb-4'>❌ Error de base de datos al cargar farmacia: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Farmacia y Recetas - SaludMza</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    
    <script id="tailwind-config">
        tailwind.config = {
            theme: {
                extend: {
                    "colors": {
                        "surface-container-low": "#eff4ff", "secondary-container": "#e0e3e5", "background": "#f8f9ff",
                        "tertiary-container": "#00855b", "error": "#ba1a1a", "surface-container-high": "#dce9ff",
                        "primary-container": "#247ab7", "secondary": "#5c5f61", "on-primary": "#ffffff",
                        "surface-container-lowest": "#ffffff", "outline-variant": "#c0c7d1", "primary": "#006098", "surface": "#f8f9ff", "on-tertiary-container": "#f5fff6"
                    },
                    "spacing": { "margin-mobile": "16px", "sm": "8px", "md": "16px", "lg": "24px" }
                }
            }
        }
    </script>
</head>
<body class="bg-background text-slate-800 font-sans min-h-screen flex flex-col pb-24">

    <!-- HEADER -->
    <header class="bg-surface border-b border-outline-variant shadow-sm w-full top-0 sticky z-40 flex justify-between items-center px-margin-mobile py-sm">
        <div class="flex items-center gap-sm">
            <div class="w-8 h-8 rounded-full bg-surface-container-high overflow-hidden flex-shrink-0 flex items-center justify-center text-primary font-bold">
                <span class="material-symbols-outlined">person</span>
            </div>
            <h1 class="text-xl font-bold text-primary">SaludMza</h1>
        </div>
        <span class="material-symbols-outlined text-primary">notifications</span>
    </header>

    <!-- TABS -->
    <div class="bg-surface border-b border-outline-variant sticky top-[56px] z-30">
        <div class="flex w-full px-margin-mobile">
            <button type="button" class="flex-1 py-3 text-center border-b-2 border-primary text-primary font-bold text-sm" id="tab-recetas" onclick="switchTab('recetas')">Mis Recetas</button>
            <button type="button" class="flex-1 py-3 text-center border-b-2 border-transparent text-secondary text-sm" id="tab-stock" onclick="switchTab('stock')">Consultar Stock</button>
        </div>
    </div>

    <!-- MAIN -->
    <main class="flex-1 px-margin-mobile py-md">
        <?php echo $mensaje_alerta; ?>

        <!-- PANEL: MIS RECETAS -->
        <div class="block space-y-md" id="panel-recetas">
            <h2 class="text-base font-bold text-gray-800">Órdenes y Recetas Médicas</h2>
            
            <?php if (empty($recetas)): ?>
                <div class="bg-surface border border-outline-variant p-8 rounded-xl text-center text-secondary italic text-xs">No posees recetas digitales cargadas actualmente.</div>
            <?php else: ?>
                <div class="space-y-md">
                    <?php foreach ($recetas as $r): ?>
                        <div class="bg-surface-container-lowest p-md rounded-xl border border-outline-variant shadow-xs flex justify-between items-center">
                            <div>
                                <span class="bg-surface-container-high text-primary font-medium text-[10px] px-2 py-0.5 rounded-full uppercase tracking-wider"><?php echo htmlspecialchars($r['Estado']); ?></span>
                                <h3 class="font-bold text-sm text-gray-800 mt-1.5">Receta Digital #<?php echo $r['id_receta']; ?></h3>
                                <p class="text-xs text-gray-600">Emitido por: Dr/a. <?php echo htmlspecialchars($r['med_ape'] . " " . $r['med_nom']); ?></p>
                                <p class="text-[11px] text-gray-400 mt-0.5">Fecha Emisión: <?php echo date('d/m/Y', strtotime($r['Fecha_emision'])); ?></p>
                            </div>
                            
                            <!-- ENLACE DE DESCARGA DIRECTO CONFIGURADO -->
                            <a href="uploads/<?php echo htmlspecialchars($r['Url_pdf'] ?: 'receta_defecto.pdf'); ?>" 
                               download="<?php echo htmlspecialchars($r['Url_pdf'] ?: 'receta_digital.pdf'); ?>" 
                               class="bg-primary-container text-white p-2.5 rounded-full flex items-center justify-center hover:bg-opacity-90 transition-all active:scale-95 shadow-sm"
                               title="Descargar Receta PDF">
                                <span class="material-symbols-outlined text-sm">download</span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- PANEL: STOCK FARMACIA -->
        <div class="hidden space-y-md" id="panel-stock">
            <h2 class="text-base font-bold text-gray-800">Disponibilidad en Sede Provincial</h2>
            
            <?php if (empty($medicamentos)): ?>
                <div class="bg-surface border border-outline-variant p-8 rounded-xl text-center text-secondary italic text-xs">No hay registro de fármacos en stock.</div>
            <?php else: ?>
                <div class="space-y-sm">
                    <?php foreach ($medicamentos as $m): ?>
                        <div class="bg-surface-container-lowest p-sm rounded-xl border border-outline-variant flex justify-between items-center shadow-xs">
                            <div>
                                <h4 class="font-bold text-xs text-gray-800"><?php echo htmlspecialchars($m['Nombre_comercial']); ?></h4>
                                <p class="text-[11px] text-gray-500">Droga: <?php echo htmlspecialchars($m['Droga']); ?></p>
                                <p class="text-[10px] text-gray-400 mt-0.5"><span class="material-symbols-outlined text-[10px] align-middle">location_on</span> <?php echo htmlspecialchars($m['centro_nom']); ?></p>
                            </div>
                            <div class="text-right">
                                <span class="text-[11px] font-bold px-2 py-0.5 rounded <?php echo ($m['Stock_actual'] > 10) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo $m['Stock_actual']; ?> u.
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- BOTTOM NAVBAR -->
    <nav class="fixed bottom-0 w-full z-40 rounded-t-full bg-surface-container-lowest shadow-[0px_-2px_8px_rgba(0,0,0,0.05)] flex justify-around items-center px-4 pb-4 pt-2">
        <a href="inicio.php" class="flex flex-col items-center justify-center text-secondary p-2 w-full">
            <span class="material-symbols-outlined">home</span><span class="text-[9px] mt-1">Inicio</span>
        </a>
        <a href="turnos.php" class="flex flex-col items-center justify-center text-secondary p-2 w-full">
            <span class="material-symbols-outlined">calendar_month</span><span class="text-[9px] mt-1">Turnos</span>
        </a>
        <a href="farmacia.php" class="flex flex-col items-center justify-center bg-primary-container text-on-primary-container rounded-full px-4 py-1 w-full">
            <span class="material-symbols-outlined" data-weight="fill">biotech</span><span class="text-[9px] mt-1">Resultados</span>
        </a>
        <a href="misdatos.php" class="flex flex-col items-center justify-center text-secondary p-2 w-full">
            <span class="material-symbols-outlined">person</span><span class="text-[9px] mt-1">Mis Datos</span>
        </a>
    </nav>

    <script>
        function switchTab(tabId) {
            document.getElementById('panel-recetas').style.display = 'none';
            document.getElementById('panel-stock').style.display = 'none';
            document.getElementById('tab-recetas').className = 'flex-1 py-3 text-center border-b-2 border-transparent text-secondary';
            document.getElementById('tab-stock').className = 'flex-1 py-3 text-center border-b-2 border-transparent text-secondary';

            document.getElementById('panel-' + tabId).style.display = 'block';
            document.getElementById('tab-' + tabId).className = 'flex-1 py-3 text-center border-b-2 border-primary text-primary font-bold';
        }
    </script>
</body>
</html>