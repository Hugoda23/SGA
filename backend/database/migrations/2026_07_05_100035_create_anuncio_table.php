<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anuncio', function (Blueprint $table) {
            $table->id('id_anuncio');
            $table->foreignId('id_asignacion')->constrained('asignacion', 'id_asignacion')->onDelete('cascade');
            $table->string('titulo', 200);
            $table->text('contenido')->nullable();
            $table->timestamp('fecha_publicacion')->useCurrent();

            $table->index('id_asignacion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anuncio');
    }
};
