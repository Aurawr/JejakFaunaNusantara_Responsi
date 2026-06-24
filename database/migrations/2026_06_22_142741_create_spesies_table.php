<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spesies', function (Blueprint $table) {
            $table->id();
            $table->string('nama_ilmiah');
            $table->string('nama_lokal');
            $table->enum('kelas_taksonomi', ['mamalia', 'burung', 'reptil', 'amfibi', 'ikan', 'serangga', 'tumbuhan']);
            $table->enum('status_konservasi', ['dilindungi', 'rentan', 'tidak_terancam', 'belum_dievaluasi'])->default('belum_dievaluasi');
            $table->string('foto_referensi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spesies');
    }
};
