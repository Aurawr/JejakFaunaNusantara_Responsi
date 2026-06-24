<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kuis_soal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spesies_id')->constrained('spesies')->onDelete('cascade');
            $table->foreignId('observasi_id')->nullable()->constrained('observasi')->onDelete('cascade');
            $table->enum('tipe_soal', ['edukasi', 'tantangan_identifikasi'])->default('edukasi');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kuis_soal');
    }
};
