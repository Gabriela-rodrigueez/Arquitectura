<?php
// 1. Forzar a PHP a mostrar cualquier error en pantalla si ocurre
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
// Seguridad: si el paciente no se logueó, lo rebota al login
if (!isset($_SESSION['usuario_conectado'])) { header("Location: login.php"); exit(); }
require 'conexion.php';

$id_usuario = $_SESSION['id_usuario'];
$mensaje_alerta = "";

// =========================================================================
// ACCIÓN A: PROCESAR RESERVA DE TURNO (MÉTODO POST)
// =========================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] === 'reservar') {
    $id_turno_reservar = $_POST['id_turno'];
    try {
        $check = $conexion->prepare("SELECT Estado FROM TURNO WHERE id_turno = :id");
        $check->execute([':id' => $id_turno_reservar]);
        $resCheck = $check->fetch(PDO::FETCH_ASSOC);

        if ($resCheck && $resCheck['Estado'] === 'Disponible') {
            $sqlReserva = "UPDATE TURNO SET id_paciente = :paciente, Estado = 'Reservado' WHERE id_turno = :id";
            $stmtReserva = $conexion->prepare($sqlReserva);
            $stmtReserva->execute([':paciente' => $id_usuario, ':id' => $id_turno_reservar]);
            
            $mensaje_alerta = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl text-sm mb-4 animate-fade-in'>✅ Turno confirmado correctamente. Puedes verlo en la pestaña 'Activos'.</div>";
        } else {
            $mensaje_alerta = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl text-sm mb-4 animate-fade-in'>❌ El turno ya no se encuentra disponible.</div>";
        }
    } catch (PDOException $e) {
        $mensaje_alerta = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl text-sm mb-4'>❌ Error al procesar reserva.</div>";
    }
}

// =========================================================================
// ACCIÓN B: CANCELAR TURNO ACTIVO (MÉTODO POST)
// =========================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] === 'cancelar') {
    $id_turno_cancelar = $_POST['id_turno'];
    try {
        $sqlCancel = "UPDATE TURNO SET id_paciente = NULL, Estado = 'Disponible', Check_iN = FALSE WHERE id_turno = :id";
        $stmtCancel = $conexion->prepare($sqlCancel);
        $stmtCancel->execute([':id' => $id_turno_cancelar]);
        $mensaje_alerta = "<div class='bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded-xl text-sm mb-4 animate-fade-in'>ℹ️ El turno fue cancelado y liberado con éxito.</div>";
    } catch (PDOException $e) {}
}

// =========================================================================
// CONSULTAS GENERALES: SELECTS DINÁMICOS DESDE TU BASE DE DATOS
// =========================================================================
$especialidades = []; $medicos = []; $sedes = []; $turnos_encontrados = []; $turnos_activos = []; $historial_turnos = []; $lista_espera_activa = [];
$busqueda_realizada = false;

try {
    $especialidades = $conexion->query("SELECT DISTINCT Especialidad FROM MEDICO ORDER BY Especialidad ASC")->fetchAll(PDO::FETCH_COLUMN);
    $medicos = $conexion->query("SELECT id_medico, Nombre, Apellido FROM MEDICO ORDER BY Apellido ASC")->fetchAll(PDO::FETCH_ASSOC);
    $sedes = $conexion->query("SELECT id_centroSalud, Nombre FROM CENTRO_SALUD ORDER BY Nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

    // BÚSQUEDA FILTRADA (MÉTODO GET)
    if ($_SERVER["REQUEST_METHOD"] == "GET" && (isset($_GET['especialidad']) || isset($_GET['fecha']))) {
        $busqueda_realizada = true;
        $filter_esp = $_GET['especialidad'] ?? '';
        $filter_med = $_GET['medico'] ?? '';
        $filter_fecha = $_GET['fecha'] ?? '';
        $filter_sede = $_GET['sede'] ?? '';

        $querySearch = "SELECT t.id_turno, t.Fecha_hora, m.Nombre AS med_nom, m.Apellido AS med_ape, m.Especialidad, c.Nombre AS centro_nom 
                        FROM TURNO t
                        INNER JOIN MEDICO m ON t.id_medico = m.id_medico
                        INNER JOIN CENTRO_SALUD c ON t.id_centroSalud = c.id_centroSalud
                        WHERE t.Estado = 'Disponible' AND t.Fecha_hora >= NOW()";

        if (!empty($filter_esp)) { $querySearch .= " AND m.Especialidad = :esp"; }
        if (!empty($filter_med)) { $querySearch .= " AND m.id_medico = :med"; }
        if (!empty($filter_sede)) { $querySearch .= " AND c.id_centroSalud = :sede"; }
        if (!empty($filter_fecha)) { $querySearch .= " AND DATE(t.Fecha_hora) = :fecha"; }

        $querySearch .= " ORDER BY t.Fecha_hora ASC LIMIT 15";
        $stmtSearch = $conexion->prepare($querySearch);
        
        if (!empty($filter_esp)) $stmtSearch->bindValue(':esp', $filter_esp);
        if (!empty($filter_med)) $stmtSearch->bindValue(':med', $filter_med);
        if (!empty($filter_sede)) $stmtSearch->bindValue(':sede', $filter_sede);
        if (!empty($filter_fecha)) $stmtSearch->bindValue(':fecha', $filter_fecha);

        $stmtSearch->execute();
        $turnos_encontrados = $stmtSearch->fetchAll(PDO::FETCH_ASSOC);
    }

    // TRAER TURNOS ACTIVOS
    $stmtActivos = $conexion->prepare("SELECT t.id_turno, t.Fecha_hora, m.Nombre AS med_nom, m.Apellido AS med_ape, m.Especialidad, c.Nombre AS centro_nom, c.Direccion, t.Check_iN FROM TURNO t INNER JOIN MEDICO m ON t.id_medico = m.id_medico INNER JOIN CENTRO_SALUD c ON t.id_centroSalud = c.id_centroSalud WHERE t.id_paciente = :paciente AND t.Fecha_hora >= NOW() AND t.Estado = 'Reservado' ORDER BY t.Fecha_hora ASC");
    $stmtActivos->execute([':paciente' => $id_usuario]);
    $turnos_activos = $stmtActivos->fetchAll(PDO::FETCH_ASSOC);

    // TRAER HISTORIAL Y LISTA DE ESPERA
    $stmtHist = $conexion->prepare("SELECT t.Fecha_hora, m.Especialidad, t.Estado FROM TURNO t INNER JOIN MEDICO m ON t.id_medico = m.id_medico WHERE t.id_paciente = :paciente AND t.Fecha_hora < NOW() ORDER BY t.Fecha_hora DESC LIMIT 10");
    $stmtHist->execute([':paciente' => $id_usuario]);
    $historial_turnos = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

    $stmtEspera = $conexion->prepare("SELECT l.fecha_inscripcion, m.Especialidad, m.Apellido, c.Nombre AS centro_nom FROM LISTA_ESPERA l INNER JOIN MEDICO m ON l.id_medico = m.id_medico INNER JOIN CENTRO_SALUD c ON l.id_centroSalud = c.id_centroSalud WHERE l.id_paciente = :paciente AND l.estado = 'Activa'");
    $stmtEspera->execute([':paciente' => $id_usuario]);
    $lista_espera_activa = $stmtEspera->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $mensaje_alerta = "<div class='bg-yellow-100 border border-yellow-300 text-yellow-800 px-4 py-3 rounded-xl text-sm mb-4'>⚠️ Sincronizado localmente de manera segura. Recuerda poblar tus tablas en DBeaver.</div>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Turnos - SaludMza</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "surface-container-low": "#eff4ff", "secondary-container": "#e0e3e5", "on-secondary-fixed-variant": "#444749",
                        "background": "#f8f9ff", "on-tertiary-fixed-variant": "#005236", "tertiary-container": "#00855b",
                        "error": "#ba1a1a", "outline": "#717881", "surface-container-high": "#dce9ff", "surface-dim": "#cbdbf5",
                        "on-tertiary-fixed": "#002113", "primary-container": "#247ab7", "inverse-surface": "#213145",
                        "secondary": "#5c5f61", "on-primary": "#ffffff", "primary-fixed": "#cee5ff", "surface-tint": "#00639b",
                        "secondary-fixed": "#e0e3e5", "on-primary-fixed": "#001d33", "on-secondary-container": "#626567",
                        "surface-container-lowest": "#ffffff", "inverse-on-surface": "#eaf1ff", "on-secondary": "#ffffff",
                        "error-container": "#ffdad6", "outline-variant": "#c0c7d1", "surface-container-highest": "#d3e4fe",
                        "surface-variant": "#d3e4fe", "surface-bright": "#f8f9ff", "on-error": "#ffffff", "on-background": "#0b1c30",
                        "on-surface-variant": "#404750", "primary-fixed-dim": "#97cbff", "on-primary-container": "#fdfcff",
                        "on-surface": "#0b1c30", "on-tertiary": "#ffffff", "on-secondary-fixed": "#191c1e", "on-tertiary-container": "#f5fff6",
                        "tertiary-fixed-dim": "#4edea3", "on-primary-fixed-variant": "#004a76", "primary": "#006098",
                        "tertiary-fixed": "#6ffbbe", "surface": "#f8f9ff", "secondary-fixed-dim": "#c4c7c9", "inverse-primary": "#97cbff",
                        "on-error-container": "#93000a", "tertiary": "#006947", "surface-container": "#e5eeff"
                    },
                    "spacing": { "margin-desktop": "48px", "margin-mobile": "16px", "xl": "32px", "base": "4px", "gutter": "16px", "lg": "24px", "md": "16px", "sm": "8px", "xs": "4px" },
                    "fontFamily": { "body-md": ["Inter", "sans-serif"], "sans": ["Inter", "sans-serif"] },
                    // CORRECCIÓN SINTÁCTICA DEL OBJETO TAILWIND CONFIG
                    "fontSize": {
                        "body-lg": "16px", "label-md": "12px", "headline-md": "24px", "headline-sm": "20px", "title-lg": "18px", "display-lg-mobile": "28px"
                    }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .material-symbols-outlined[data-weight="fill"] { font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        body { min-height: max(884px, 100dvh); }
    </style>
</head>
<body class="bg-background text-on-background font-body-md text-body-md min-h-screen flex flex-col pb-24">

    <!-- HEADER -->
    <header class="bg-surface border-b border-outline-variant shadow-sm w-full top-0 sticky z-40 flex justify-between items-center px-margin-mobile py-sm">
        <div class="flex items-center gap-sm">
            <div class="w-8 h-8 rounded-full bg-surface-container-high overflow-hidden flex-shrink-0 flex items-center justify-center text-primary font-bold">
                <span class="material-symbols-outlined">person</span>
            </div>
            <h1 class="text-headline-md font-headline-md text-primary">SaludMza</h1>
        </div>
        <button type="button" class="text-primary hover:bg-surface-container-low transition-colors p-sm rounded-full">
            <span class="material-symbols-outlined">notifications</span>
        </button>
    </header>

    <!-- PESTAÑAS (TABS) -->
    <div class="bg-surface border-b border-outline-variant sticky top-[56px] z-30">
        <div class="flex w-full px-margin-mobile">
            <button type="button" class="flex-1 py-sm text-center border-b-2 border-primary text-primary font-title-lg text-title-lg transition-all font-bold" id="tab-solicitar" onclick="switchTab('solicitar')">Solicitar</button>
            <button type="button" class="flex-1 py-sm text-center border-b-2 border-transparent text-secondary font-title-lg text-title-lg hover:text-primary transition-all" id="tab-activos" onclick="switchTab('activos')">Activos</button>
            <button type="button" class="flex-1 py-sm text-center border-b-2 border-transparent text-secondary font-title-lg text-title-lg hover:text-primary transition-all" id="tab-historial" onclick="switchTab('historial')">Historial</button>
        </div>
    </div>

    <!-- CUERPO DE PESTAÑAS -->
    <main class="flex-1 px-margin-mobile py-md">
        <?php echo $mensaje_alerta; ?>
        
        <!-- PANEL SOLICITAR -->
        <div class="block space-y-md" id="panel-solicitar">
            <form action="turnos.php" method="GET" class="bg-surface rounded-xl shadow-sm border border-outline-variant p-md space-y-md block w-full">
                <h2 class="font-headline-sm text-headline-sm text-on-surface">Nuevo Turno</h2>
                
                <div class="space-y-sm">
                    <label class="font-label-md text-label-md text-on-surface-variant block">Especialidad o Estudio</label>
                    <div class="relative">
                        <select name="especialidad" class="w-full bg-surface border border-outline rounded-lg p-sm appearance-none text-on-surface font-body-lg text-body-lg focus:border-primary focus:ring-1 focus:ring-primary outline-none" required>
                            <option value="">Seleccionar Especialidad</option>
                            <?php foreach ($especialidades as $esp): ?>
                                <option value="<?php echo htmlspecialchars($esp); ?>" <?php if(isset($_GET['especialidad']) && $_GET['especialidad'] === $esp) echo 'selected'; ?>><?php echo htmlspecialchars($esp); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="material-symbols-outlined absolute right-sm top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none">expand_more</span>
                    </div>
                </div>
                
                <div class="space-y-sm">
                    <label class="font-label-md text-label-md text-on-surface-variant block">Médico (Opcional)</label>
                    <div class="relative">
                        <select name="medico" class="w-full bg-surface border border-outline rounded-lg p-sm appearance-none text-on-surface font-body-lg text-body-lg focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                            <option value="">Cualquier profesional disponible</option>
                            <?php foreach ($medicos as $med): ?>
                                <option value="<?php echo $med['id_medico']; ?>" <?php if(isset($_GET['medico']) && $_GET['medico'] == $med['id_medico']) echo 'selected'; ?>>Dr/a. <?php echo htmlspecialchars($med['Apellido'] . ", " . $med['Nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="material-symbols-outlined absolute right-sm top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none">expand_more</span>
                    </div>
                </div>
                
                <div class="flex gap-md">
                    <div class="space-y-sm flex-1">
                        <label class="font-label-md text-label-md text-on-surface-variant block">Fecha</label>
                        <input name="fecha" class="w-full bg-surface border border-outline rounded-lg p-sm text-on-surface font-body-lg text-body-lg focus:border-primary focus:ring-1 focus:ring-primary outline-none" type="date" value="<?php echo $_GET['fecha'] ?? date('Y-m-d'); ?>"/>
                    </div>
                    <div class="space-y-sm flex-1">
                        <label class="font-label-md text-label-md text-on-surface-variant block">Sede</label>
                        <select name="sede" class="w-full bg-surface border border-outline rounded-lg p-sm appearance-none text-on-surface font-body-lg text-body-lg focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                            <option value="">Todas las Sedes</option>
                            <?php foreach ($sedes as $sd): ?>
                                <option value="<?php echo $sd['id_centroSalud']; ?>" <?php if(isset($_GET['sede']) && $_GET['sede'] == $sd['id_centroSalud']) echo 'selected'; ?>><?php echo htmlspecialchars($sd['Nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="w-full bg-primary text-on-primary font-title-lg text-title-lg py-sm rounded-full mt-sm hover:bg-surface-tint flex items-center justify-center gap-xs shadow-sm">
                    <span class="material-symbols-outlined text-[20px]">search</span> Buscar Horarios
                </button>
            </form>

            <!-- GRILLA DINÁMICA -->
            <?php if ($busqueda_realizada): ?>
                <div class="mt-lg">
                    <h3 class="font-title-lg text-title-lg text-on-surface mb-sm">Horarios Disponibles</h3>
                    <?php if (empty($turnos_encontrados)): ?>
                        <div class="bg-surface border border-outline-variant p-8 rounded-xl text-center text-secondary italic text-xs">No se encontraron turnos disponibles.</div>
                    <?php else: ?>
                        <div class="grid grid-cols-3 gap-sm">
                            <?php foreach ($turnos_encontrados as $t): ?>
                                <button type="button" class="bg-surface border border-outline-variant rounded-lg p-sm text-center hover:border-primary hover:bg-surface-container-low transition-colors flex flex-col items-center justify-center" 
                                        onclick="openBottomSheet('<?php echo $t['id_turno']; ?>', '<?php echo htmlspecialchars($t['Especialidad']); ?>', '<?php echo date('d/m - H:i', strtotime($t['Fecha_hora'])); ?>', '<?php echo htmlspecialchars($t['med_ape'] . ', ' . $t['med_nom']); ?>', '<?php echo htmlspecialchars($t['centro_nom']); ?>')">
                                    <span class="block font-headline-sm text-headline-sm text-on-surface"><?php echo date('H:i', strtotime($t['Fecha_hora'])); ?></span>
                                    <span class="block text-[10px] text-secondary mt-1 truncate w-full">Dr/a. <?php echo htmlspecialchars($t['med_ape']); ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- PANEL ACTIVOS -->
        <div class="hidden space-y-md" id="panel-activos">
            <?php if (empty($turnos_activos)): ?>
                <div class="bg-surface border border-outline-variant p-8 rounded-xl text-center text-secondary italic text-sm">No posees turnos agendados en este momento.</div>
            <?php else: ?>
                <?php foreach ($turnos_activos as $act): ?>
                    <div class="bg-surface rounded-xl shadow-sm border border-outline-variant p-md">
                        <div class="flex justify-between items-start mb-sm">
                            <div>
                                <span class="<?php echo $act['Check_iN'] ? 'bg-tertiary-container text-on-tertiary-container' : 'bg-surface-container-high text-primary'; ?> font-label-md text-label-md px-2 py-1 rounded-full">
                                    <?php echo $act['Check_iN'] ? 'En Establecimiento' : 'Confirmado'; ?>
                                </span>
                                <h3 class="font-headline-sm text-headline-sm text-on-surface mt-xs"><?php echo htmlspecialchars($act['Especialidad']); ?></h3>
                                <p class="font-body-md text-body-md text-secondary mt-xs">Dr/a. <?php echo htmlspecialchars($act['med_nom'] . ' ' . $act['med_ape']); ?></p>
                            </div>
                            <div class="text-right">
                                <span class="block font-headline-md text-headline-md text-primary"><?php echo date('d M', strtotime($act['Fecha_hora'])); ?></span>
                                <span class="block font-body-md text-body-md text-on-surface-variant"><?php echo date('H:i', strtotime($act['Fecha_hora'])); ?> hs</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-xs text-secondary font-body-md text-body-md mb-md">
                            <span class="material-symbols-outlined text-[16px]">location_on</span> <?php echo htmlspecialchars($act['centro_nom'] . ', ' . $act['Direccion']); ?>
                        </div>
                        <div class="flex gap-sm">
                            <form action="turnos.php" method="POST" class="w-full flex">
                                <input type="hidden" name="id_turno" value="<?php echo $act['id_turno']; ?>">
                                <input type="hidden" name="accion" value="cancelar">
                                <button type="submit" class="w-full border border-error text-error font-title-lg text-title-lg py-sm rounded-full hover:bg-error-container transition-colors">Cancelar Turno</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- PANEL HISTORIAL -->
        <div class="hidden space-y-md" id="panel-historial">
            <?php foreach ($historial_turnos as $h): ?>
                <div class="bg-surface rounded-xl shadow-sm border border-outline-variant p-md opacity-75">
                    <div class="flex justify-between items-center mb-xs">
                        <h3 class="font-title-lg text-title-lg text-on-surface-variant"><?php echo htmlspecialchars($h['Especialidad']); ?></h3>
                        <span class="font-label-md text-label-md text-secondary"> <?php echo date('d M Y', strtotime($h['Fecha_hora'])); ?></span>
                    </div>
                    <p class="font-body-md text-body-md text-secondary">Estado de cita: <?php echo htmlspecialchars($h['Estado']); ?></p>
                </div>
            <?php endforeach; ?>
            
            <?php foreach ($lista_espera_activa as $le): ?>
                <div class="bg-surface-container-low rounded-xl border border-dashed border-outline-variant p-md">
                    <h3 class="font-title-lg text-title-lg text-on-surface-variant"><?php echo htmlspecialchars($le['Especialidad']); ?> (Lista Espera)</h3>
                    <p class="font-body-md text-body-md text-secondary">Sede: <?php echo htmlspecialchars($le['centro_nom']); ?> • Te notificaremos prioritariamente.</p>
                </div>
            <?php endforeach; ?>

            <?php if(empty($historial_turnos) && empty($lista_espera_activa)): ?>
                <div class="bg-surface border border-outline-variant p-8 rounded-xl text-center text-secondary italic text-sm">No se registran atenciones pasadas.</div>
            <?php endif; ?>
        </div>
    </main>

    <!-- BOTTOM SHEET DE CONFIRMACIÓN -->
    <div class="fixed inset-0 z-50 flex items-end justify-center pointer-events-none opacity-0 transition-opacity duration-300 shadow-2xl" id="bottom-sheet-overlay">
        <div class="absolute inset-0 bg-on-background/30 pointer-events-auto backdrop-blur-sm" onclick="closeBottomSheet()"></div>
        
        <div class="bg-surface w-full max-w-md rounded-t-xl p-md pointer-events-auto transform translate-y-full transition-transform duration-300 shadow-lg pb-10 relative z-50" id="bottom-sheet-content">
            <div class="w-12 h-1 bg-outline-variant rounded-full mx-auto mb-md"></div>
            <h2 class="font-headline-md text-headline-md text-on-surface mb-sm">Confirmar Turno</h2>
            
            <div class="bg-surface-container-low rounded-lg p-sm mb-md space-y-xs text-xs border border-gray-100">
                <div class="flex justify-between">
                    <span class="text-secondary">Estudio/Especialidad:</span>
                    <span class="font-title-lg text-on-surface font-bold" id="bs-especialidad">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-secondary">Profesional Médico:</span>
                    <span class="font-title-lg text-on-surface font-bold" id="bs-medico">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-secondary">Fecha y Hora:</span>
                    <span class="font-title-lg text-on-surface font-bold" id="bs-fecha">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-secondary">Establecimiento Sede:</span>
                    <span class="font-title-lg text-on-surface font-bold" id="bs-centro">-</span>
                </div>
                <div class="flex justify-between items-center mt-sm pt-sm border-t border-outline-variant">
                    <span class="text-secondary">Cobertura Salud Provincial:</span>
                    <span class="bg-tertiary-container text-on-tertiary-container font-label-md text-label-md px-2 py-1 rounded">Validada</span>
                </div>
            </div>
            
            <div class="flex gap-sm">
                <button type="button" class="flex-1 py-sm font-title-lg text-title-lg text-secondary hover:bg-surface-container-high rounded-full transition-colors" onclick="closeBottomSheet()">Cancelar</button>
                <form action="turnos.php" method="POST" class="flex-1 m-0 p-0 flex">
                    <input type="hidden" name="id_turno" id="bs-input-id" value="">
                    <input type="hidden" name="accion" value="reservar">
                    <button type="submit" class="w-full py-sm font-title-lg text-title-lg bg-primary text-on-primary rounded-full hover:bg-surface-tint active:scale-[0.98] transition-all shadow-sm">Confirmar</button>
                </form>
            </div>
        </div>
    </div>

    <!-- NAVBAR INFERIOR -->
    <nav class="fixed bottom-0 w-full z-40 rounded-t-full bg-surface-container-lowest shadow-[0px_-2px_8px_rgba(0,0,0,0.05)] flex justify-around items-center px-4 pb-4 pt-2">
        <a href="inicio.php" class="flex flex-col items-center justify-center text-secondary hover:bg-surface-container-high p-2 rounded-lg w-full">
            <span class="material-symbols-outlined">home</span><span class="font-label-md text-label-md mt-1">Inicio</span>
        </a>
        <a href="turnos.php" class="flex flex-col items-center justify-center bg-primary-container text-on-primary-container rounded-full px-4 py-1 w-full">
            <span class="material-symbols-outlined" data-weight="fill">calendar_month</span><span class="font-label-md text-label-md mt-1">Turnos</span>
        </a>
        <a href="resultados.php" class="flex flex-col items-center justify-center text-secondary hover:bg-surface-container-high p-2 rounded-lg w-full">
            <span class="material-symbols-outlined">biotech</span><span class="font-label-md text-label-md mt-1">Resultados</span>
        </a>
        <a href="misdatos.php" class="flex flex-col items-center justify-center text-secondary hover:bg-surface-container-high p-2 rounded-lg w-full">
            <span class="material-symbols-outlined">person</span><span class="font-label-md text-label-md mt-1">Mis Datos</span>
        </a>
    </nav>

    <!-- JS MANEJADO POR ID DIRECTO (MÁXIMA ESTABILIDAD) -->
    <script>
        function switchTab(tabId) {
            document.getElementById('panel-solicitar').style.display = 'none';
            document.getElementById('panel-activos').style.display = 'none';
            document.getElementById('panel-historial').style.display = 'none';
            
            document.getElementById('tab-solicitar').className = 'flex-1 py-3 text-center border-b-2 border-transparent text-secondary font-title-lg text-title-lg';
            document.getElementById('tab-activos').className = 'flex-1 py-3 text-center border-b-2 border-transparent text-secondary font-title-lg text-title-lg';
            document.getElementById('tab-historial').className = 'flex-1 py-3 text-center border-b-2 border-transparent text-secondary font-title-lg text-title-lg';

            document.getElementById('panel-' + tabId).style.display = 'block';
            document.getElementById('tab-' + tabId).className = 'flex-1 py-3 text-center border-b-2 border-primary text-primary font-title-lg text-title-lg font-bold';
        }

        function openBottomSheet(id, esp, fecha, medico, centro) {
            document.getElementById('bs-input-id').value = id;
            document.getElementById('bs-especialidad').innerText = esp;
            document.getElementById('bs-fecha').innerText = fecha + " hs";
            document.getElementById('bs-medico').innerText = "Dr/a. " + medico;
            document.getElementById('bs-centro').innerText = centro;

            var overlay = document.getElementById('bottom-sheet-overlay');
            var content = document.getElementById('bottom-sheet-content');
            
            if (overlay && content) {
                overlay.style.pointerEvents = 'auto';
                overlay.style.opacity = '1';
                content.style.transform = 'translateY(0)';
            }
        }

        function closeBottomSheet() {
            var overlay = document.getElementById('bottom-sheet-overlay');
            var content = document.getElementById('bottom-sheet-content');
            if (overlay && content) {
                content.style.transform = 'translateY(100%)';
                setTimeout(function() {
                    overlay.style.opacity = '0';
                    overlay.style.pointerEvents = 'none';
                }, 300);
            }
        }
    </script>
</body>
</html>