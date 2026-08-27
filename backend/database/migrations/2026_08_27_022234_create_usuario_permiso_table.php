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
     * Excepciones de permisos por usuario individual, por encima de los que
     * ya le dan sus roles: 'concedido' = true otorga ese permiso aunque
     * ninguno de sus roles lo tenga; false se lo quita aunque su rol sí lo
     * tenga. Un usuario sin fila aquí para un permiso simplemente hereda lo
     * que le da su rol.
     */
    public function up(): void
    {
        Schema::create('usuario_permiso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_usuario')->constrained('usuario', 'id_usuario')->onDelete('cascade');
            $table->foreignId('id_permiso')->constrained('permiso', 'id_permiso')->onDelete('cascade');
            $table->boolean('concedido');
            $table->timestamps();

            $table->unique(['id_usuario', 'id_permiso']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuario_permiso');
    }
};
