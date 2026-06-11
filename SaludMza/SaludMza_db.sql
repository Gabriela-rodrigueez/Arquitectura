
CREATE DATABASE `saludmza_db`;
use saludmza_db;

CREATE TABLE USUARIO (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    DNI VARCHAR(20) NOT NULL UNIQUE,
    Nombre VARCHAR(100) NOT NULL,
    Apellido VARCHAR(100) NOT NULL,
    Email VARCHAR(150) NOT NULL UNIQUE,
    Telefono VARCHAR(50),
    Rango_edad VARCHAR(50) NOT NULL, -- 'Menor', 'Adulto', 'Adulto Mayor'
    Datos_salud TEXT,
    Contrasenia VARCHAR(255) NOT NULL
);

CREATE TABLE CONFIGURACION_NOTIFICACION (
    id_configuracion INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    Canal_preferido VARCHAR(50) DEFAULT 'Email', -- 'WhatsApp' o 'Email'
    Antelacion_horas INT DEFAULT 24, -- 24, 48 o 72
    Alertas_lista_espera BOOLEAN DEFAULT TRUE,
    Alertas_estudios BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario) ON DELETE CASCADE
);

CREATE TABLE COBERTURA_MEDICA (
    id_cobertura INT AUTO_INCREMENT PRIMARY KEY,
    Nombre_OS VARCHAR(150) NOT NULL,
    Tipo VARCHAR(50) NOT null -- 'Prepaga', 'Obra Social', 'Particular'
);

-- Tabla Intermedia Muchos a Muchos: Usuarios y sus Coberturas
CREATE TABLE USUARIO_COBERTURA (
    id_usuario INT NOT NULL,
    id_cobertura INT NOT NULL,
    Nro_afiliado VARCHAR(100),
    Plan VARCHAR(100) NOT NULL,
    PRIMARY KEY (id_usuario, id_cobertura),
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_cobertura) REFERENCES COBERTURA_MEDICA(id_cobertura) ON DELETE CASCADE
);

-- Tabla Autorreferencial: Vínculos Familiares (Tutor / Menor)
CREATE TABLE VINCULO_FAMILIAR (
    id_vinculo INT AUTO_INCREMENT PRIMARY KEY,
    id_tutor INT NOT NULL, -- ID del Tutor
    id_dependiente INT NOT NULL,     -- ID del Menor / Adulto a cargo
    Relacion VARCHAR(50) NOT NULL, -- 'Hijo', 'Adulto a cargo'
    Permiso VARCHAR(50) NOT NULL DEFAULT 'Solo Lectura', -- 'Total', 'Solo Lectura'
    FOREIGN KEY (id_tutor) REFERENCES USUARIO(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_dependiente) REFERENCES USUARIO(id_usuario) ON DELETE CASCADE
);

CREATE TABLE DOCUMENTO_MENOR (
    id_documento INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL, -- ID del Menor al que pertenece el documento
    Tipo_documento VARCHAR(50) NOT NULL, -- 'DNI', 'Partida', 'Carnet', 'Vacunas'
    Url_archivo VARCHAR(255) NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario) ON DELETE CASCADE
);


-- =========================================================================
-- MÓDULO 2: PROFESIONALES, CENTROS Y GESTIÓN DE TURNOS
-- =========================================================================

CREATE TABLE MEDICO (
    id_medico INT AUTO_INCREMENT PRIMARY KEY,
    Matricula VARCHAR(50) NOT NULL UNIQUE,
    Nombre VARCHAR(100) NOT NULL,
    Apellido VARCHAR(100) NOT NULL,
    Especialidad VARCHAR(150) NOT NULL
);

CREATE TABLE CENTRO_SALUD (
    id_centroSalud INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(150) NOT NULL,
    Direccion VARCHAR(255) NOT NULL
);

CREATE TABLE TURNO (
    id_turno INT AUTO_INCREMENT PRIMARY KEY,
    id_medico INT NOT NULL,
    id_centroSalud INT NOT NULL,
    id_paciente INT NULL, -- Puede ser NULL si está disponible
    Fecha_hora DATETIME NOT NULL,
    Estado VARCHAR(50) NOT NULL DEFAULT 'Disponible', -- 'Disponible', 'Reservado', 'Cancelado'
    Check_iN BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (id_medico) REFERENCES MEDICO(id_medico) ON DELETE RESTRICT,
    FOREIGN KEY (id_centroSalud) REFERENCES CENTRO_SALUD(id_centroSalud) ON DELETE RESTRICT,
    FOREIGN KEY (id_paciente) REFERENCES USUARIO(id_usuario) ON DELETE SET NULL
);

CREATE TABLE LISTA_ESPERA (
    id_lista INT AUTO_INCREMENT PRIMARY KEY,
    id_paciente INT NOT NULL,
    id_medico INT NOT NULL,
    id_centroSalud INT NOT NULL,
    fecha_inscripcion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado VARCHAR(50) NOT NULL DEFAULT 'Activa', -- 'Activa', 'Asignada', 'Cancelada'
    FOREIGN KEY (id_paciente) REFERENCES USUARIO(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_medico) REFERENCES MEDICO(id_medico) ON DELETE RESTRICT,
    FOREIGN KEY (id_centroSalud) REFERENCES CENTRO_SALUD(id_centroSalud) ON DELETE RESTRICT
);

-- Tabla Intermedia Muchos a Muchos: Médicos Frecuentes de cada Usuario
CREATE TABLE MEDICO_FRECUENTE (
    id_usuario INT NOT NULL,
    id_medico INT NOT NULL,
    PRIMARY KEY (id_usuario, id_medico),
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_medico) REFERENCES MEDICO(id_medico) ON DELETE CASCADE
);


-- =========================================================================
-- MÓDULO 4 Y 5: HISTORIAL MÉDICO Y STOCK DE FARMACIA
-- =========================================================================

CREATE TABLE ESTUDIO_MEDICO (
    id_estudio INT AUTO_INCREMENT PRIMARY KEY,
    id_paciente INT NOT NULL,
    id_medico_emisor INT NOT NULL,
    Tipo_estudio VARCHAR(100) NOT NULL, -- 'Laboratorio', 'Imagen', 'Alta Complejidad'
    Descripcion TEXT,
    Url_pdf VARCHAR(255) NOT NULL,
    Fecha_emision DATETIME NOT NULL,
    Estado_resultado VARCHAR(50) NOT NULL DEFAULT 'Pendiente', -- 'Pendiente', 'Cargado'
    Cobertura_porcentaje INT DEFAULT 0,
    Monto_pagar DECIMAL(10, 2) DEFAULT 0.00,
    FOREIGN KEY (id_paciente) REFERENCES USUARIO(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_medico_emisor) REFERENCES MEDICO(id_medico) ON DELETE RESTRICT
);

CREATE TABLE RECETA_DIGITAL (
    id_receta INT AUTO_INCREMENT PRIMARY KEY,
    id_paciente INT NOT NULL,
    id_medico_emisor INT NOT NULL,
    Fecha_emision DATETIME NOT NULL,
    Estado VARCHAR(50) NOT NULL DEFAULT 'Activa', -- 'Activa', 'Dispensada', 'Vencida'
    Url_pdf VARCHAR(255),
    FOREIGN KEY (id_paciente) REFERENCES USUARIO(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_medico_emisor) REFERENCES MEDICO(id_medico) ON DELETE RESTRICT
);

CREATE TABLE MEDICAMENTO (
    id_medicamento INT AUTO_INCREMENT PRIMARY KEY,
    id_centroSalud INT NOT NULL, -- Asocia el stock a una sede de farmacia física específica
    Nombre_comercial VARCHAR(150) NOT NULL,
    Droga VARCHAR(150) NOT NULL,
    Stock_actual INT NOT NULL DEFAULT 0,
    FOREIGN KEY (id_centroSalud) REFERENCES CENTRO_SALUD(id_centroSalud) ON DELETE CASCADE
);

-- Tabla Intermedia Muchos a Muchos: El desglose de fármacos dentro de una receta
CREATE TABLE DETALLE_RECETA (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_receta INT NOT NULL,
    id_medicamento INT NOT NULL,
    Cantidad_recetada INT NOT NULL DEFAULT 1,
    Dosis_indicada VARCHAR(255) NOT NULL,
    FOREIGN KEY (id_receta) REFERENCES RECETA_DIGITAL(id_receta) ON DELETE CASCADE,
    FOREIGN KEY (id_medicamento) REFERENCES MEDICAMENTO(id_medicamento) ON DELETE RESTRICT
);



-- 1. Insertar Centros de Salud
INSERT INTO CENTRO_SALUD (Nombre, Direccion) VALUES 
('Sede Central - Hospital Mendoza', 'Av. San Martín 1234, Ciudad'),
('Sede Norte - Centro de Especialidades', 'Ruta 40 Km 12, Las Heras');


-- 2. Insertar Médicos con especialidades que coincidan con los filtros
INSERT INTO MEDICO (Matricula, Nombre, Apellido, Especialidad) VALUES 
('M-55512', 'Juan', 'Pérez', 'Cardiología'),
('M-99823', 'Ana', 'Gómez', 'Traumatología'),
('M-11223', 'Carlos', 'Mendoza', 'Laboratorio'),
('M-44556', 'Laura', 'Estévez', 'Laboratorio');


-- 3. INSERTAR MEDICAMENTOS (Stock físico de farmacia para farmacia.php)
INSERT INTO MEDICAMENTO (id_medicamento, id_centroSalud, Nombre_comercial, Droga, Stock_actual) VALUES 
(1, 1, 'Losartán 50mg', 'Losartán', 45),
(2, 1, 'Amoxicilina 500mg', 'Amoxicilina', 8), -- Poco stock (saldrá en rojo)
(3, 2, 'Ibuprofeno 400mg', 'Ibuprofeno', 120),
(4, 1, 'Paracetamol 500mg', 'Paracetamol', 75);

-- 4. INSERTAR RECETAS DIGITALES (Para la sección de farmacia.php)
INSERT INTO RECETA_DIGITAL (id_receta, id_paciente, id_medico_emisor, Fecha_emision, Estado, Url_pdf) VALUES 
(101, 1, 1, '2026-06-01 10:00:00', 'Activa', 'receta_cardio.pdf'),
(102, 1, 3, '2026-06-05 11:30:00', 'Dispensada', 'receta_pediatria.pdf');


-- 5. VINCULAR LOS DETALLES DE LAS RECETAS
INSERT INTO DETALLE_RECETA (id_receta, id_medicamento, Cantidad_recetada, Dosis_indicated) VALUES 
(101, 1, 2, '1 comprimido cada 12 horas'),
(102, 2, 1, '5ml cada 8 horas por 7 días');


-- Estudios para el Usuario ID = 1
INSERT INTO ESTUDIO_MEDICO (id_paciente, id_medico_emisor, Tipo_estudio, Descripcion, Url_pdf, Fecha_emision, Estado_resultado, Cobertura_porcentaje, Monto_pagar) VALUES 
(1, 4, 'Laboratorio', 'Hemograma Complejo y Perfil Lipídico', 'analisis_sangre_r1.pdf', '2026-06-01 08:00:00', 'Cargado', 100, 0.00),
(1, 2, 'Imagen', 'Radiografía de Tórax (Frente y Perfil)', 'radiografia_torax.pdf', '2026-06-05 14:30:00', 'Cargado', 80, 1500.00),
(1, 1, 'Alta Complejidad', 'Ecocardiograma Doppler Color', 'ecocardiograma_01.pdf', '2026-06-11 10:15:00', 'Cargado', 100, 0.00);

-- Estudios para el Usuario ID = 2 (Copia de respaldo por si tu sesión usa el ID 2)
INSERT INTO ESTUDIO_MEDICO (id_paciente, id_medico_emisor, Tipo_estudio, Descripcion, Url_pdf, Fecha_emision, Estado_resultado, Cobertura_porcentaje, Monto_pagar) VALUES 
(2, 4, 'Laboratorio', 'Análisis Clínico de Rutina y Glucemia', 'analisis_sangre_r1.pdf', '2026-06-02 08:00:00', 'Cargado', 100, 0.00),
(2, 2, 'Imagen', 'Ecografía Abdominal Completa', 'radiografia_torax.pdf', '2026-06-06 11:20:00', 'Cargado', 70, 2100.00);

-- Estudios para el Usuario ID = 3 (Copia de respaldo por si tu sesión usa el ID 3)
INSERT INTO ESTUDIO_MEDICO (id_paciente, id_medico_emisor, Tipo_estudio, Descripcion, Url_pdf, Fecha_emision, Estado_resultado, Cobertura_porcentaje, Monto_pagar) VALUES 
(3, 4, 'Laboratorio', 'Examen de Orina Completa y Uremia', 'analisis_sangre_r1.pdf', '2026-06-03 09:15:00', 'Cargado', 100, 0.00),
(3, 1, 'Alta Complejidad', 'Resonancia Magnética de Rodilla', 'radiografia_torax.pdf', '2026-06-09 16:00:00', 'Cargado', 90, 4500.00);


