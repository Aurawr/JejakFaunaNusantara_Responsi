<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');

        Schema::create('provinsi', function (Blueprint $table) {
            $table->id();
            $table->string('nama_provinsi');
            $table->enum('zona_biogeografi', ['asiatis', 'peralihan', 'australis']);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE provinsi ADD COLUMN geom_polygon geometry(MultiPolygon, 4326)');
    }

    public function down(): void
    {
        Schema::dropIfExists('provinsi');
    }
};
