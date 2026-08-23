<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificacion', function (Blueprint $table) {
            $table->id('id_notificacion');
            $table->foreignId('id_usuario')->constrained('usuario', 'id_usuario')->onDelete('cascade');
            $table->text('mensaje');
            $table->timestamp('fecha')->useCurrent();
            $table->boolean('leido')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificacion');
    }
};
