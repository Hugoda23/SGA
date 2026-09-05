<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Datos de identificación del alumno: nacionalidad y documento (CUI o
     * pasaporte). Los tres son obligatorios.
     *
     * Se agregan primero como nullable y se rellenan antes de marcarlos
     * NOT NULL, porque un ADD COLUMN NOT NULL sin default revienta si la
     * tabla ya trae filas. En desarrollo la tabla está vacía y el backfill
     * no toca nada; en un entorno que ya tenga alumnos, esos quedan con
     * numero_documento 'PENDIENTE-<id>' — visible a propósito, para
     * corregirlo editando cada alumno en la aplicación.
     */
    public function up(): void
    {
        Schema::table('alumno', function (Blueprint $table) {
            $table->string('nacionalidad', 60)->nullable()->after('fecha_nacimiento');
            $table->string('tipo_documento', 30)->nullable()->after('nacionalidad');
            $table->string('numero_documento', 30)->nullable()->after('tipo_documento');
        });

        DB::table('alumno')->whereNull('nacionalidad')->update(['nacionalidad' => 'Guatemalteca']);
        DB::table('alumno')->whereNull('tipo_documento')->update(['tipo_documento' => 'cui']);
        DB::statement("UPDATE alumno SET numero_documento = 'PENDIENTE-' || id_alumno WHERE numero_documento IS NULL");

        Schema::table('alumno', function (Blueprint $table) {
            $table->string('nacionalidad', 60)->default('Guatemalteca')->nullable(false)->change();
            $table->string('tipo_documento', 30)->nullable(false)->change();
            $table->string('numero_documento', 30)->nullable(false)->change();

            $table->unique('numero_documento');
        });

        DB::statement("ALTER TABLE alumno ADD CONSTRAINT alumno_tipo_documento_check CHECK (tipo_documento IN ('cui', 'pasaporte'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE alumno DROP CONSTRAINT IF EXISTS alumno_tipo_documento_check');

        Schema::table('alumno', function (Blueprint $table) {
            $table->dropUnique(['numero_documento']);
            $table->dropColumn(['nacionalidad', 'tipo_documento', 'numero_documento']);
        });
    }
};
