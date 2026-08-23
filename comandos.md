# Comandos Útiles para Interactuar con PostgreSQL (SGA)

Este archivo contiene una referencia rápida de los comandos que puedes utilizar para gestionar tu base de datos PostgreSQL desde la terminal utilizando Docker.

## 1. Ingresar a la consola de la base de datos (psql)
Para entrar directamente a interactuar con la base de datos (sin pasar por el shell del contenedor de Linux), ejecuta en tu terminal:

```bash
docker exec -it sga_db psql -U sga_user -d sga_db
```

---

## 2. Comandos de la consola de PostgreSQL (Atajos de psql)
Una vez dentro de la consola (el texto en tu terminal cambiará a algo como `sga_db=#`), puedes usar estos comandos rápidos. **Nota:** Estos comandos *no* llevan punto y coma al final.

| Comando | Descripción |
| :--- | :--- |
| `\l` o `\list` | Muestra la lista de todas las bases de datos. |
| `\c nombre_db` | Cambiar/Conectarse a otra base de datos diferente. |
| `\dt` | Muestra la lista de todas las **tablas** en la base de datos actual. |
| `\d nombre_tabla` | Muestra la **estructura** de una tabla específica (columnas, tipos de datos, llaves foráneas). |
| `\du` | Muestra la lista de todos los usuarios (roles). |
| `\x` | Activa/Desactiva la vista expandida (muy útil si un `SELECT` devuelve muchas columnas y se ve desordenado). |
| `\?` | Muestra el menú de ayuda de psql con más comandos. |
| `\q` | **Salir** de la consola de la base de datos y regresar a tu terminal normal. |

---

## 3. Comandos SQL Básicos (Consultas)
Todas las sentencias SQL estándar funcionan aquí. **IMPORTANTE:** Siempre debes terminar estas sentencias con un punto y coma (`;`).

**Ver todos los registros de una tabla:**
```sql
SELECT * FROM users;
```

**Ver columnas específicas con un filtro (Ejemplo):**
```sql
SELECT id, name, email FROM users WHERE id = 1;
```

**Contar cuántos registros hay en una tabla:**
```sql
SELECT COUNT(*) FROM users;
```

**Insertar datos manualmente:**
```sql
INSERT INTO users (name, email, password) VALUES ('Juan', 'juan@test.com', 'password');
```

**Actualizar un registro existente:**
```sql
UPDATE users SET name = 'Juan Modificado' WHERE id = 1;
```

**Eliminar todos los datos de una tabla (¡Cuidado!):**
```sql
TRUNCATE TABLE users CASCADE;
```

---

## 4. Comandos extra: Respaldos desde tu terminal
Estos comandos se ejecutan desde **fuera** de la base de datos (en tu terminal normal de Ubuntu/VSCode) para exportar e importar la base de datos.

**Crear un backup (exportar a archivo `sga_backup.sql`):**
```bash
docker exec -t sga_db pg_dump -U sga_user -d sga_db -c > sga_backup.sql
```

**Restaurar un backup (importar desde archivo `sga_backup.sql`):**
```bash
cat sga_backup.sql | docker exec -i sga_db psql -U sga_user -d sga_db
```
