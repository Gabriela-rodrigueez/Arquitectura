<?php
session_start();
if (!isset($_SESSION['usuario_conectado'])) { header("Location: login.php"); exit(); }
require 'conexion.php';

$id_padre = $_SESSION['id_usuario'];
$familiares = [];
$mensaje_alerta = "";

// CARGAR GRUPO FAMILIAR (CONSULTA LIMPIA ADAPTADA A TU BD REAL)
try {
    $sql = "SELECT u.id_usuario, u.Nombre, u.Apellido, u.DNI, u.Rango_edad, v.Relacion 
            FROM VINCULO_FAMILIAR v
            INNER JOIN USUARIO u ON v.id_dependiente = u.id_usuario
            WHERE v.id_tutor = :padre";
    $stmt = $conexion->prepare($sql);
    $stmt->bindParam(':padre', $id_padre, PDO::PARAM_INT);
    $stmt->execute();
    $familiares = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $mensaje_alerta = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl text-sm mb-4'>❌ Error de base de datos al listar familiares.</div>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Grupo Familiar - SaludMza</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    
    <script id="tailwind-config">
        tailwind.config = {
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
                    "fontSize": {
                        "body-lg": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                        "label-md": ["12px", {"lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "500"}],
                        "headline-md": ["24px", {"lineHeight": "32px", "letterSpacing": "-0.01em", "fontWeight": "600"}],
                        "headline-sm": ["20px", {"lineHeight": "28px", "fontWeight": "600"}],
                        "title-lg": ["18px", {"lineHeight": "24px", "fontWeight": "600"}],
                        "display-lg-mobile": ["28px", {"lineHeight": "36px", "fontWeight": "700"}]
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

    <header class="bg-surface border-b border-outline-variant shadow-sm w-full top-0 sticky z-40 flex justify-between items-center px-margin-mobile py-sm">
        <div class="flex items-center gap-sm">
            <h1 class="text-headline-md font-headline-md text-primary">Grupo Familiar</h1>
        </div>
        <a href="inicio.php" class="text-primary hover:bg-surface-container-low transition-colors p-sm rounded-full flex items-center">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
    </header>

    <main class="flex-1 px-margin-mobile py-md space-y-lg">
        <?php echo $mensaje_alerta; ?>
        
        <section class="bg-surface-container-lowest rounded-xl border border-outline-variant p-md shadow-sm">
            <h2 class="font-title-lg text-title-lg text-primary mb-md flex items-center gap-xs">
                <span class="material-symbols-outlined">person_add</span> Agregar Familiar a Cargo
            </h2>

            <form action="procesar_familiar.php" method="POST" enctype="multipart/form-data" class="space-y-md">
                <input type="hidden" name="accion" value="insertar">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                    <div class="space-y-sm">
                        <label class="font-label-md text-label-md text-on-surface-variant block uppercase">Tipo de Familiar</label>
                        <select name="rango_edad" id="rango_edad" onchange="evaluarCampos()" class="w-full bg-surface border border-outline rounded-lg p-sm text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none text-body-lg">
                            <option value="Menor">Menor de Edad</option>
                            <option value="Adulto">Adulto / Adulto Mayor a Cargo</option>
                        </select>
                    </div>

                    <div class="space-y-sm">
                        <label class="font-label-md text-label-md text-on-surface-variant block uppercase">Parentesco / Vínculo</label>
                        <select name="relacion" id="relacion" class="w-full bg-surface border border-outline rounded-lg p-sm text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none text-body-lg">
                            <option value="Hijo/a">Hijo/a</option>
                            <option value="Tutelado/a">Tutelado/a</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                    <div class="space-y-sm">
                        <label class="font-label-md text-label-md text-on-surface-variant block uppercase">Nombre</label>
                        <input type="text" name="nombre_hijo" required class="w-full bg-surface border border-outline rounded-lg p-sm text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none text-body-lg">
                    </div>
                    <div class="space-y-sm">
                        <label class="font-label-md text-label-md text-on-surface-variant block uppercase">Apellido</label>
                        <input type="text" name="apellido_hijo" required class="w-full bg-surface border border-outline rounded-lg p-sm text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none text-body-lg">
                    </div>
                    <div class="space-y-sm">
                        <label class="font-label-md text-label-md text-on-surface-variant block uppercase">DNI</label>
                        <input type="text" name="dni_hijo" required class="w-full bg-surface border border-outline rounded-lg p-sm text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none text-body-lg">
                    </div>
                    <div class="space-y-sm">
                        <label class="font-label-md text-label-md text-on-surface-variant block uppercase">Fecha de Nacimiento</label>
                        <input type="date" name="fecha_nac" required class="w-full bg-surface border border-outline rounded-lg p-sm text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none text-body-lg">
                    </div>
                </div>

                <div class="border-t border-outline-variant pt-md mt-sm">
                    <p class="font-title-lg text-body-lg font-bold text-on-surface mb-md">Documentación Obligatoria (PDF o Imagen)</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-md">
                        <div class="flex flex-col gap-xs">
                            <label class="text-[11px] text-secondary font-bold uppercase">Foto DNI (Frente)</label>
                            <input type="file" name="doc_dni" required class="text-xs text-secondary file:mr-sm file:py-xs file:px-md file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-surface-container-low file:text-primary hover:file:bg-surface-container-high transition-colors">
                        </div>
                        <div id="box_partida" class="flex flex-col gap-xs">
                            <label class="text-[11px] text-secondary font-bold uppercase">Partida Nacimiento</label>
                            <input type="file" name="doc_partida" id="doc_partida" required class="text-xs text-secondary file:mr-sm file:py-xs file:px-md file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-surface-container-low file:text-primary hover:file:bg-surface-container-high transition-colors">
                        </div>
                        <div id="box_vacunas" class="flex flex-col gap-xs">
                            <label class="text-[11px] text-secondary font-bold uppercase">Libreta Vacunas</label>
                            <input type="file" name="doc_vacunas" id="doc_vacunas" required class="text-xs text-secondary file:mr-sm file:py-xs file:px-md file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-surface-container-low file:text-primary hover:file:bg-surface-container-high transition-colors">
                        </div>
                    </div>
                </div>

                <div class="pt-sm">
                    <button type="submit" class="w-full bg-primary text-on-primary font-title-lg text-title-lg py-sm rounded-full hover:bg-surface-tint active:scale-[0.99] transition-all shadow-sm">
                        Vincular y Cargar Documentos
                    </button>
                </div>
            </form>
        </section>

        <section class="space-y-md">
            <h3 class="font-title-lg text-label-md text-secondary uppercase tracking-wider">Familiares Vinculados</h3>
            
            <?php if(empty($familiares)): ?>
                <div class="bg-surface border border-dashed border-outline-variant p-lg rounded-xl text-center flex flex-col items-center justify-center gap-xs">
                    <span class="material-symbols-outlined text-outline-variant text-[48px]">family_restroom</span>
                    <p class="text-secondary text-body-md">No has vinculado familiares a tu cuenta todavía.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                    <?php foreach($familiares as $f): ?>
                        <div class="bg-surface-container-lowest p-md rounded-xl border border-outline-variant flex items-center justify-between shadow-xs">
                            <div>
                                <p class="font-bold text-on-background text-body-lg"><?php echo htmlspecialchars($f['Nombre'] . " " . $f['Apellido']); ?></p>
                                <p class="text-xs text-secondary">DNI: <?php echo htmlspecialchars($f['DNI']); ?> • Vínculo: <strong class="text-primary"><?php echo htmlspecialchars($f['Relacion']); ?></strong></p>
                                <span class="inline-block mt-1 px-2 py-0.5 bg-tertiary-container text-on-tertiary-container font-semibold text-[10px] rounded-full uppercase"><?php echo htmlspecialchars($f['Rango_edad']); ?></span>
                            </div>
                            <span class="inline-block px-3 py-0.5 bg-tertiary-container text-on-tertiary-container font-semibold text-xs rounded-full">Vinculado</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script>
        function evaluarCampos() {
            var selector = document.getElementById('rango_edad').value;
            var parentescoSelect = document.getElementById('relacion');
            
            var boxPartida = document.getElementById('box_partida');
            var boxVacunas = document.getElementById('box_vacunas');
            var inputPartida = document.getElementById('doc_partida');
            var inputVacunas = document.getElementById('doc_vacunas');

            parentescoSelect.innerHTML = "";

            if (selector === 'Adulto') {
                boxPartida.style.display = 'none';
                boxVacunas.style.display = 'none';
                inputPartida.removeAttribute('required');
                inputVacunas.removeAttribute('required');

                var opciones = [
                    {value: "Padre/Madre", text: "Padre/Madre"},
                    {value: "Cónyuge/Pareja", text: "Cónyuge/Pareja"},
                    {value: "Abuelo/a", text: "Abuelo/a"},
                    {value: "Familiar a Cargo", text: "Familiar a Cargo"}
                ];
            } else {
                boxPartida.style.display = 'flex';
                boxVacunas.style.display = 'flex';
                inputPartida.setAttribute('required', 'required');
                inputVacunas.setAttribute('required', 'required');

                var opciones = [
                    {value: "Hijo/a", text: "Hijo/a"},
                    {value: "Tutelado/a", text: "Tutelado/a"}
                ];
            }

            opciones.forEach(function(opt) {
                var el = document.createElement("option");
                el.value = opt.value;
                el.text = opt.text;
                parentescoSelect.appendChild(el);
            });
        }
    </script>
</body>
</html>