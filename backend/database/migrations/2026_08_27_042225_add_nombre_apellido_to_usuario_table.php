<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Nombre/apellido propios del usuario — para personal (admin, director,
     * secretaria) que no tiene perfil de Alumno ni Catedratico, que son los
     * únicos lugares donde existía un nombre hasta ahora.
     */
    public function up(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            $table->string('nombre', 100)->nullable()->after('username');
            $table->string('apellido', 100)->nullable()->after('nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            $table->dropColumn(['nombre', 'apellido']);
        });
    }
};
