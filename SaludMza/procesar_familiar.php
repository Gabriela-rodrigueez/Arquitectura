<?php
session_start();
require 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion'])) {
    $id_tutor = $_SESSION['id_usuario'];
    $accion = $_POST['accion'];

    $nombre = trim($_POST['nombre_hijo']);
    $apellido = trim($_POST['apellido_hijo']);
    $dni = trim($_POST['dni_hijo']);
    $fecha_nac = $_POST['fecha_nac'] ?? null;
    $rango_seleccionado = $_POST['rango_edad']; 
    $relacion_vinculo = $_POST['relacion'];

    // PROCESAMIENTO BIOMÉTRICO DE EDADES (APLICA EXCLUSIVAMENTE AL DAR DE ALTA)
    $rango_edad_final = 'Menor';
    if ($accion === 'insertar' && $fecha_nac) {
        $nacimiento = new DateTime($fecha_nac);
        $hoy = new DateTime();
        $edad = $hoy->diff($nacimiento)->y;

        if ($rango_seleccionado === 'Menor') {
            if ($edad >= 18) { die("Error: Indicó menor de edad pero la fecha corresponde a un adulto."); }
        } else {
            if ($edad < 18) { die("Error: Indicó un adulto a cargo pero la fecha corresponde a un menor."); }
            $rango_edad_final = ($edad >= 60) ? 'Adulto Mayor' : 'Adulto';
        }
    }

    // =========================================================================
    // CASO 1: INSERTAR (NUEVO REGISTRO)
    // =========================================================================
    if ($accion === 'insertar') {
        try {
            $conexion->beginTransaction();

            $sqlUser = "INSERT INTO USUARIO (DNI, Nombre, Apellido, Email, Rango_edad, Contrasenia) 
                        VALUES (:dni, :nom, :ape, :email, :rango, :pass)";
            $stmtU = $conexion->prepare($sqlUser);
            
            $email_dummy = "familiar_" . $dni . "@saludmza.com";
            $pass_dummy = password_hash(uniqid(), PASSWORD_DEFAULT);

            $stmtU->execute([
                ':dni' => $dni, ':nom' => $nombre, ':ape' => $apellido,
                ':email' => $email_dummy, ':rango' => $rango_edad_final, ':pass' => $pass_dummy
            ]);
            $id_dependiente = $conexion->lastInsertId();

            $sqlVinculo = "INSERT INTO VINCULO_FAMILIAR (id_tutor, id_dependiente, Relacion, Permiso) 
                           VALUES (:tutor, :dependiente, :relacion, 'Solo Lectura')";
            $stmtV = $conexion->prepare($sqlVinculo);
            $stmtV->execute([':tutor' => $id_tutor, ':dependiente' => $id_dependiente, ':relacion' => $relacion_vinculo]);

            $sqlDoc = "INSERT INTO DOCUMENTO_MENOR (id_usuario, Tipo_documento, Url_archivo) VALUES (:usuario, :tipo, :url)";
            $stmtD = $conexion->prepare($sqlDoc);

            $doc_dni = $_FILES['doc_dni']['name'] ?? 'sin_archivo.jpg';
            $stmtD->execute([':usuario' => $id_dependiente, ':tipo' => 'DNI', ':url' => $doc_dni]);

            if ($rango_edad_final === 'Menor') {
                $doc_partida = $_FILES['doc_partida']['name'] ?? 'sin_archivo.jpg';
                $doc_vacunas = $_FILES['doc_vacunas']['name'] ?? 'sin_archivo.jpg';
                $stmtD->execute([':usuario' => $id_dependiente, ':tipo' => 'Partida', ':url' => $doc_partida]);
                $stmtD->execute([':usuario' => $id_dependiente, ':tipo' => 'Vacunas', ':url' => $doc_vacunas]);
            }

            $conexion->commit();
            header("Location: grupo_familiar.php?exito=1");
            exit();
        } catch(PDOException $e) {
            $conexion->rollBack();
            header("Location: grupo_familiar.php?error=" . urlencode($e->getMessage()));
            exit();
        }
    }

    // =========================================================================
    // CASO 2: EDITAR (ACTUALIZACIÓN DIRECTA)
    // =========================================================================
    if ($accion === 'editar' && isset($_POST['id_dependiente'])) {
        $id_dependiente = $_POST['id_dependiente'];
        $rango_update = ($rango_seleccionado === 'Menor') ? 'Menor' : 'Adulto';
        
        try {
            $conexion->beginTransaction();

            // Sincronizado nativo con las columnas exactas de tu Base de Datos real
            $sqlUpUser = "UPDATE USUARIO SET DNI = :dni, Nombre = :nom, Apellido = :ape, Rango_edad = :rango 
                          WHERE id_usuario = :id";
            $stmtUpU = $conexion->prepare($sqlUpUser);
            $stmtUpU->execute([
                ':dni' => $dni, ':nom' => $nombre, ':ape' => $apellido,
                ':rango' => $rango_update, ':id' => $id_dependiente
            ]);

            $sqlUpVinculo = "UPDATE VINCULO_FAMILIAR SET Relacion = :relacion 
                             WHERE id_tutor = :tutor AND id_dependiente = :id";
            $stmtUpV = $conexion->prepare($sqlUpVinculo);
            $stmtUpV->execute([':relacion' => $relacion_vinculo, ':tutor' => $id_tutor, ':id' => $id_dependiente]);

            $conexion->commit();
            header("Location: grupo_familiar.php?exito=2");
            exit();
        } catch(PDOException $e) {
            $conexion->rollBack();
            header("Location: grupo_familiar.php?error=" . urlencode($e->getMessage()));
            exit();
        }
    }
}
?>