<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spesies_provinsi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spesies_id')->constrained('spesies')->onDelete('cascade');
            $table->foreignId('provinsi_id')->constrained('provinsi')->onDelete('cascade');
            $table->text('deskripsi')->nullable();
            $table->timestamps();

            $table->unique(['spesies_id', 'provinsi_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spesies_provinsi');
    }
};
