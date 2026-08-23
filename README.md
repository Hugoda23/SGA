# SGA — Sistema de Gestión Académica

**Arquitectura Headless (Desacoplada)**
Backend: Laravel (API RESTful) | Frontend: React + Vite + Tailwind CSS | DB: PostgreSQL | Infraestructura: Docker

---

## Estructura del Repositorio

```
SGA/
├── backend/                    # Proyecto Laravel (API RESTful)
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/Api/  # Controladores de la API
│   │   │   ├── Middleware/        # Auth, CORS, throttle
│   │   │   └── Requests/          # Form Requests (validación)
│   │   ├── Models/                # Modelos Eloquent
│   │   └── Services/              # Lógica de negocio desacoplada
│   ├── database/
│   │   ├── migrations/            # Migraciones (con campos JSONB)
│   │   └── seeders/               # Datos semilla
│   ├── routes/
│   │   └── api.php                # Rutas RESTful versionadas
│   └── ...
│
├── frontend/                   # Proyecto React + Vite + Tailwind
│   ├── src/
│   │   ├── api/                   # Servicios Axios / fetch
│   │   ├── assets/                # Imágenes, íconos
│   │   ├── components/            # Componentes reutilizables
│   │   ├── hooks/                 # Custom hooks de React
│   │   ├── pages/                 # Vistas/rutas de la app
│   │   ├── store/                 # Estado global (Zustand/Redux)
│   │   └── styles/                # Estilos globales + Tailwind
│   └── ...
│
├── docker/                     # Configuración Docker
│   ├── nginx/
│   │   └── default.conf           # Reverse proxy para Laravel
│   ├── php/
│   │   ├── Dockerfile             # PHP-FPM 8.4
│   │   └── local.ini              # Config PHP dev
│   └── postgres/
│       └── init.sql               # Init BD + extensiones JSONB
│
├── docker-compose.yml          # Orquestación local de desarrollo
├── docker-compose.prod.yml     # (Futuro) Producción
├── .env                        # Variables de entorno (NO subir a Git)
├── .env.example                # Plantilla de variables
└── README.md
```

---

##  Pasos de Configuración Inicial

### Prerrequisitos
- Docker Engine ≥ 24.x
- Docker Compose ≥ 2.x
- Git

---

### PASO 1 — Clonar y preparar el entorno

```bash
# 1. Entrar a la carpeta del proyecto
cd /home/hugoda/SGA

# 2. Copiar las variables de entorno
cp .env.example .env

# 3. Construir las imágenes Docker (primera vez, tarda ~3-5 min)
docker compose build
```

---

### PASO 2 — Inicializar Laravel en el contenedor

```bash
# 1. Levantar SOLO el contenedor de base de datos primero
docker compose up -d db

# 2. Esperar que esté saludable (healthcheck)
docker compose ps

# 3. Levantar el backend (PHP-FPM)
docker compose up -d backend

# 4. Entrar al contenedor del backend
docker compose exec backend bash

# === DENTRO DEL CONTENEDOR ===

# 5. Crear el proyecto Laravel (si la carpeta backend está vacía)
composer create-project laravel/laravel . --prefer-dist

# 6. Configurar el .env de Laravel para PostgreSQL
# Editar el archivo .env dentro de /var/www/html/
# DB_CONNECTION=pgsql
# DB_HOST=db
# DB_PORT=5432
# DB_DATABASE=sga_db
# DB_USERNAME=sga_user
# DB_PASSWORD=sga_secret

# 7. Generar la clave de aplicación
php artisan key:generate

# 8. Correr las migraciones
php artisan migrate

# 9. (Opcional) Poblar la BD con datos de prueba
php artisan db:seed

# 10. Ajustar permisos de almacenamiento
chmod -R 775 storage bootstrap/cache
chown -R www:www storage bootstrap/cache

# Salir del contenedor
exit
```

---

### PASO 3 — Inicializar React + Vite + Tailwind CSS

```bash
# 1. Levantar el contenedor del frontend
docker compose up -d frontend

# 2. Entrar al contenedor del frontend
docker compose exec frontend sh

# === DENTRO DEL CONTENEDOR ===

# 3. Crear el proyecto Vite + React (si la carpeta frontend está vacía)
npm create vite@latest . -- --template react

# 4. Instalar dependencias base
npm install

# 5. Instalar Tailwind CSS v4 (recomendado) y sus dependencias
npm install -D tailwindcss@latest @tailwindcss/vite

# 6. Instalar dependencias adicionales recomendadas
npm install axios react-router-dom
npm install -D @types/react @types/react-dom

# Salir del contenedor
exit
```

---

### PASO 4 — Configurar Tailwind CSS

**Editar `frontend/src/index.css`** — reemplazar contenido con:

```css
@import "tailwindcss";
```

**Editar `frontend/vite.config.js`** — agregar el plugin de Tailwind:

```js
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  plugins: [
    react(),
    tailwindcss(),
  ],
  server: {
    host: '0.0.0.0',
    port: 5173,
  },
})
```

---

### PASO 5 — Levantar todo el entorno

```bash
# Levantar todos los servicios
docker compose up -d

# Verificar que todos los contenedores están corriendo
docker compose ps

# Ver los logs en tiempo real
docker compose logs -f
```

---

## URLs de Acceso

| Servicio | URL | Descripción |
|---|---|---|
| **API Laravel** | http://localhost:8080/api | Backend RESTful |
| **React App** | http://localhost:3000 | Frontend SPA |
| **PostgreSQL** | localhost:5433 | Puerto local BD |

---

## Comandos Útiles del Día a Día

```bash
# Artisan (desde host)
docker compose exec backend php artisan migrate
docker compose exec backend php artisan make:model Estudiante -mcr
docker compose exec backend php artisan route:list

# NPM (desde host)
docker compose exec frontend npm run build
docker compose exec frontend npm add zustand

# PostgreSQL (cliente psql)
docker compose exec db psql -U sga_user -d sga_db

# Logs
docker compose logs backend --tail=50
docker compose logs frontend --tail=50

# Reiniciar un servicio
docker compose restart backend

# Apagar todo
docker compose down

# Apagar y eliminar volúmenes (¡borra la BD!)
docker compose down -v
```

---

## Funcionalidades Principales

### Académico
- **Inscripción con reglas de negocio** (`InscripcionService`): rechaza periodo cerrado, inscripción activa duplicada, cupo del aula superado, carrera no compatible y choque de horario (mismo día y franja solapada). Retiro de inscripción vía `POST /v1/inscripciones/{inscripcion}/retirar` (permite reinscribirse).
- **Horario del alumno**: `GET /v1/alumno/horario` agrupa las clases de las inscripciones activas del alumno (curso, aula, grado, sección, periodo).
- **Créditos en kárdex**: `curso.creditos` alimenta el PDF del kárdex con créditos aprobados.
- **Estado académico y cierre de periodo** (`PromocionService`): al cerrar un periodo se bloquea la edición de calificaciones y se promueve a los alumnos que cumplen la nota mínima.

### Reportes
- **Kárdex, constancias y actas**: PDF descargables (`/v1/reportes/pdf/...`) con código QR de verificación.
- **Rendimiento por grado/periodo**: `GET /v1/reportes/rendimiento` con resumen de inscritos, aprobados, reprobados y porcentaje de aprobación por asignación.

### Listados (paginación, búsqueda y exportación)
- Los índices de **alumnos, asignaciones, inscripciones, catedráticos, evaluaciones y tareas** soportan `per_page` (máx. 1000), `page` y `q` (búsqueda por texto). El botón "Exportar CSV" descarga el listado filtrado en formato CSV.

### Notificaciones
- Automáticas al inscribirse, al publicar tarea/anuncio/material/evaluación (para alumnos del curso) y al presentar una tarea (para el catedrático). `GET /v1/notificaciones/no-leidas` alimenta el contador del menú.

### Integridad de datos
- Índice único parcial en `inscripcion(id_alumno, id_asignacion) WHERE estado='activo'`, índice único en `calificacion_final(id_inscripcion)` y checks de valores para `estado` de inscripción, `estado_academico` y estado de periodo.

---

## Estándar de la API RESTful

Las rutas deben estar versionadas en `backend/routes/api.php`:

```php
Route::prefix('v1')->group(function () {
    Route::apiResource('estudiantes', EstudianteController::class);
    Route::apiResource('cursos', CursoController::class);
    Route::apiResource('inscripciones', InscripcionController::class);
});
```

**Ejemplo de endpoint:** `GET http://localhost:8080/api/v1/estudiantes`

---

*Generado para el SGA — Arquitectura Headless | Stack: Laravel + React + PostgreSQL + Docker*
