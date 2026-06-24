<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kuis_hasil', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('skor')->default(0);
            $table->integer('jumlah_benar')->default(0);
            $table->integer('jumlah_soal')->default(5);
            $table->timestamp('dikerjakan_pada')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kuis_hasil');
    }
};
