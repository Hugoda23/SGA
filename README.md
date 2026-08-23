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
├── docker-compose.prod.yml     # Orquestación de producción (VPS)
├── .env                        # Variables de entorno de docker-compose (NO subir a Git)
├── .env.example                # Plantilla de variables de desarrollo
├── .env.production.example     # Plantilla de variables de producción
└── README.md
```

---

## Variables de entorno — qué archivo editar

Hay dos `.env` relevantes en desarrollo y no cubren lo mismo:

- **`/.env` (raíz)**: lo lee `docker-compose.yml` para sustituir `${DB_PASSWORD}`, `${APP_KEY}`, etc. Esos valores se inyectan como variables de entorno **dentro** del contenedor `backend`, y en Laravel una variable de entorno del sistema operativo tiene prioridad sobre la misma clave definida en `backend/.env`. Es decir: si quieres cambiar `DB_PASSWORD`, `APP_KEY`, `APP_DEBUG` o `APP_ENV` para todo el stack, edita este archivo.
- **`backend/.env`**: cubre todo lo que el `docker-compose.yml` no sobrescribe (mail, sesión, colas, `CORS_ALLOWED_ORIGINS`, `SANCTUM_STATEFUL_DOMAINS`, etc.). Es el que Laravel usa como fuente principal cuando una clave no llega ya seteada por Docker.

Para producción, ver `.env.production.example` y la sección **Despliegue en producción (VPS)** más abajo.

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

# Tests (usan una BD dedicada "sga_test", nunca sga_db — ver backend/phpunit.xml)
docker compose exec backend php artisan test

# Si el contenedor de BD ya existía antes de agregar sga_test a init.sql,
# crearla una sola vez con:
docker compose exec db createdb -U sga_user sga_test

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

## Despliegue en producción (VPS)

Esta sección describe cómo desplegar SGA en un VPS (por ejemplo, Hostinger) usando
`docker-compose.prod.yml`. A diferencia del entorno de desarrollo, en producción:

- El backend corre desde una imagen con el código ya compilado (`docker/php/Dockerfile.prod`),
  no desde un bind-mount.
- El frontend se compila una sola vez a estáticos (`frontend/Dockerfile.prod`) y Nginx los
  sirve directamente — no hay servidor de desarrollo de Vite en producción.
- Solo Nginx expone puertos a Internet (80/443); la base de datos y el backend solo son
  alcanzables dentro de la red interna de Docker.
- `APP_DEBUG=false`, `APP_ENV=production`, y todas las variables sensibles vienen de
  `.env.production` (basado en `.env.production.example`, **nunca** se commitea el real).

### Prerrequisitos en el VPS

- Docker Engine y Docker Compose instalados.
- Un dominio apuntando (registro A) a la IP del VPS.
- Puertos 80 y 443 abiertos en el firewall (`ufw allow 80,443,22/tcp`; bloquear todo lo demás,
  en particular no exponer el puerto de PostgreSQL ni el de PHP-FPM).

### Pasos

```bash
# 1. Clonar el repositorio en el VPS
git clone https://github.com/Hugoda23/SGA.git
cd SGA

# 2. Configurar variables de producción
cp .env.production.example .env.production
# Editar .env.production: dominio real, contraseña de BD fuerte, etc.

# 3. Generar APP_KEY (una sola vez) y pegarlo en .env.production como APP_KEY=base64:...
docker run --rm -v "$PWD/backend":/app -w /app composer:2.7 sh -c \
  "php -r \"echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;\""

# 4. Reemplazar "tudominio.com" por el dominio real en docker/nginx/prod.conf

# 5. Compilar el frontend (deja los estáticos en el volumen frontend_dist)
docker compose -f docker-compose.prod.yml --env-file .env.production \
  --profile build run --rm frontend-build

# 6. Levantar base de datos, backend (corre migraciones automáticamente) y Nginx
docker compose -f docker-compose.prod.yml --env-file .env.production up -d db backend webserver

# 7. Emitir el certificado SSL (Let's Encrypt, método webroot) — solo la primera vez
docker compose -f docker-compose.prod.yml --env-file .env.production run --rm \
  --entrypoint certbot certbot certonly --webroot -w /var/www/certbot \
  -d tudominio.com --email tu-correo@ejemplo.com --agree-tos --no-eff-email

# 8. Reiniciar Nginx para que cargue el certificado recién emitido
docker compose -f docker-compose.prod.yml --env-file .env.production restart webserver

# 9. Dejar la renovación automática del certificado corriendo en segundo plano
docker compose -f docker-compose.prod.yml --env-file .env.production --profile certbot up -d certbot
```

### Mantenimiento

```bash
# Backup de la base de datos
docker exec -t sga_db_prod pg_dump -U sga_user -d sga_db -c > "backup_$(date +%F).sql"

# Ver logs
docker compose -f docker-compose.prod.yml logs backend --tail=100
docker compose -f docker-compose.prod.yml logs webserver --tail=100

# Desplegar una actualización de código
git pull
docker compose -f docker-compose.prod.yml --env-file .env.production build backend
docker compose -f docker-compose.prod.yml --env-file .env.production --profile build run --rm frontend-build
docker compose -f docker-compose.prod.yml --env-file .env.production up -d backend webserver
```

**Nota:** `EnviarNotificacionJob` y `EnviarNotificacionMultipleJob` (`backend/app/Jobs/`) existen
en el código pero hoy no se despachan desde ningún lado. Si en el futuro se activan, agregar un
servicio adicional en `docker-compose.prod.yml` corriendo `php artisan queue:work` (misma imagen
del backend, comando distinto) — sin eso, los jobs despachados se acumularían sin procesarse.

---

*Generado para el SGA — Arquitectura Headless | Stack: Laravel + React + PostgreSQL + Docker*
