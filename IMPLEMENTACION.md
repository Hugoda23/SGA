# IMPLEMENTACIÓN — Sistema de Gestión Académica (SGA)

> **⚠️ Documento histórico, congelado al 2026-08-09.** Describe las decisiones del
> bootstrap inicial del proyecto (estructura base, modelos, migraciones). El sistema
> siguió evolucionando bastante después de esta fecha — para la estructura, el setup y
> las funcionalidades **actuales**, usar siempre **`README.md`** como fuente de verdad.
> Este archivo se conserva solo como bitácora de diseño de esa etapa inicial; varios
> detalles puntuales (por ejemplo, conteos de modelos/controladores/migraciones, o la
> mención de `User.php` más abajo — ese modelo se eliminó por completo después) ya no
> reflejan el estado real del código.

> **Fecha de actualización:** 2026-08-09
> **Autor:** Arquitecto Senior / DevSecOps
> **Stack:** Laravel · React + Vite + Tailwind CSS + TW Elements · PostgreSQL · Docker
> **Patrón:** Arquitectura Headless (Desacoplada)

---

## 1. Objetivo

Inicializar la estructura base de un **Sistema de Gestión Académica** bajo una **Arquitectura Desacoplada (Headless)**, donde el backend expone una API RESTful apátrida y el frontend consume dicha API de forma independiente.

---

## 2. Stack Tecnológico

| Capa | Tecnología | Versión |
|---|---|---|
| Backend | Laravel (PHP) | PHP 8.4-FPM |
| Frontend | React + Vite | Node 20-alpine |
| Estilos | Tailwind CSS + TW Elements | v4 (`@tailwindcss/vite`) + v2.0.0 |
| Base de Datos | PostgreSQL | 16-alpine |
| Web Server | Nginx | 1.25-alpine |
| Orquestación | Docker + Docker Compose | v3.9 |
| Gestor PHP | Composer | 2.7 |
| PDF | DomPDF + QrCode | laravel-dompdf + simplesoftwareio/qrcode |
| Gráficos | Recharts | React charts |

---

## 3. Estructura de Archivos

```
/home/hugoda/SGA/
├── docker-compose.yml
├── .env
├── .env.example
├── .gitignore
├── README.md
├── IMPLEMENTACION.md                     ← Este archivo
├── sga.sql                               ← Esquema original de BD
├── docker/
│   ├── php/
│   │   ├── Dockerfile
│   │   └── local.ini
│   ├── nginx/
│   │   └── default.conf
│   └── postgres/
│       └── init.sql
├── backend/
│   ├── app/
│   │   ├── Models/                        ← 31 modelos Eloquent
│   │   │   ├── User.php                   ← Autenticación Sanctum (legacy)
│   │   │   ├── Usuario.php                ← Modelo principal de autenticación SGA
│   │   │   ├── Rol.php
│   │   │   ├── Permiso.php
│   │   │   ├── Alumno.php
│   │   │   ├── Catedratico.php
│   │   │   ├── Curso.php
│   │   │   ├── Carrera.php
│   │   │   ├── Edificio.php
│   │   │   ├── Aula.php
│   │   │   ├── Grado.php                  ← Nuevo (v3.0)
│   │   │   ├── Seccion.php                ← Nuevo (v3.0)
│   │   │   ├── PeriodoAcademico.php
│   │   │   ├── Pensum.php
│   │   │   ├── Asignacion.php
│   │   │   ├── Inscripcion.php
│   │   │   ├── Tarea.php
│   │   │   ├── Unidad.php                 ← Nuevo (v6.0)
│   │   │   ├── Material.php               ← Nuevo (v6.0)
│   │   │   ├── Anuncio.php                ← Nuevo (v6.0)
│   │   │   ├── HorarioDetalle.php
│   │   │   ├── Evaluacion.php
│   │   │   ├── EntregaTarea.php
│   │   │   ├── Asistencia.php
│   │   │   ├── CalificacionFinal.php
│   │   │   ├── DetalleCalificacion.php
│   │   │   ├── Notificacion.php
│   │   │   ├── Bitacora.php
│   │   │   ├── ReporteGenerado.php
│   │   │   ├── Configuracion.php
│   │   │   └── Archivo.php
│   │   ├── Services/
│   │   │   └── NotificacionService.php    ← Envío de notificaciones masivas
│   │   ├── Traits/
│   │   │   └── LogsActivity.php           ← Trait de auditoría automática
│   │   └── Http/
│   │       └── Controllers/
│   │           ├── Controller.php
│   │           └── Api/
│   │               └── V1/                ← 39 controladores API
│   │                   ├── AuthController.php
│   │                   ├── UserManagementController.php
│   │                   ├── UsuarioController.php
│   │                   ├── RolController.php
│   │                   ├── PermisoController.php
│   │                   ├── AlumnoController.php
│   │                   ├── CatedraticoController.php
│   │                   ├── CursoController.php
│   │                   ├── CarreraController.php
│   │                   ├── EdificioController.php
│   │                   ├── AulaController.php
│   │                   ├── GradoController.php           ← Nuevo
│   │                   ├── SeccionController.php         ← Nuevo
│   │                   ├── PeriodoAcademicoController.php
│   │                   ├── PensumController.php
│   │                   ├── AsignacionController.php
│   │                   ├── InscripcionController.php
│   │                   ├── TareaController.php
│   │                   ├── UnidadController.php          ← Nuevo (v6.0)
│   │                   ├── ConfiguracionCursoController.php ← Nuevo (v6.0)
│   │                   ├── MaterialController.php        ← Nuevo (v6.0)
│   │                   ├── AnuncioController.php         ← Nuevo (v6.0)
│   │                   ├── AlumnoCursoController.php     ← Nuevo (v6.0)
│   │                   ├── HorarioDetalleController.php
│   │                   ├── EvaluacionController.php
│   │                   ├── EntregaTareaController.php
│   │                   ├── AsistenciaController.php
│   │                   ├── CalificacionFinalController.php
│   │                   ├── DetalleCalificacionController.php
│   │                   ├── NotificacionController.php
│   │                   ├── BitacoraController.php
│   │                   ├── ReporteGeneradoController.php
│   │                   ├── ConfiguracionController.php
│   │                   ├── ArchivoController.php
│   │                   ├── DashboardController.php       ← Nuevo
│   │                   ├── MisCursosController.php       ← Nuevo
│   │                   ├── MisTareasController.php       ← Nuevo
│   │                   ├── RegistroCalificacionesController.php ← Nuevo
│   │                   └── PdfReportController.php       ← Nuevo
│   ├── database/
│   │   ├── migrations/                    ← 47 migraciones total
│   │   │   ├── 0001_01_01_000000_create_users_table.php
│   │   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   │   ├── 0001_01_01_000002_create_jobs_table.php
│   │   │   ├── 2026_07_05_055515_create_personal_access_tokens_table.php
│   │   │   ├── 2026_07_05_100000_create_usuario_table.php
│   │   │   ├── 2026_07_05_100001_create_rol_table.php
│   │   │   ├── 2026_07_05_100002_create_permiso_table.php
│   │   │   ├── 2026_07_05_100003_create_edificio_table.php
│   │   │   ├── 2026_07_05_100004_create_carrera_table.php
│   │   │   ├── 2026_07_05_100005_create_periodo_academico_table.php
│   │   │   ├── 2026_07_05_100006_create_configuracion_table.php
│   │   │   ├── 2026_07_05_100007_create_archivo_table.php
│   │   │   ├── 2026_07_05_100010_create_usuario_rol_table.php
│   │   │   ├── 2026_07_05_100011_create_rol_permiso_table.php
│   │   │   ├── 2026_07_05_100012_create_notificacion_table.php
│   │   │   ├── 2026_07_05_100013_create_bitacora_table.php
│   │   │   ├── 2026_07_05_100014_create_reporte_generado_table.php
│   │   │   ├── 2026_07_05_100015_create_alumno_table.php
│   │   │   ├── 2026_07_05_100016_create_catedratico_table.php
│   │   │   ├── 2026_07_05_100017_create_curso_table.php
│   │   │   ├── 2026_07_05_100018_create_aula_table.php
│   │   │   ├── 2026_07_05_100020_create_pensum_table.php
│   │   │   ├── 2026_07_05_100021_create_asignacion_table.php
│   │   │   ├── 2026_07_05_100030_create_tarea_table.php
│   │   │   ├── 2026_07_05_100031_create_inscripcion_table.php
│   │   │   ├── 2026_07_05_100032_create_horario_detalle_table.php
│   │   │   ├── 2026_07_05_100033_create_evaluacion_table.php
│   │   │   ├── 2026_07_05_100040_create_entrega_tarea_table.php
│   │   │   ├── 2026_07_05_100041_create_asistencia_table.php
│   │   │   ├── 2026_07_05_100042_create_calificacion_final_table.php
│   │   │   ├── 2026_07_05_100043_create_detalle_calificacion_table.php
│   │   │   ├── 2026_07_05_110000_add_password_change_required_to_usuario_table.php
│   │   │   ├── 2026_07_05_120000_create_grado_table.php     ← Nuevo
│   │   │   ├── 2026_07_05_120001_create_seccion_table.php   ← Nuevo
│   │   │   ├── 2026_07_05_120002_update_asignacion_add_grado_seccion_fk.php ← Nuevo
│   │   │   ├── 2026_07_08_100000_update_fecha_entrega_to_datetime_in_tarea_table.php
│   │   │   ├── 2026_07_08_100001_add_nombre_original_to_entrega_tarea_table.php
│   │   │   ├── 2026_08_09_100000_create_unidad_table.php    ← Nuevo (v6.0)
│   │   │   ├── 2026_08_09_100001_add_id_unidad_to_tarea_table.php ← Nuevo (v6.0)
│   │   │   ├── 2026_08_09_100002_create_material_table.php  ← Nuevo (v6.0)
│   │   │   ├── 2026_08_09_100003_create_anuncio_table.php   ← Nuevo (v6.0)
│   │   │   ├── 2026_08_09_100004_add_permitir_link_to_tarea_table.php ← Nuevo (v6.0)
│   │   │   └── 2026_08_09_100005_add_link_to_entrega_tarea_table.php ← Nuevo (v6.0)
│   │   └── seeders/
│   │       ├── DatabaseSeeder.php
│   │       ├── RoleSeeder.php
│   │       ├── TestDataSeeder.php
│   │       └── ComprehensiveSeeder.php    ← Nuevo (datos masivos con Faker)
│   ├── routes/
│   │   └── api.php                        ← 39 controladores, ~280+ endpoints
│   └── config/
│       └── auth.php                       ← Apunta a App\Models\Usuario
└── frontend/
    ├── src/
    │   ├── api/axios.js
    │   ├── context/AuthContext.jsx
    │   ├── components/
    │   │   ├── Layout.jsx
    │   │   ├── DataTable.jsx
    │   │   ├── FormInput.jsx
    │   │   ├── Modal.jsx                        ← Nuevo (TW Elements)
    │   │   └── ProtectedRoute.jsx
    │   ├── lib/
    │   │   ├── twClasses.js                     ← Nuevo: presets compartidos TW Elements
    │   │   └── twElements.js                    ← Nuevo: init de componentes JS de TW Elements
    │   ├── pages/
    │   │   ├── Login.jsx
    │   │   ├── ChangePassword.jsx
    │   │   ├── Dashboard.jsx
    │   │   ├── alumnos/ (List + Form)
    │   │   ├── catedraticos/ (List + Form + MisCursos + RegistroCalificaciones* + ConfiguracionCurso*)
    │   │   │   └── curso/ (MaterialesTab + AnunciosTab + EvaluacionesTab) ← Nuevo (v6.0)
    │   │   ├── alumno/ (MisCursosAlumno + CursoAlumno)      ← Nuevo (v6.0)
    │   │   ├── cursos/ (List + Form)
    │   │   ├── carreras/ (List + Form)
    │   │   ├── edificios/ (List + Form)
    │   │   ├── aulas/ (List + Form)
    │   │   ├── grados/ (GradoList)
    │   │   ├── secciones/ (SeccionList)
    │   │   ├── periodos/ (List + Form)
    │   │   ├── pensum/ (List + Form)
    │   │   ├── asignaciones/ (List + Form)
    │   │   ├── inscripciones/ (List + Form)
    │   │   ├── tareas/ (List + Form)
    │   │   ├── entregas/ (EntregasList)
    │   │   ├── evaluaciones/ (List + Form)
    │   │   ├── roles/ (List + Form)
    │   │   ├── permisos/ (List + Form)
    │   │   ├── configuracion/ (List + Form)
    │   │   ├── notificaciones/ (NotificacionList)
    │   │   ├── admin/ (UserList + AuditoriaList)
    │   │   └── reportes/ (ReporteActas + ReporteNotas + ReporteConstancias + ReporteGeneradoList)
    │   ├── App.jsx
    │   ├── main.jsx                          ← initTwElements() en arranque
    │   └── index.css                         ← Tema completo TW Elements (paleta, sombras, animaciones)
    └── vite.config.js
```

---

## 4. Infraestructura Docker

### 4.1 Servicios (`docker-compose.yml`)

4 servicios orquestados con red interna `sga_network`:

| Servicio | Puerto Host | Puerto Contenedor | Imagen |
|---|---|---|---|
| `db` (PostgreSQL) | 5433 | 5432 | postgres:16-alpine |
| `backend` (PHP-FPM) | — | 9000 | Dockerfile personalizado |
| `webserver` (Nginx) | 8081 | 80 | nginx:1.25-alpine |
| `frontend` (Vite) | 3002 | 5173 | node:20-alpine |

**Decisiones clave:**
- PostgreSQL healthcheck: backend no arranca hasta que la BD esté lista
- Volumen persistente `pgdata` para datos
- `CHOKIDAR_USEPOLLING=true` para hot-reload en Docker
- CORS permitido desde `http://localhost:3002`

### 4.2 Nginx (`default.conf`)

Reverse proxy que sirve Laravel vía PHP-FPM en `backend:9000`. Incluye:
- Cabeceras CORS (`Access-Control-Allow-Origin: http://localhost:3002`)
- Manejo de preflight OPTIONS con `204 No Content`
- Denegación de archivos ocultos (`.env`, `.git`)

---

## 5. Esquema de Base de Datos

48 migraciones que crean 36 tablas en 5 niveles por dependencias + 3 tablas nuevas del módulo de configuración de curso. Incluye 3 tablas de Laravel core (`users`, `cache`, `jobs`) + 3 de Sanctum (`personal_access_tokens`, `cache_locks`, `job_batches`, `failed_jobs`).

### Nivel 1 — Tablas independientes

| Migración | Tabla | PK |
|---|---|---|
| `100000_create_usuario_table` | `usuario` | `id_usuario` |
| `100001_create_rol_table` | `rol` | `id_rol` |
| `100002_create_permiso_table` | `permiso` | `id_permiso` |
| `100003_create_edificio_table` | `edificio` | `id_edificio` |
| `100004_create_carrera_table` | `carrera` | `id_carrera` |
| `100005_create_periodo_academico_table` | `periodo_academico` | `id_periodo` |
| `100006_create_configuracion_table` | `configuracion` | `id_config` |
| `100007_create_archivo_table` | `archivo` | `id_archivo` |
| `120000_create_grado_table` | `grado` | `id_grado` |
| `120001_create_seccion_table` | `seccion` | `id_seccion` |

### Nivel 2 — Dependen de Nivel 1

| Migración | Tabla | FK |
|---|---|---|
| `100010_create_usuario_rol_table` | `usuario_rol` | `id_usuario`, `id_rol` |
| `100011_create_rol_permiso_table` | `rol_permiso` | `id_rol`, `id_permiso` |
| `100012_create_notificacion_table` | `notificacion` | `id_usuario` |
| `100013_create_bitacora_table` | `bitacora` | `id_usuario` |
| `100014_create_reporte_generado_table` | `reporte_generado` | `id_usuario` |
| `100015_create_alumno_table` | `alumno` | `id_usuario`, `id_carrera` |
| `100016_create_catedratico_table` | `catedratico` | `id_usuario` |
| `100017_create_curso_table` | `curso` | `id_carrera` |
| `100018_create_aula_table` | `aula` | `id_edificio` |

### Nivel 3 — Dependen de Nivel 2

| Migración | Tabla | FK |
|---|---|---|
| `100020_create_pensum_table` | `pensum` | `id_carrera`, `id_curso` |
| `100021_create_asignacion_table` | `asignacion` | `id_catedratico`, `id_curso`, `id_aula`, `id_periodo` |

### Nivel 4 — Dependen de Nivel 3

| Migración | Tabla | FK |
|---|---|---|
| `100030_create_tarea_table` | `tarea` | `id_asignacion` |
| `100031_create_inscripcion_table` | `inscripcion` | `id_alumno`, `id_asignacion` |
| `100032_create_horario_detalle_table` | `horario_detalle` | `id_asignacion` |
| `100033_create_evaluacion_table` | `evaluacion` | `id_asignacion` |

### Nivel 5 — Dependen de Nivel 4

| Migración | Tabla | FK |
|---|---|---|
| `100040_create_entrega_tarea_table` | `entrega_tarea` | `id_tarea`, `id_alumno` |
| `100041_create_asistencia_table` | `asistencia` | `id_inscripcion` |
| `100042_create_calificacion_final_table` | `calificacion_final` | `id_inscripcion` |
| `100043_create_detalle_calificacion_table` | `detalle_calificacion` | `id_evaluacion`, `id_inscripcion` |

### Migración especial

| Migración | Acción |
|---|---|
| `110000_add_password_change_required` | Agrega columna `password_change_required` a `usuario` |
| `120002_update_asignacion_add_grado_seccion_fk` | Agrega FK `id_grado`, `id_seccion` a `asignacion`, elimina columnas texto |
| `2026_08_09_100001_add_id_unidad_to_tarea_table` | Agrega FK `id_unidad` (semana) a `tarea` |
| `2026_08_09_100004_add_permitir_link_to_tarea_table` | Agrega `permitir_link` (bool) a `tarea` |
| `2026_08_09_100005_add_link_to_entrega_tarea_table` | Agrega `link` (varchar 500) a `entrega_tarea` |
| `2026_08_14_100001_make_fecha_entrega_nullable_in_entrega_tarea_table` | Hace nullable `fecha_entrega` de `entrega_tarea` (permite borradores sin fecha hasta presentar) |

### Nuevas tablas del módulo de configuración de curso (v6.0)

| Migración | Tabla | PK | FK |
|---|---|---|---|
| `2026_08_09_100000_create_unidad_table` | `unidad` | `id_unidad` | `id_asignacion` |
| `2026_08_09_100002_create_material_table` | `material` | `id_material` | `id_asignacion`, `id_archivo`, `id_unidad` |
| `2026_08_09_100003_create_anuncio_table` | `anuncio` | `id_anuncio` | `id_asignacion` |

---

## 6. Sistema de Autenticación

### 6.1 Modelo de Autenticación

El modelo `App\Models\Usuario` (`usuario` table) es el responsable de la autenticación vía Sanctum. Configurado en `config/auth.php` como modelo por defecto.

### 6.2 Roles del Sistema

5 roles precargados vía `RoleSeeder`:

| Rol | Descripción |
|---|---|
| `admin` | Administrador del sistema |
| `director` | Director académico |
| `secretaria` | Secretaría |
| `catedratico` | Catedrático / docente |
| `alumno` | Alumno / estudiante |

### 6.3 Login multimodal

`POST /api/v1/auth/login`

Parámetros:
- `codigo` — username del usuario
- `password` — contraseña
- `tipo` — `administrador` | `docente` | `estudiante`

El backend valida que el rol del usuario sea compatible con el tipo seleccionado:
- `administrador` → admin, director, secretaria
- `docente` → catedratico
- `estudiante` → alumno

### 6.4 Generación Automática de Contraseñas

| Tipo de Usuario | Fórmula |
|---|---|
| Alumno | `primera_letra_apellido + nombre(s) sin espacios + año_nacimiento` |
| Catedrático | `primera_letra_apellido + nombre(s) sin espacios` |
| Admin/Director/Secretaria | Asignada manualmente por el admin |

Al crear el usuario, se marca `password_change_required = true`. La contraseña generada se devuelve como `password_temporal`.

---

## 7. API RESTful — Endpoints

### 7.1 Autenticación

| Método | Endpoint | Descripción |
|---|---|---|
| POST | `/api/v1/auth/login` | Login multimodal (retorna token + `password_change_required`) |
| POST | `/api/v1/auth/logout` | Cerrar sesión (revoca token) |
| GET | `/api/v1/auth/me` | Datos del usuario autenticado |
| POST | `/api/v1/auth/change-password` | Cambiar contraseña |

### 7.2 Dashboard

| Método | Endpoint | Descripción |
|---|---|---|
| GET | `/api/v1/dashboard/stats` | KPIs (alumnos, catedráticos, cursos, inscripciones + chart) |

### 7.3 Catedrático

| Método | Endpoint | Descripción |
|---|---|---|
| GET | `/api/v1/catedratico/mis-cursos` | Cursos del catedrático autenticado con horarios y tareas |

### 7.4 Registro de Calificaciones

| Método | Endpoint | Descripción |
|---|---|---|
| GET | `/api/v1/registro-calificaciones/{id_asignacion}` | Cuadro completo de notas |
| POST | `/api/v1/registro-calificaciones/{id_asignacion}/guardar` | Guardar notas en masa |
| POST | `/api/v1/registro-calificaciones/{id_asignacion}/evaluaciones` | Crear columna de evaluación |
| DELETE | `/api/v1/registro-calificaciones/evaluaciones/{id_evaluacion}` | Eliminar evaluación |

### 7.5 Reportes PDF

| Método | Endpoint | Descripción |
|---|---|---|
| GET | `/api/v1/reportes/pdf/boletin/{id}` | Boleta de calificaciones |
| GET | `/api/v1/reportes/pdf/kardex/{id}` | Kardex académico |
| GET | `/api/v1/reportes/pdf/acta/{id}` | Acta de curso |
| GET | `/api/v1/reportes/pdf/bitacora` | Bitácora de auditoría |
| GET | `/api/v1/reportes/pdf/constancia/{id}` | Constancia de estudios |
| GET | `/api/v1/reportes/pdf/avance-programatico/{id_asignacion}` | Avance programático (vista blade, v6.0) |

### 7.5.1 Avance Programático / Unidades (v6.0)

| Método | Endpoint | Descripción |
|---|---|---|
| GET | `/api/v1/unidades/por-asignacion/{id_asignacion}` | Unidades/semanas de una asignación |
| POST | `/api/v1/unidades` | Crear unidad/semana |
| GET | `/api/v1/unidades/{id_unidad}` | Detalle de unidad |
| PUT/PATCH | `/api/v1/unidades/{id_unidad}` | Actualizar unidad (incluye cambio de estado) |
| DELETE | `/api/v1/unidades/{id_unidad}` | Eliminar unidad |

### 7.5.2 Configuración de Curso (catedrático, v6.0)

| Método | Endpoint | Descripción |
|---|---|---|
| GET | `/api/v1/catedratico/configuracion-curso/{id_asignacion}` | Payload consolidado: asignación, horarios, alumnos, unidades, tareas, materiales, anuncios, evaluaciones |

### 7.5.3 Materiales del Curso (v6.0)

| Método | Endpoint | Descripción |
|---|---|---|
| GET | `/api/v1/materiales/por-asignacion/{id_asignacion}` | Listar materiales |
| POST | `/api/v1/materiales` | Crear material (archivo o enlace) |
| PUT/PATCH | `/api/v1/materiales/{id_material}` | Actualizar material |
| DELETE | `/api/v1/materiales/{id_material}` | Eliminar material |
| GET | `/api/v1/archivos/{archivo}/descargar` | Descarga de archivo de material |

### 7.5.4 Anuncios del Curso (v6.0)

| Método | Endpoint | Descripción |
|---|---|---|
| GET | `/api/v1/anuncios/por-asignacion/{id_asignacion}` | Listar anuncios |
| POST | `/api/v1/anuncios` | Crear anuncio (notifica a inscritos) |
| PUT/PATCH | `/api/v1/anuncios/{id_anuncio}` | Actualizar anuncio |
| DELETE | `/api/v1/anuncios/{id_anuncio}` | Eliminar anuncio |

### 7.5.5 Vista Alumno (v6.0)

| Método | Endpoint | Descripción |
|---|---|---|
| GET | `/api/v1/alumno/mis-cursos` | Cursos del alumno autenticado (inscripciones) |
| GET | `/api/v1/alumno/curso/{id_asignacion}` | Detalle del curso (unidades, tareas + mi entrega, materiales, anuncios, evaluaciones) |
| POST | `/api/v1/alumno/curso/{id_asignacion}/entregar/{id_tarea}` | Entregar tarea (archivo o enlace, valida inscripción) |
| GET | `/api/v1/mis-tareas` | Tareas del alumno con estado de entrega |

### 7.5.6 Entregas por enlace (v6.0)

| Método | Endpoint | Descripción |
|---|---|---|
| POST | `/api/v1/entregas-tarea/subir-archivo` | Alumno: subir archivo o enlace (si la tarea permite link) |
| GET | `/api/v1/entregas-tarea/por-tarea/{id_tarea}` | Profesor: entregas por tarea (archivo + link) |
| POST | `/api/v1/entregas-tarea/calificar/{id_entrega}` | Calificar entrega |

### 7.6 Gestión de Usuarios Staff

| Método | Endpoint | Descripción |
|---|---|---|
| POST | `/api/v1/usuarios/admin` | Crear admin/director/secretaria |
| GET | `/api/v1/usuarios/admin` | Listar usuarios staff |
| PUT | `/api/v1/usuarios/{usuario}/password` | Cambiar contraseña de cualquier usuario |
| PATCH | `/api/v1/usuarios/{usuario}/estado` | Activar/desactivar cuenta |

### 7.7 Recursos CRUD (apiResource)

Todos protegidos con `auth:sanctum`:

| Endpoint | Controlador |
|---|---|
| `/api/v1/usuarios` | UsuarioController |
| `/api/v1/roles` | RolController |
| `/api/v1/permisos` | PermisoController |
| `/api/v1/edificios` | EdificioController |
| `/api/v1/carreras` | CarreraController |
| `/api/v1/aulas` | AulaController |
| `/api/v1/grados` | GradoController |
| `/api/v1/secciones` | SeccionController |
| `/api/v1/periodos-academicos` | PeriodoAcademicoController |
| `/api/v1/configuraciones` | ConfiguracionController |
| `/api/v1/archivos` | ArchivoController |
| `/api/v1/notificaciones` | NotificacionController |
| `/api/v1/bitacoras` | BitacoraController |
| `/api/v1/reportes-generados` | ReporteGeneradoController |
| `/api/v1/alumnos` | AlumnoController |
| `/api/v1/catedraticos` | CatedraticoController |
| `/api/v1/cursos` | CursoController |
| `/api/v1/pensums` | PensumController |
| `/api/v1/asignaciones` | AsignacionController |
| `/api/v1/tareas` | TareaController |
| `/api/v1/inscripciones` | InscripcionController |
| `/api/v1/horarios` | HorarioDetalleController |
| `/api/v1/evaluaciones` | EvaluacionController |
| `/api/v1/entregas-tarea` | EntregaTareaController |
| `/api/v1/asistencias` | AsistenciaController |
| `/api/v1/calificaciones-finales` | CalificacionFinalController |
| `/api/v1/detalles-calificacion` | DetalleCalificacionController |

---

## 8. Trait LogsActivity (Auditoría Automática)

El trait `App\Traits\LogsActivity` se activa en 6 modelos mediante eventos Eloquent:

| Modelo | Eventos auditados |
|---|---|
| Alumno | CREAR, ACTUALIZAR, ELIMINAR |
| Catedrático | CREAR, ACTUALIZAR, ELIMINAR |
| Curso | CREAR, ACTUALIZAR, ELIMINAR |
| Grado | CREAR, ACTUALIZAR, ELIMINAR |
| Sección | CREAR, ACTUALIZAR, ELIMINAR |
| Usuario | CREAR, ACTUALIZAR, ELIMINAR |

Cada acción registra en `bitacora`: usuario, acción, tabla afectada, ID del registro, descripción y fecha/hora.

---

## 9. Seeders

| Seeder | Descripción |
|---|---|
| `RoleSeeder` | Crea los 5 roles del sistema (admin, director, secretaria, catedratico, alumno) |
| `TestDataSeeder` | Usuarios de prueba (admin, 2 alumnos, 1 catedrático, edificios, aulas, cursos, periodos, bitácora) |
| `ComprehensiveSeeder` | Población masiva con Faker: 50 alumnos, 15 catedráticos, 10 cursos, 5 carreras, 20 asignaciones, ~100 inscripciones, ~300 asistencias, + evaluaciones, tareas, calificaciones, notificaciones, bitácora |

### Credenciales de Prueba

| Usuario | Rol | Código | Contraseña | ¿Forzar cambio? |
|---|---|---|---|---|
| Admin | admin | `admin` | `admin123` | No |
| Juan Pérez | alumno | `ALG001` | `pjuan2005` | Sí |
| María López | catedratico | `CAT001` | `lmaria` | Sí |
| Hugo Alejandro Mendez Diaz | alumno | `32032345` | `mhugoalejandro2026` | Sí |

---

## 10. Frontend — React + Vite + Tailwind CSS

### 10.1 Rutas del Frontend

| Ruta | Página | Roles |
|---|---|---|
| `/login` | Login | Público |
| `/cambiar-contrasena` | ChangePassword | Autenticado |
| `/` | Dashboard | Todos |
| `/alumnos` | AlumnoList | admin, director, secretaria |
| `/alumnos/nuevo`, `/alumnos/:id` | AlumnoForm | admin, director, secretaria |
| `/catedraticos` | CatedraticoList | admin, director, secretaria |
| `/catedraticos/nuevo`, `/catedraticos/:id` | CatedraticoForm | admin, director, secretaria |
| `/mis-cursos` | MisCursos | catedratico |
| `/registro-calificaciones` | RegistroCalificacionesIndex | catedratico |
| `/registro-calificaciones/:id_asignacion` | RegistroCalificaciones | catedratico |
| `/configuracion-curso` | ConfiguracionCursoIndex | catedratico |
| `/configuracion-curso/:id_asignacion` | ConfiguracionCurso | catedratico |
| `/mis-cursos-alumno` | MisCursosAlumno | alumno |
| `/mis-cursos-alumno/:id_asignacion` | CursoAlumno | alumno |
| `/cursos` | CursoList | admin, director |
| `/cursos/nuevo`, `/cursos/:id` | CursoForm | admin, director |
| `/carreras` | CarreraList | admin, director |
| `/carreras/nuevo`, `/carreras/:id` | CarreraForm | admin, director |
| `/edificios` | EdificioList | admin, director |
| `/edificios/nuevo`, `/edificios/:id` | EdificioForm | admin, director |
| `/aulas` | AulaList | admin, director |
| `/aulas/nuevo`, `/aulas/:id` | AulaForm | admin, director |
| `/grados` | GradoList | admin, director |
| `/secciones` | SeccionList | admin, director |
| `/periodos` | PeriodoList | admin, director |
| `/periodos/nuevo`, `/periodos/:id` | PeriodoForm | admin, director |
| `/pensum` | PensumList | admin, director |
| `/pensum/nuevo`, `/pensum/:id` | PensumForm | admin, director |
| `/asignaciones` | AsignacionList | admin, director, secretaria |
| `/asignaciones/nuevo`, `/asignaciones/:id` | AsignacionForm | admin, director, secretaria |
| `/inscripciones` | InscripcionList | admin, director, secretaria |
| `/inscripciones/nuevo` | InscripcionForm | admin, director, secretaria |
| `/tareas` | TareaList | admin, director |
| `/tareas/nuevo`, `/tareas/:id` | TareaForm | admin, director |
| `/evaluaciones` | EvaluacionList | admin, director |
| `/evaluaciones/nuevo`, `/evaluaciones/:id` | EvaluacionForm | admin, director |
| `/reportes/actas` | ReporteActas | admin, director |
| `/reportes/notas` | ReporteNotas | admin, director |
| `/reportes/constancias` | ReporteConstancias | admin, director |
| `/auditoria` | AuditoriaList | admin |
| `/admin/usuarios` | UserList | admin |

### 10.2 Componentes Compartidos

- **`DataTable.jsx`** — Tabla genérica con búsqueda, columnas configurables, botones de acción (estilos TW Elements via `twClasses`)
- **`FormInput.jsx`** — Input genérico (text, select, textarea, number, email, date) con estilos TW Elements
- **`Modal.jsx`** — Modal reutilizable estilo TW Elements (tamaños sm–5xl, overlay, cierre con ESC/backdrop)
- **`Layout.jsx`** — Sidebar responsivo + topbar con navegación filtrada por rol e iconos SVG
- **`ProtectedRoute.jsx`** — Ruta protegida por autenticación y roles

### 10.3 Flujo de Autenticación Frontend

1. `AuthContext` provee `user`, `login()`, `logout()`, `changePassword()`, `hasRole()`
2. En mount, valida token guardado via `GET /api/v1/auth/me`
3. Login con selector de tipo (Administrador/Docente/Estudiante)
4. Si `password_change_required`, redirige a `/cambiar-contrasena`
5. Interceptor de axios agrega `Authorization: Bearer` automáticamente
6. En 401, limpia token y redirige al login

### 10.4 Pantallas por Rol

| Rol | Pantallas disponibles |
|---|---|
| admin | Dashboard, Usuarios, Cursos, Infraestructura (Edificios, Aulas, Grados, Secciones), Reportes (Actas, Notas, Constancias), Auditoría, Catedráticos, Alumnos, Carreras, Periodos, Pensum, Asignaciones, Inscripciones, Tareas, Evaluaciones |
| director | Dashboard, Cursos, Infraestructura, Reportes, Catedráticos, Alumnos, Carreras, Periodos, Pensum, Asignaciones, Inscripciones, Tareas, Evaluaciones |
| secretaria | Dashboard, Catedráticos, Alumnos, Aulas, Asignaciones, Inscripciones |
| catedratico | Dashboard (redirect a Mis Cursos), Mis Cursos, Registro de Calificaciones, Configuración de Curso (5 pestañas: Avance, Tareas, Materiales, Anuncios, Evaluaciones), Entregas de Tareas |
| alumno | Dashboard, Mis Cursos (alumno) con detalle por curso, Mis Tareas |

---

## 11. URLs de Acceso

| Servicio | URL | Contenedor |
|---|---|---|
| API Laravel | http://localhost:8081/api | `sga_nginx` → `sga_backend` |
| React App | http://localhost:3002 | `sga_frontend` |
| PostgreSQL | localhost:5433 | `sga_db` |

---

## 12. Comandos de Inicialización

```bash
# Primera vez
cd /home/hugoda/SGA
cp .env.example .env
docker compose build
docker compose up -d

# Migraciones y seeders
docker compose exec backend php artisan migrate
docker compose exec backend php artisan db:seed

# Verificar estado
docker compose ps
docker compose logs -f
```

---

## 13. Historial de Cambios

| Fecha | Versión | Descripción |
|---|---|---|
| 2026-07-04 | 1.0.0 | Inicialización completa de infraestructura SGA |
| 2026-07-05 | 1.1.0 | Verificación de dependencias y comandos de inicialización de Sanctum |
| 2026-07-05 | 2.0.0 | Migraciones completas del esquema `sga.sql` (27 tablas) |
| 2026-07-05 | 2.0.0 | Modelos Eloquent + Controladores API Resource (26 cada uno) |
| 2026-07-05 | 2.0.0 | Sistema de autenticación con Sanctum (login por código + password) |
| 2026-07-05 | 2.0.0 | Roles: admin, director, secretaria, catedratico, alumno |
| 2026-07-05 | 2.0.0 | Generación automática de contraseñas para alumnos y catedráticos |
| 2026-07-05 | 2.0.0 | Gestión de usuarios staff (admin crea admin/director/secretaria) |
| 2026-07-05 | 2.0.0 | Frontend completo: ~15 módulos CRUD + auth + layout responsivo |
| 2026-07-05 | 2.1.0 | Selector de tipo de usuario en login con validación backend |
| 2026-07-05 | 2.1.0 | Botón mostrar/ocultar contraseña en login y cambio de contraseña |
| 2026-07-05 | 2.1.0 | Forzar cambio de contraseña en primer login (`password_change_required`) |
| 2026-07-05 | 2.2.0 | Resolución de errores de permisos y configuración de CORS |
| 2026-07-05 | 2.3.0 | Creación de `ComprehensiveSeeder` para poblar masivamente toda la BD con Faker |
| 2026-07-05 | 2.4.0 | Modernización UI de Edificios y Aulas (modales consistentes y Tailwind CSS) |
| 2026-07-05 | 2.5.0 | Implementación de generación de reportes PDF centralizada en el Backend |
| 2026-07-05 | 2.5.1 | Integración del Trait `LogsActivity` para registro automático de eventos en Bitácora |
| 2026-07-08 | 3.0.0 | Nuevas entidades: Grado y Sección con CRUD completo (backend + frontend) |
| 2026-07-08 | 3.0.0 | DashboardController con KPIs y gráfico de alumnos por carrera |
| 2026-07-08 | 3.0.0 | MisCursosController: catálogo de cursos asignados al catedrático autenticado |
| 2026-07-08 | 3.0.0 | RegistroCalificacionesController: libro de calificaciones con cálculo de promedio ponderado |
| 2026-07-08 | 3.0.0 | Reportes PDF: boletín, kardex (con hash SHA-256), acta, bitácora, constancia |
| 2026-07-08 | 3.0.0 | Frontend: Mis Cursos, Registro de Calificaciones, Configuración de Curso, Grados, Secciones |
| 2026-07-08 | 3.0.0 | Frontend: Reportes PDF (Actas, Notas/Boletín/Kardex, Constancias) |
| 2026-07-08 | 3.0.0 | Frontend: Auditoría (Bitácora con filtros y descarga PDF) |
| 2026-07-08 | 3.0.0 | Migración de columnas texto `grado`/`seccion` a FK en `asignacion` |
| 2026-07-08 | 3.0.0 | 28 modelos Eloquent, 33 controladores API, 35 migraciones, 4 seeders |
| 2026-07-08 | 3.1.0 | **Notificaciones**: NotificacionService, EnviarNotificacionJob, EnviarNotificacionMultipleJob, GenerarNotificaciones (schedule 6 AM), NotificacionController (list/marcar leído/todas), frontend NotificacionList con filtro + campana en Layout con contador no leídas y polling 30s |
| 2026-07-08 | 3.1.0 | **Roles/Permisos/Configuración**: CRUD frontend completo con DataTable/FormInput |
| 2026-07-08 | 3.1.0 | **HorarioDetalle**: Sección integrada en AsignacionForm (agregar/eliminar horarios con día, hora inicio/fin) |
| 2026-07-08 | 3.1.0 | **Asistencia**: Modal en RegistroCalificaciones con date picker + toggles Presente/Ausente/Justificado, guardado batch via updateOrCreate, PDF descargable con blob autenticado |
| 2026-07-08 | 3.1.0 | **Tareas**: Campo `fecha_entrega` cambiado de DATE a DATETIME, migración actualizada |
| 2026-07-08 | 3.1.0 | **EntregaTarea**: Upload real con Storage (disco public), validación MIME (PDF,ZIP,RAR,Word,Excel,PPT,imágenes,TXT), límite 20MB, columna `nombre_original` para mostrar nombre del archivo |
| 2026-07-08 | 3.1.0 | **ConfiguracionCurso**: Input datetime-local, notificación automática a todos los alumnos al publicar tarea via NotificacionService::crearMultiple, lista de tareas publicadas con estado vencida |
| 2026-07-08 | 3.1.0 | **MisTareas**: Alumno — subida con barra de progreso + botón "Entregar" de confirmación, tareas vencidas bloqueadas con mensaje, re-upload si activa, modal de éxito |
| 2026-07-08 | 3.1.0 | **EntregasList**: Profesor — tabla con fecha+hora de entrega, enlace de descarga con nombre original, badge de entrega tardía, calificación inline |
| 2026-07-08 | 3.1.0 | **Bug fixes**: Content-Type header en uploads (multipart con boundary), storage symlink (php artisan storage:link), MIME validation (mimes→mimetypes), null check en a.entrega |
| 2026-07-08 | 3.1.0 | 37 migraciones total (2 nuevas: fecha_entrega datetime, nombre_original entrega_tarea) |
| 2026-07-11 | 4.0.0 | **Unificación Visual del Frontend**: Eliminación total de emojis (17 archivos), reemplazo con SVGs consistentes. Layout.jsx reescrito con iconos SVG. Sistema de diseño unificado: títulos blue gradient, botones por acción (Crear=blue, Editar=amber, Eliminar=red), focus rings blue, badges blue. Empty states con SVG en todas las vistas. |
| 2026-07-11 | 4.0.0 | **Estandarización de botones**: Editar → amber en 16 archivos, botones de modal alerta/confirmación alineados. |
| 2026-07-11 | 4.0.0 | **FormInput + Login + ChangePassword**: Focus rings cambiados de indigo a blue para consistencia. |
| 2026-07-11 | 4.0.0 | **Fix build**: Instalación de `@tailwindcss/oxide-linux-x64-gnu@4.3.2` para Ubuntu x86_64 glibc. Build exitoso sin errores. |
| 2026-07-30 | 5.0.0 | **Integración de TW Elements (v2.0.0)**: Tema completo en `index.css` (`@theme` con paleta primary/secondary/success/danger/warning/info + sombras shadow-1..5/primary/success/danger + animaciones fade/slide/zoom/tada + dark mode via `@custom-variant dark`), fuente Roboto. |
| 2026-07-30 | 5.0.0 | **`lib/twClasses.js`**: Presets compartidos de clases TW Elements para botones (primary, secondary, success, danger, outline, outlineDanger, ghost, neutral), inputs, cards, tablas y badges. |
| 2026-07-30 | 5.0.0 | **`lib/twElements.js`**: Inicialización de componentes JS de TW Elements (Ripple, Button, Collapse, Dropdown, Modal, Offcanvas, Popover, Tab, Tooltip) con `initTwElements()` en `main.jsx`. MutationObserver para re-renders de React + listeners delegados persistentes para togglers + auto-detección de color de ripple (light/dark). |
| 2026-07-30 | 5.0.0 | **`components/Modal.jsx`**: Nuevo modal reutilizable estilo TW Elements (tamaños sm–5xl, overlay, cierre ESC/backdrop, bloqueo de scroll). |
| 2026-07-30 | 5.0.0 | **Migración de vistas**: Dashboard, Login, ChangePassword, DataTable, FormInput, Layout y ~30 páginas List/Form migradas a la paleta TW Elements (primary/success/danger/warning/info en lugar de blue/indigo), con `data-twe-ripple-init` en botones y ripple activo. |
| 2026-07-30 | 5.0.0 | **Nuevas vistas List/Form**: CRUD completo frontend para Roles, Permisos, Configuración; ReporteGeneradoList; EntregasList (catedrático) y MisTareas (alumno) con estilos TW Elements. |
| 2026-07-30 | 5.0.0 | **Lint/build**: `oxlint` sin errores; build de producción Vite exitoso (54 JS chunks + 1 CSS, dist ~1.2 MB). |
| 2026-08-09 | 6.0.0 | **Avance Programático**: tabla `unidad` (numero_semana, titulo, temas, competencia, estado planificado/en_progreso/completado, fecha_inicio/fin) + FK `id_unidad` en `tarea`. UnidadController CRUD con verificación de propiedad del catedrático; endpoints `unidades/por-asignacion` y rutas CRUD. TareaController con `id_unidad` (fix bug: `orderBy created_at` en tabla sin timestamps → `orderBy id_tarea`). PDF de avance programático + vista blade descargable. ConfiguracionCurso.jsx con pestañas Avance Programático y Tareas. |
| 2026-08-09 | 6.0.0 | **Materiales y Anuncios**: tablas `material` (tipo archivo/enlace, url, id_archivo) y `anuncio`. MaterialController (subida a storage público + descarga autenticada, enlace directo) y AnuncioController con notificación automática a inscritos (NotificacionService::crearMultiple). ConfiguracionCursoController consolidado: asignación + horarios + alumnos + unidades + tareas + materiales + anuncios + evaluaciones en un solo endpoint. |
| 2026-08-09 | 6.0.0 | **Vista Alumno**: AlumnoCursoController (mis-cursos con estadísticas, detalle de curso con tareas planas + `mi_entrega`, entrega de tarea validando inscripción y derivando alumno del token). Páginas MisCursosAlumno y CursoAlumno con pestañas Avance/Tareas/Materiales/Evaluaciones/Anuncios; ítem "Mis Cursos" en Layout solo para rol alumno; rutas en App.jsx. |
| 2026-08-09 | 6.0.0 | **Entrega por enlace**: columna `permitir_link` (bool) en `tarea` y `link` (varchar 500) en `entrega_tarea`. Checkbox "Permitir entrega por enlace" en el formulario de tarea (ConfiguracionCurso). AlumnoCursoController@entregarTarea y EntregaTareaController@subirArchivo aceptan archivo **o** enlace (valida URL, rechaza con 422 si la tarea no permite enlaces, limpia el dato previo al reemplazar). UI alumno: botón "Entregar/Enviar enlace" (modal), visualización y reemplazo de enlace en CursoAlumno y MisTareas; columna "Entrega" en EntregasList muestra archivo o enlace clickeable. |
| 2026-08-09 | 6.0.0 | 31 modelos Eloquent, 39 controladores API, 47 migraciones. Build de producción en contenedor `sga_frontend` (dist/ servido por nginx). |
| 2026-08-14 | 6.0.1 | **Fix entrega en borrador**: columna `fecha_entrega` de `entrega_tarea` era NOT NULL, pero el flujo borrador→presentar la guarda como NULL (EntregaTareaController@subirArchivo y AlumnoCursoController@entregarTarea). Nueva migración `2026_08_14_100001` la hace nullable. Verificación end-to-end del módulo Mis Tareas: login, `GET /v1/mis-tareas`, subida de enlace como borrador, `presentar` → entregada, `por-tarea` (profesor solo ve entregadas), rechazo de enlace en tareas sin `permitir_link`. Lint (oxlint) y build Vite del frontend sin errores (48 migraciones). |
| 2026-08-14 | 6.0.2 | **Fix crash frontend (MisTareas/CursoAlumno)**: al cerrar/presentar, `setModalSubida(null)` re-renderizaba el modal y el `children` evaluaba el else `modalSubida.nombre` con `null` → `TypeError`. Se envuelve el contenido con guard `modalSubida ? ... : null` y se usan optional chaining en el `onClick` de "Presentar Tarea" en `pages/tareas/MisTareas.jsx` y `pages/alumno/CursoAlumno.jsx`. Lint y build sin errores. |
| 2026-08-14 | 6.0.3 | **Fix 413 en subidas (Nginx)**: `client_max_body_size` usaba el default de 1MB, por lo que archivos >1MB eran rechazados con 413 (sin cabecera CORS → el navegador lo mostraba como error de origen cruzado). Se agrega `client_max_body_size 100M` en `docker/nginx/default.conf` (coincide con PHP `upload_max_filesize`/`post_max_size` = 100M). Verificado con subida multipart de 1.9MB → HTTP 201. |
| 2026-08-14 | 6.0.4 | **Fix "no pasa nada al subir tarea" (MisTareas)**: `POST /auth/login` solo cargaba `roles`, por lo que `user.alumno` era `undefined` justo después de iniciar sesión y `handleSubirArchivo`/`handleEnviarLink` retornaban en silencio (`if (!user?.alumno?.id_alumno) return`). Cambios: (1) `AuthController@login` ahora carga `roles`, `alumno`, `catedratico` (consistente con `/auth/me`); (2) `EntregaTareaController@subirArchivo` deriva `id_alumno` del token y valida inscripción en la asignación de la tarea (403 si no inscrito), sin confiar en `id_alumno` del cliente; (3) `MisTareas.jsx` elimina la dependencia de `user.alumno`, y limpia el input de archivo también en error (permite re-seleccionar el mismo archivo). Verificado: login devuelve `alumno`, subida sin `id_alumno` → 201 borrador, tarea de curso no inscrito → 403, enlace sin `permitir_link` → 422, presentar → entregada. Lint y build sin errores. |
| 2026-08-14 | 6.0.5 | **Mi Resumen de alumno funcional (Dashboard)**: nuevo endpoint `GET /v1/alumno/resumen` en `AlumnoCursoController@resumen` que agrega para el alumno autenticado: `promedio_general` (promedio por curso de `calificacion_final.nota_final`, solo con notas reales >0 para descartar el 0 generado por el recálculo de evaluaciones sin notas), `tareas_pendientes` + `proxima_entrega` + `proximas_entregas` (tareas sin entrega `entregada`, ordenadas por fecha), `asistencia_porcentaje` (presentes/total de `asistencia`, case-insensitive) + `asistencias_registradas`, y `avisos` (anuncios recientes de cursos inscritos). `Dashboard.jsx` reescrito: elimina los valores hardcodeados (85/100, 2 pendientes, "Mañana 23:59", 95%) y muestra datos reales con estilos TW Elements (`card`, `badge.*`, tarjeta de promedio con gradiente primary + `shadow-primary-3`, listas con bloques de fecha mes/día, badges de estado y avisos); vista admin sin cambios; vista catedrático/director con mensaje y accesos rápidos. Lint (oxlint) y build Vite sin errores. |
| 2026-08-14 | 6.0.6 | **Campana de notificaciones funcional y con estilos TW Elements (Layout)**: el botón mostraba el placeholder `[CAMPANA]`. Ahora es un botón circular con icono SVG real de campana, contador de no leídas como badge `bg-danger` con `shadow-danger-3` (9+), hover/active primary y variantes dark mode, todo con `data-twe-ripple-init`. Dropdown reestilado (card white/dark, sombra `shadow-4`, hora relativa "Ahora/Hace X min/Hace X h/fecha", botón "Marcar todas" → `POST /notificaciones/marcar-todas-leidas`, acción de marcar leída por hover con ripple). Verificados los endpoints `no-leidas`, `PATCH {id}/leido` y `marcar-todas-leidas` con token de alumno (201 crear → 200 marcar → mensaje OK). Lint y build sin errores. |
| 2026-08-14 | 6.0.7 | **Identidad del usuario logueado más visual (Layout)**: el bloque de nombre a la par de la campana ahora es un dropdown de usuario estilo TW Elements. Avatar circular con gradiente primary→primary-800, iniciales blancas y `shadow-primary-3`; nombre + rol con chevron que rota al abrir; menú con cabecera (avatar + nombre + rol), enlaces "Cambiar contraseña" y "Notificaciones" (con badge `danger` de no leídas), divisor y "Cerrar sesión" en danger con ripple. Se eliminó el botón de logout suelto del header (redundante). Cierre por clic fuera y en tablet/móvil se muestra solo el avatar. Lint y build sin errores. |
| 2026-08-14 | 6.0.8 | **Menú por rol (Dashboard/Mi Resumen)**: el ítem del menú principal cambia según el rol. Admin: etiqueta "Dashboard" (mantiene las métricas y gráfico existentes en `/`). Docente (catedrático): se elimina el ítem de resumen y `/` redirige automáticamente a `/mis-cursos` (`<Navigate replace>`). Alumno, director y secretaria: conservan "Mi Resumen"; la vista director/secretaria simplificada (panel informativo con acceso a Reportes) ya no incluye la rama de catedrático. Lint y build sin errores. |

---

## 14. Unificación Visual del Frontend (2026-07-11)

### 14.1 Objetivo

Estandarizar el diseño visual de todas las vistas del frontend para que todos los roles (admin, director, secretaria, catedrático, alumno) tengan una experiencia visual idéntica, respetando los permisos de navegación. Eliminar todos los emojis y reemplazarlos con iconos SVG/Tailwind consistentes.

### 14.2 Sistema de Diseño Unificado

| Elemento | Estilo |
|---|---|
| Título de página | `text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600` |
| Subtítulo | `text-base text-gray-500 font-medium` |
| Botón Crear/Nuevo | `bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white shadow-lg shadow-blue-200 rounded-xl` |
| Botón Editar | `bg-amber-50 text-amber-600 hover:bg-amber-500 hover:text-white rounded-lg` |
| Botón Eliminar | `bg-red-50 text-red-600 hover:bg-red-600 hover:text-white rounded-lg` |
| Botón Aceptar/Guardar | `bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl` |
| Botón Cancelar | `bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200` |
| Confirmar Eliminar | `bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white shadow-lg shadow-red-200 rounded-xl` |
| Container | `max-w-7xl mx-auto pb-12` |
| Card | `bg-white rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 overflow-hidden` |
| Barra de filtros | `p-6 md:p-8 border-b border-slate-100 bg-slate-50/50` |
| Input de búsqueda | `w-full pl-12 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent` |
| Spinner de carga | `animate-spin rounded-full h-8 w-8 border-4 border-blue-200 border-t-blue-600` |
| Empty state | SVG icon `w-12 h-12 text-slate-300` + `font-semibold text-lg text-slate-600` |
| Focus ring (forms) | `focus:ring-blue-500 focus:border-blue-500` |
| Badge informativo | `bg-blue-50 text-blue-600 border border-blue-100` |

### 14.3 Cambios Realizados

#### Layout.jsx — Reescritura completa
- Sidebar: Todos los iconos de navegación reemplazados de emojis a SVG (Heroicons inline)
  - Dashboard (🏠→casa SVG), Usuarios (👥→personas SVG), Cursos (📚→libro SVG)
  - Infraestructura (🏢→edificio SVG), Reportes PDF (📄→documento SVG), Auditoría (🛡️→escudo SVG)
  - Catedráticos (👨‍🏫→persona SVG), Alumnos (👨‍🎓→personas SVG), Carreras (🎓→birrete SVG)
  - Periodos (📅→calendario SVG), Pensum (📋→clipboard SVG), Asignaciones (📝→clipboard SVG)
  - Inscripciones (📑→documento SVG), Roles (🔑→llave SVG), Permisos (🔒→candado SVG)
  - Configuración (⚙️→engranaje SVG), Notificaciones (🔔→campana SVG), Historial Reportes (📊→gráfico SVG)
  - Mis Tareas (alumno), Entregas de Tareas (catedrático), Mis Cursos (catedrático)
  - Registro de Calificaciones (catedrático), Configuración de Curso (catedrático)
- Header: Campana de notificaciones e ícono de hamburguesa reemplazados con SVG
- Logout: Icono de salida SVG

#### DataTable.jsx
- Empty state: emoji 📭 reemplazado con SVG de buzón
- Botón Editar: `bg-indigo-50 text-indigo-600` → `bg-amber-50 text-amber-600`
- Loading spinner: `border-indigo-200 border-t-indigo-600` → `border-blue-200 border-t-blue-600`

#### Dashboard.jsx
- Empty states: emojis 🏫, 📋, 📊 reemplazados con SVGs (edificio, clipboard, gráfico)
- Badges de bitácora: `bg-indigo-50 text-indigo-600` → `bg-blue-50 text-blue-600`

#### Todas las vistas con empty states (17 archivos)
| Archivo | Emoji original | SVG replacement |
|---|---|---|
| EdificioList.jsx | 🏛️ | Building SVG |
| AulaList.jsx | 🚪 | Door/Lock SVG |
| GradoList.jsx | 📚 | Book SVG |
| SeccionList.jsx | 📋 | Clipboard SVG |
| CursoList.jsx | 📚 | Book SVG |
| UserList.jsx | 👤 | Users SVG |
| AuditoriaList.jsx | 📋 | Clipboard SVG |
| MisTareas.jsx | 📋 | Clipboard SVG |
| MisCursos.jsx | 📭 | Inbox SVG |
| NotificacionList.jsx | 🔔 | Bell SVG |
| ReporteActas.jsx | 📄 | Document SVG |
| ReporteNotas.jsx | 🎓 | Academic cap SVG |
| ReporteConstancias.jsx | 📜 | Scroll/Book SVG |
| ReporteGeneradoList.jsx | 📄 | Document SVG |
| AsignacionForm.jsx | ⚠️ | Warning triangle SVG |
| ConfiguracionCurso.jsx | ✓ (text char) | Se mantuvo (no es emoji) |
| Dashboard.jsx | 🏫 📋 📊 | Building, Clipboard, Chart SVG |

#### Botones Editar estandarizados a amber (16 archivos)
EdificioList, AulaList, GradoList, SeccionList, UserList + todos los que usaban `bg-indigo-50 text-indigo-600` para acciones de editar.

#### FormInput.jsx + Login.jsx + ChangePassword.jsx
- Focus rings: `focus:ring-indigo-500 focus:border-indigo-500` → `focus:ring-blue-500 focus:border-blue-500`
- Asterisco de requerido: `text-indigo-500` → `text-blue-500`

#### Badges/tags informativos
- Dashboard, RegistroCalificaciones, UserList: `bg-indigo-50 text-indigo-600` → `bg-blue-50 text-blue-600`

#### Otros fixes
- EntregasList.jsx: Focus ring de calificación inline cambiado a blue
- RegistroCalificacionesIndex.jsx: Gradiente de tarjeta `from-indigo-50 to-purple-50` → `from-blue-50 to-indigo-50`
- RegistroCalificaciones.jsx: Botón guardar y focus rings de inputs de nota cambiados a blue
- NotificacionList.jsx: Loading spinner y botón Aceptar cambiados a blue
- MisTareas.jsx: Loading spinner cambiado a blue

### 14.4 Fix de Build
- Instalación de `@tailwindcss/oxide-linux-x64-gnu@4.3.2` (binario nativo faltante para Ubuntu x86_64 glibc)
- Build exitoso: 693 módulos transformados, 0 errores

---

## 15. Integración de TW Elements (2026-07-30)

### 15.1 Objetivo

Migrar el sistema de diseño del frontend a **TW Elements (v2.0.0)** de MDBootstrap, componente UI construido sobre Tailwind CSS v4, para contar con una paleta de colores semántica (primary/success/danger/warning/info), sombras, animaciones y componentes interactivos (ripple, modal, dropdown, tooltip) consistentes en todo el SGA.

### 15.2 Dependencia

| Paquete | Versión | Rol |
|---|---|---|
| `tw-elements` | ^2.0.0 | Componentes UI + JS (Ripple, Button, Collapse, Dropdown, Modal, Offcanvas, Popover, Tab, Tooltip) |

No se importa el CSS precompilado de TW Elements: el tema se define íntegramente con tokens de Tailwind v4 en `src/index.css` (patrón soportado por TW Elements 2.0).

### 15.3 Tema en `src/index.css`

| Token | Detalle |
|---|---|
| `--font-sans / --font-body` | Roboto (Google Fonts) |
| `--color-primary` | #3b71ca (azul TW Elements) con escala 50–900 + `accent-100/200/300` |
| `--color-secondary` | #9fa6b2 (gris) con escala 50–900 |
| `--color-success` | #14a44d (verde) |
| `--color-danger` | #dc4c64 (rojo) |
| `--color-warning` | #e4a11b (ámbar) |
| `--color-info` | #54b4d3 (cian) |
| `--color-surface / surface-dark` | #4f4f4f / #424242 (dark mode) |
| `--shadow-1` … `--shadow-5` | Sombras neutrales TW Elements |
| `--shadow-primary-1/2/3/5`, `--shadow-success-*`, `--shadow-danger-*`, `--shadow-warning-*`, `--shadow-info-*`, `--shadow-secondary-*`, `--shadow-dark-*` | Sombras por color |
| `--animate-fade-*`, `--animate-slide-*`, `--animate-zoom-*`, `--animate-tada` | Animaciones con keyframes definidos en `@theme` |
| `@custom-variant dark` | Modo oscuro por clase `.dark` |
| `@source inline(...)` | Safelist de la clase del ripple que TW Elements agrega en runtime |

Base: `body` con `bg-neutral-100`, fuente Roboto y `antialiased`; scrollbar personalizado; ocultamiento de `::ms-reveal/clear` en password inputs.

### 15.4 `src/lib/twClasses.js` — Presets compartidos

Centraliza las clases para evitar duplicación en ~30 vistas:

| Export | Contenido |
|---|---|
| `btn` | primary, secondary, success, danger, outline, outlineDanger, ghost, neutral — todos incluyen `data-twe-ripple-init` |
| `input` | `base` (foco con ring primary), `label`, `error` (text-danger) |
| `card` | `rounded-xl bg-white p-6 shadow-4 dark:bg-surface-dark` |
| `table` | `wrapper`, `head`, `th`, `td`, `row` (hover + dark mode) |
| `badge` | success, danger, warning, info, primary, neutral (pill con variante dark) |

### 15.5 `src/lib/twElements.js` — Runtime JS

- **`initTwElements()`** se invoca en `src/main.jsx` antes de montar React.
- **Ripple por elemento + MutationObserver**: escanea `[data-twe-ripple-init]` y observa el DOM para inicializar ripples en nodos agregados por re-renders de React (evita el problema de que TW Elements solo escanea el DOM una vez).
- **Auto-detección de color de ripple**: calcula luminancia del fondo y asigna `data-twe-ripple-color="light|dark"` automáticamente.
- **Listeners delegados persistentes**: un solo listener `document` para togglers de Button, Collapse, Tab/Pill/List, Dropdown, Modal (cierra el modal previo al abrir otro) y Offcanvas, sobreviviendo a los cambios de ruta de React Router.
- **Tooltips y popovers**: inicialización estática sobre `[data-twe-toggle="tooltip"]` / `[data-twe-toggle="popover"]`.

### 15.6 Componentes

- **`components/Modal.jsx`**: modal reutilizable con tamaños `sm`–`5xl`, overlay `bg-black/50` (click para cerrar), cierre con `ESC`, bloqueo de scroll del `body` y footer configurable. Sustituye los modales repetidos de cada vista. Usado en DataTable + ~30 páginas.
- **`DataTable.jsx`**: reescrito con estilos TW Elements (`btn`, `input`, `table`), spinner `border-primary-100 border-t-primary`, empty state con SVG y modal de confirmación de borrado.
- **`FormInput.jsx`**: inputs con `input.base` (foco ring primary) y mensaje de error con `input.error`.
- **`Layout.jsx`**: botones del sidebar, campana de notificaciones y logout con `data-twe-ripple-init` (efecto ripple); color activo `bg-primary shadow-primary-3`; sidebar `bg-surface-dark`.

### 15.7 Vistas migradas a la paleta TW Elements

| Grupo | Archivos |
|---|---|
| Auth | Login, ChangePassword |
| Principal | Dashboard |
| Compartidos | DataTable, FormInput, Layout |
| Infraestructura | EdificioList/Form, AulaList/Form, GradoList, SeccionList |
| Académico | CursoList/Form, CarreraForm, PeriodoForm, PensumForm, AsignacionForm, InscripcionForm |
| Docente | CatedraticoForm, MisCursos, RegistroCalificaciones, ConfiguracionCurso |
| Tareas | MisTareas, TareaForm, EntregasList |
| Evaluaciones | EvaluacionForm |
| Admin | UserList, AuditoriaList, RolForm, PermisoForm, ConfiguracionForm |
| Notificaciones | NotificacionList |
| Reportes | ReporteActas, ReporteNotas, ReporteConstancias, ReporteGeneradoList |

Las vistas listadas usan los presets de `twClasses`; las páginas List que delegan en `DataTable` (Alumnos, Catedráticos, Carreras, Periodos, Tareas, Roles, Permisos, Configuración, Inscripciones, Asignaciones, Evaluaciones, Pensum) heredan el mismo sistema visual.

### 15.8 Sistema de Diseño Resultante

| Elemento | Estilo TW Elements |
|---|---|
| Título de página | `text-3xl font-bold text-neutral-800 dark:text-neutral-100` |
| Botón primario | `btn.primary` → `bg-primary shadow-primary-3` con ripple |
| Botón peligro | `btn.danger` → `bg-danger shadow-danger-3` |
| Botón neutral | `btn.neutral` → texto `text-neutral-600` con hover |
| Input | `input.base` → `focus:border-primary focus:ring-primary` |
| Card | `card` → `shadow-4`, variante dark `surface-dark` |
| Tabla | `table.head/th/td/row` con hover `bg-neutral-50` |
| Badge | pill `rounded-full` con `*-50` y variante dark |
| Spinner | `border-4 border-primary-100 border-t-primary` |
| Ripple | en botones y elementos interactivos (`data-twe-ripple-init`) |
| Dark mode | Variante `.dark` habilitada en todo el CSS |

---

## 16. Módulo de Configuración de Curso y Vista Alumno (2026-08-09)

### 16.1 Objetivo

Dotar al catedrático de un panel completo de gestión por curso (avance programático, tareas, materiales, anuncios y evaluaciones) y al alumno de una vista de curso con entrega de tareas por archivo **o enlace**.

### 16.2 Esquema de datos (v6.0)

| Tabla | Columnas principales | Relaciones |
|---|---|---|
| `unidad` | `id_unidad`, `id_asignacion`, `numero_semana`, `titulo`, `temas`, `competencia`, `estado`, `fecha_inicio`, `fecha_fin` | FK `id_asignacion` |
| `material` | `id_material`, `id_asignacion`, `id_unidad`, `id_archivo`, `titulo`, `descripcion`, `tipo` (archivo/enlace), `url`, `fecha_publicacion` | FK `id_asignacion`, `id_unidad`, `id_archivo` |
| `anuncio` | `id_anuncio`, `id_asignacion`, `titulo`, `contenido`, `fecha_publicacion` | FK `id_asignacion` |
| `tarea` (+2 cols) | `permitir_link` (bool), `id_unidad` (FK) | — |
| `entrega_tarea` (+1 col) | `link` (varchar 500) | — |

### 16.3 Controladores y autorización

Todos los endpoints de catedrático validan **propiedad de la asignación** (`verificarCatedratico`): si el usuario tiene perfil de catedrático, debe ser el dueño de la asignación (403 en caso contrario); admin sin perfil catedrático pasa directamente. Los endpoints de alumno validan **inscripción** (`estaInscrito`) y derivan `id_alumno` del token (nunca se recibe del cliente).

### 16.4 Notificaciones automáticas

- Al publicar una **tarea** → `NotificacionService::crearMultiple` a todos los alumnos inscritos, con título y fecha límite.
- Al crear un **anuncio** → misma notificación masiva a inscritos.

### 16.5 Configuración de Curso (catedrático)

`ConfiguracionCursoController@show` devuelve en un solo GET:
`asignacion` (curso, grado, sección, periodo) · `horarios` · `alumnos` · `unidades` (con tareas y total de entregas) · `tareas` (con unidad y estadísticas) · `materiales` · `anuncios` · `evaluaciones`.

El frontend `ConfiguracionCurso.jsx` renderiza 5 pestañas: **Avance Programático** (timeline de semanas con estado, generar 16 semanas, agregar/editar/eliminar, avanzar estado, descargar PDF), **Tareas** (formulario + lista con vencidas), **Materiales**, **Anuncios** y **Evaluaciones** (componentes en `pages/catedraticos/curso/`).

### 16.6 Vista Alumno

- `MisCursosAlumno` → lista de cursos inscritos (GET `/v1/alumno/mis-cursos`) con contadores de unidades/tareas/materiales/anuncios/evaluaciones.
- `CursoAlumno` → detalle del curso (GET `/v1/alumno/curso/{id}`) con pestañas; entrega de tareas vía `POST /v1/alumno/curso/{id}/entregar/{id_tarea}`.

### 16.7 Entrega por enlace

| Lado | Detalle |
|---|---|
| Catedrático | Checkbox "Permitir entrega por enlace (link)" al publicar una tarea (`permitir_link`) |
| Alumno | Botón "Entregar/Enviar enlace" (modal con URL) si la tarea lo permite; reemplazo de archivo o enlace |
| Backend | `entregarTarea` / `subirArchivo` aceptan archivo (multipart) **o** `link` (JSON). Validación: URL válida (`url`), máx. 500; si la tarea no permite enlaces → 422; al reemplazar se limpia el dato anterior |
| Profesor | `EntregasList` muestra en la columna "Entrega" el archivo descargable **o** el enlace clickeable |

---

> NOTA: Este archivo debe actualizarse con cada cambio significativo al proyecto.
> Es el documento fuente de verdad de la implementación técnica.
