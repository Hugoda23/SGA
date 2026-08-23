-- ============================================================
-- Script de inicialización de PostgreSQL para SGA
-- Se ejecuta automáticamente al crear el contenedor
-- ============================================================

-- Habilitar extensiones útiles para campos JSONB y UUID
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";  -- búsqueda de texto

-- Mensaje de confirmación
DO $$
BEGIN
    RAISE NOTICE 'Base de datos SGA inicializada correctamente con soporte JSONB y UUID';
END $$;
