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

El proyecto (backend Laravel y frontend React) ya está construido — estos pasos son para
clonar el repositorio y levantarlo, no para crearlo desde cero.

### Prerrequisitos
- Docker Engine ≥ 24.x
- Docker Compose ≥ 2.x
- Git

---

### PASO 1 — Clonar y configurar las variables de entorno

```bash
# 1. Clonar el repositorio
git clone https://github.com/Hugoda23/SGA.git
cd SGA

# 2. Copiar las plantillas de variables de entorno (ninguna se sube a Git)
cp .env.example .env
cp backend/.env.example backend/.env
```

Ver la sección **Variables de entorno — qué archivo editar** más arriba si necesitas ajustar
algo (contraseña de BD, dominio, etc.) antes de continuar — los valores por defecto de las
plantillas ya sirven para desarrollo local tal cual.

---

### PASO 2 — Construir y levantar los contenedores

```bash
# 1. Construir las imágenes (primera vez, tarda ~3-5 min)
docker compose build

# 2. Levantar todo el stack (db, backend, webserver/Nginx, frontend)
# el backend espera automáticamente a que la BD esté saludable
docker compose up -d

# 3. Verificar que todos los contenedores están corriendo
docker compose ps
```

---

### PASO 3 — Inicializar Laravel (clave, migraciones, datos de prueba)

```bash
# 1. Generar la clave de aplicación
docker compose exec backend php artisan key:generate

# 2. Correr las migraciones (crea todas las tablas en sga_db)
docker compose exec backend php artisan migrate

# 3. Poblar la BD con datos de demo end-to-end (cursos, alumnos, asignaciones,
#    inscripciones, roles y permisos) — ver backend/database/seeders/DatabaseSeeder.php
docker compose exec backend php artisan db:seed

# 4. Crear la base de datos dedicada para tests (una sola vez)
docker compose exec db createdb -U sga_user sga_test
```

Con esto el sistema ya queda usable de punta a punta — ver **URLs de Acceso** abajo para
entrar, y `docker compose logs -f` para ver logs en tiempo real de todos los servicios.

---

## URLs de Acceso

| Servicio | URL | Descripción |
|---|---|---|
| **App (React, SPA)** | http://localhost:3002 | Frontend — servidor de desarrollo de Vite con hot-reload |
| **API Laravel** | http://localhost:8081/api | Backend RESTful, servido por Nginx → PHP-FPM |
| **PostgreSQL** | localhost:5433 | Puerto local de la BD (usuario/BD en `.env`) |

---

## Comandos Útiles del Día a Día

```bash
# Artisan (desde host)
docker compose exec backend php artisan migrate
docker compose exec backend php artisan make:model Estudiante -mcr
docker compose exec backend php artisan route:list

# Tests (usan una BD dedicada "sga_test", nunca sga_db)
# IMPORTANTE: siempre con `composer test`, NUNCA `php artisan test` directo
# — ver el comentario en backend/scripts/test.sh y backend/phpunit.xml
# para el porqué (docker-compose.yml ya define DB_DATABASE como variable
# de entorno del contenedor, y eso gana sobre el <env force="true"> de
# PHPUnit si no se exporta ANTES de arrancar PHP).
docker compose exec backend composer test

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

**Ejemplo de endpoint:** `GET http://localhost:8081/api/v1/estudiantes`

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

# 5. Crear la carpeta donde "webserver" monta los archivos subidos (backend_storage).
#    "public/storage" está en .gitignore (normalmente es un symlink en desarrollo) y
#    no existe todavía en un clon nuevo — sin esto, Nginx falla al arrancar con
#    "read-only file system" porque no puede crear el punto de montaje dentro de
#    ./backend/public, que se monta de solo lectura.
mkdir -p backend/public/storage

# 6. Compilar el frontend (deja los estáticos en el volumen frontend_dist)
docker compose -f docker-compose.prod.yml --env-file .env.production \
  --profile build run --rm frontend-build

# 7. Levantar base de datos y backend primero (todavía no Nginx)
docker compose -f docker-compose.prod.yml --env-file .env.production up -d db backend

# 8. Certificado autofirmado temporal — Nginx tiene el bloque HTTPS en su config
#    incondicionalmente y no arranca si no encuentra un certificado en esa ruta,
#    pero certbot (método webroot) necesita a Nginx ya corriendo para poder
#    emitir el real. Este autofirmado solo existe para romper ese círculo.
docker run --rm -v sga_certbot_conf:/etc/letsencrypt alpine sh -c "
  apk add --no-cache openssl &&
  mkdir -p /etc/letsencrypt/live/tudominio.com &&
  openssl req -x509 -nodes -newkey rsa:2048 -days 1 \
    -keyout /etc/letsencrypt/live/tudominio.com/privkey.pem \
    -out /etc/letsencrypt/live/tudominio.com/fullchain.pem \
    -subj '/CN=localhost'
"

# 9. Ahora sí, levantar Nginx (ya tiene un certificado, aunque sea temporal, para arrancar)
docker compose -f docker-compose.prod.yml --env-file .env.production up -d webserver

# 10. Emitir el certificado real (Let's Encrypt, método webroot) — solo la primera vez
docker compose -f docker-compose.prod.yml --env-file .env.production run --rm \
  --entrypoint certbot certbot certonly --webroot -w /var/www/certbot \
  -d tudominio.com --email tu-correo@ejemplo.com --agree-tos --no-eff-email

# 11. Reiniciar Nginx para que cargue el certificado real recién emitido
docker compose -f docker-compose.prod.yml --env-file .env.production restart webserver

# 12. Dejar la renovación automática del certificado corriendo en segundo plano
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

# El backend lleva el código dentro de la imagen, hay que reconstruirla.
# Al arrancar corre "php artisan migrate --force" solo (ver su "command"),
# así que las migraciones nuevas se aplican en este paso.
docker compose -f docker-compose.prod.yml --env-file .env.production build backend

# El frontend se recompila a estáticos. Hay que reconstruir la imagen ANTES
# de correr el servicio: "run" reutiliza la imagen existente si ya existe y,
# sin este build, se recompilaría con el código viejo.
docker compose -f docker-compose.prod.yml --env-file .env.production \
  --profile build build frontend-build
docker compose -f docker-compose.prod.yml --env-file .env.production \
  --profile build run --rm frontend-build

docker compose -f docker-compose.prod.yml --env-file .env.production up -d backend webserver

# prod.conf es un bind-mount: si cambió, hace falta reiniciar Nginx.
docker compose -f docker-compose.prod.yml --env-file .env.production restart webserver
```

**Nota:** `EnviarNotificacionJob` y `EnviarNotificacionMultipleJob` (`backend/app/Jobs/`) existen
en el código pero hoy no se despachan desde ningún lado. Si en el futuro se activan, agregar un
servicio adicional en `docker-compose.prod.yml` corriendo `php artisan queue:work` (misma imagen
del backend, comando distinto) — sin eso, los jobs despachados se acumularían sin procesarse.

---

*Generado para el SGA — Arquitectura Headless | Stack: Laravel + React + PostgreSQL + Docker*
