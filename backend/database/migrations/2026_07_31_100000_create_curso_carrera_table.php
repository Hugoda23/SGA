<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curso_carrera', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_curso')->constrained('curso', 'id_curso')->onDelete('cascade');
            $table->foreignId('id_carrera')->constrained('carrera', 'id_carrera')->onDelete('cascade');
            $table->unique(['id_curso', 'id_carrera']);
        });

        DB::table('curso')
            ->whereNotNull('id_carrera')
            ->get(['id_curso', 'id_carrera'])
            ->each(fn ($row) => DB::table('curso_carrera')->insertOrIgnore([
                'id_curso' => $row->id_curso,
                'id_carrera' => $row->id_carrera,
            ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('curso_carrera');
    }
};
