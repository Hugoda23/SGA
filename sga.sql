-- ==========================================================
-- NIVEL 1: Tablas independientes (Sin llaves foráneas)
-- ==========================================================

CREATE TABLE USUARIO (
    id_usuario SERIAL PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    estado VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE ROL (
    id_rol SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT
);

CREATE TABLE PERMISO (
    id_permiso SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT
);

CREATE TABLE EDIFICIO (
    id_edificio SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    ubicacion VARCHAR(255)
);

CREATE TABLE CARRERA (
    id_carrera SERIAL PRIMARY KEY,
    nombre_carrera VARCHAR(200) NOT NULL,
    descripcion TEXT
);

CREATE TABLE PERIODO_ACADEMICO (
    id_periodo SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    fecha_inicio DATE,
    fecha_fin DATE,
    estado VARCHAR(50)
);

CREATE TABLE CONFIGURACION (
    id_config SERIAL PRIMARY KEY,
    clave VARCHAR(100) NOT NULL,
    valor TEXT
);

CREATE TABLE ARCHIVO (
    id_archivo SERIAL PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    ruta VARCHAR(255) NOT NULL,
    tipo VARCHAR(50),
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================================
-- NIVEL 2: Tablas que dependen de tablas del Nivel 1
-- ==========================================================

CREATE TABLE USUARIO_ROL (
    id_usuario INT REFERENCES USUARIO(id_usuario) ON DELETE CASCADE,
    id_rol INT REFERENCES ROL(id_rol) ON DELETE CASCADE,
    PRIMARY KEY (id_usuario, id_rol)
);

CREATE TABLE ROL_PERMISO (
    id_rol INT REFERENCES ROL(id_rol) ON DELETE CASCADE,
    id_permiso INT REFERENCES PERMISO(id_permiso) ON DELETE CASCADE,
    PRIMARY KEY (id_rol, id_permiso)
);

CREATE TABLE NOTIFICACION (
    id_notificacion SERIAL PRIMARY KEY,
    id_usuario INT REFERENCES USUARIO(id_usuario) ON DELETE CASCADE,
    mensaje TEXT NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    leido BOOLEAN DEFAULT FALSE
);

CREATE TABLE BITACORA (
    id_bitacora SERIAL PRIMARY KEY,
    id_usuario INT REFERENCES USUARIO(id_usuario),
    accion VARCHAR(255) NOT NULL,
    tabla_afectada VARCHAR(100),
    id_registro INT,
    descripcion TEXT,
    fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE REPORTE_GENERADO (
    id_reporte SERIAL PRIMARY KEY,
    id_usuario INT REFERENCES USUARIO(id_usuario) ON DELETE CASCADE,
    tipo_reporte VARCHAR(100),
    fecha_generacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tiempo_generacion NUMERIC
);

CREATE TABLE ALUMNO (
    id_alumno SERIAL PRIMARY KEY,
    id_usuario INT UNIQUE REFERENCES USUARIO(id_usuario) ON DELETE CASCADE,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    codigo_mineduc VARCHAR(50),
    correo VARCHAR(150),
    telefono VARCHAR(20),
    fecha_nacimiento DATE,
    id_carrera INT REFERENCES CARRERA(id_carrera)
);

CREATE TABLE CATEDRATICO (
    id_catedratico SERIAL PRIMARY KEY,
    id_usuario INT UNIQUE REFERENCES USUARIO(id_usuario) ON DELETE CASCADE,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    especialidad VARCHAR(150),
    correo VARCHAR(150),
    telefono VARCHAR(20)
);

CREATE TABLE CURSO (
    id_curso SERIAL PRIMARY KEY,
    nombre_curso VARCHAR(150) NOT NULL,
    descripcion TEXT,
    id_carrera INT REFERENCES CARRERA(id_carrera) ON DELETE CASCADE
);

CREATE TABLE AULA (
    id_aula SERIAL PRIMARY KEY,
    nombre_aula VARCHAR(100) NOT NULL,
    capacidad INT,
    id_edificio INT REFERENCES EDIFICIO(id_edificio) ON DELETE CASCADE
);

-- ==========================================================
-- NIVEL 3: Tablas que dependen de tablas del Nivel 2
-- ==========================================================

CREATE TABLE PENSUM (
    id_pensum SERIAL PRIMARY KEY,
    id_carrera INT REFERENCES CARRERA(id_carrera) ON DELETE CASCADE,
    id_curso INT REFERENCES CURSO(id_curso) ON DELETE CASCADE,
    grado VARCHAR(50),
    obligatorio BOOLEAN DEFAULT TRUE
);

CREATE TABLE ASIGNACION (
    id_asignacion SERIAL PRIMARY KEY,
    id_catedratico INT REFERENCES CATEDRATICO(id_catedratico),
    id_curso INT REFERENCES CURSO(id_curso),
    id_aula INT REFERENCES AULA(id_aula),
    id_periodo INT REFERENCES PERIODO_ACADEMICO(id_periodo),
    grado VARCHAR(50),
    seccion VARCHAR(20)
);

-- ==========================================================
-- NIVEL 4: Tablas que dependen de ASIGNACION y otros (Nivel 3)
-- ==========================================================

CREATE TABLE TAREA (
    id_tarea SERIAL PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    fecha_entrega DATE,
    id_asignacion INT REFERENCES ASIGNACION(id_asignacion) ON DELETE CASCADE
);

CREATE TABLE INSCRIPCION (
    id_inscripcion SERIAL PRIMARY KEY,
    id_alumno INT REFERENCES ALUMNO(id_alumno) ON DELETE CASCADE,
    id_asignacion INT REFERENCES ASIGNACION(id_asignacion) ON DELETE CASCADE,
    fecha_inscripcion DATE DEFAULT CURRENT_DATE
);

CREATE TABLE HORARIO_DETALLE (
    id_horario SERIAL PRIMARY KEY,
    id_asignacion INT REFERENCES ASIGNACION(id_asignacion) ON DELETE CASCADE,
    dia_semana VARCHAR(20),
    hora_inicio TIME,
    hora_fin TIME
);

CREATE TABLE EVALUACION (
    id_evaluacion SERIAL PRIMARY KEY,
    id_asignacion INT REFERENCES ASIGNACION(id_asignacion) ON DELETE CASCADE,
    unidad_academica INT,
    nombre VARCHAR(100),
    porcentaje NUMERIC(5,2)
);

-- ==========================================================
-- NIVEL 5: Tablas finales (Dependen de INSCRIPCION, EVALUACION, TAREA)
-- ==========================================================

CREATE TABLE ENTREGA_TAREA (
    id_entrega SERIAL PRIMARY KEY,
    id_tarea INT REFERENCES TAREA(id_tarea) ON DELETE CASCADE,
    id_alumno INT REFERENCES ALUMNO(id_alumno) ON DELETE CASCADE,
    archivo VARCHAR(255),
    fecha_entrega TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    calificacion NUMERIC(5,2)
);

CREATE TABLE ASISTENCIA (
    id_asistencia SERIAL PRIMARY KEY,
    id_inscripcion INT REFERENCES INSCRIPCION(id_inscripcion) ON DELETE CASCADE,
    fecha DATE,
    estado VARCHAR(50)
);

CREATE TABLE CALIFICACION_FINAL (
    id_calificacion SERIAL PRIMARY KEY,
    id_inscripcion INT REFERENCES INSCRIPCION(id_inscripcion) ON DELETE CASCADE,
    unidad_academica INT,
    nota_final NUMERIC(5,2),
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE DETALLE_CALIFICACION (
    id_detalle SERIAL PRIMARY KEY,
    id_evaluacion INT REFERENCES EVALUACION(id_evaluacion) ON DELETE CASCADE,
    id_inscripcion INT REFERENCES INSCRIPCION(id_inscripcion) ON DELETE CASCADE,
    nota NUMERIC(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);