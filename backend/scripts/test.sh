#!/bin/sh
# Ejecuta la suite de tests contra la BD de test dedicada (sga_test).
#
# Por qué existe este script: docker-compose.yml define DB_CONNECTION/
# DB_DATABASE como variables de entorno REALES del contenedor "backend".
# PHP puebla $_SERVER/$_ENV con esos valores al arrancar, y Laravel
# (vía phpdotenv) prioriza $_SERVER/$_ENV sobre el putenv() que usa
# phpunit.xml en sus tags <env force="true">. Resultado: <env force="true">
# NO alcanza a sobreescribir una variable que el contenedor ya trae puesta
# — los tests correrían silenciosamente contra sga_db (datos reales).
#
# La única forma confiable de forzar la BD de test es exportar estas
# variables ANTES de que el proceso de PHP arranque, para que $_SERVER/
# $_ENV ya nazcan con el valor correcto. Por eso este script — nunca
# ejecutar `php artisan test` directamente en este proyecto.
set -e

export DB_CONNECTION=pgsql
export DB_HOST=db
export DB_PORT=5432
export DB_DATABASE=sga_test
export DB_USERNAME=sga_user
export DB_PASSWORD=sga_secret

php artisan config:clear --ansi
exec php artisan test "$@"
