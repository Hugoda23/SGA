<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pensum', function (Blueprint $table) {
            $table->foreignId('id_grado')->nullable()->after('id_curso')->constrained('grado', 'id_grado')->onDelete('cascade');
        });

        DB::table('pensum')
            ->whereNotNull('grado')
            ->get(['id_pensum', 'grado'])
            ->each(function ($row) {
                $grado = DB::table('grado')
                    ->where('nombre', $row->grado)
                    ->orWhere('nombre', 'like', $row->grado . '%')
                    ->first();

                if ($grado) {
                    DB::table('pensum')->where('id_pensum', $row->id_pensum)->update(['id_grado' => $grado->id_grado]);
                }
            });

        Schema::table('pensum', function (Blueprint $table) {
            $table->dropColumn('grado');
        });
    }

    public function down(): void
    {
        Schema::table('pensum', function (Blueprint $table) {
            $table->string('grado', 50)->nullable()->after('id_curso');
        });

        DB::table('pensum')
            ->whereNotNull('id_grado')
            ->get(['id_pensum', 'id_grado'])
            ->each(function ($row) {
                $nombre = DB::table('grado')->where('id_grado', $row->id_grado)->value('nombre');
                if ($nombre) {
                    DB::table('pensum')->where('id_pensum', $row->id_pensum)->update(['grado' => $nombre]);
                }
            });

        Schema::table('pensum', function (Blueprint $table) {
            $table->dropForeign(['id_grado']);
            $table->dropColumn('id_grado');
        });
    }
};
