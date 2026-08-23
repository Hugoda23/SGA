-- ============================================================
-- Script de inicialización de PostgreSQL para SGA
-- Se ejecuta automáticamente al crear el contenedor
-- ============================================================

-- Habilitar extensiones útiles para campos JSONB y UUID
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";  -- búsqueda de texto

-- Base de datos dedicada para la suite de tests (backend/phpunit.xml).
-- Separada de sga_db para que RefreshDatabase nunca toque datos de desarrollo.
SELECT 'CREATE DATABASE sga_test OWNER ' || current_user
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'sga_test')\gexec

-- Mensaje de confirmación
DO $$
BEGIN
    RAISE NOTICE 'Base de datos SGA inicializada correctamente con soporte JSONB y UUID';
END $$;
