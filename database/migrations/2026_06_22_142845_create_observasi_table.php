<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('observasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('spesies_id')->nullable()->constrained('spesies')->onDelete('set null');
            $table->foreignId('provinsi_id')->nullable()->constrained('provinsi')->onDelete('set null');
            $table->string('nama_usulan')->nullable()->comment('Nama yang diisi pelapor jika spesies belum pasti');
            $table->string('foto');
            $table->date('tanggal_amatan');
            $table->enum('sumber_lokasi', ['gps_realtime', 'klik_manual'])->default('klik_manual');
            $table->enum('status_verifikasi', ['pending', 'terverifikasi', 'ditolak'])->default('pending');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE observasi ADD COLUMN geom_point geometry(Point, 4326)');
    }

    public function down(): void
    {
        Schema::dropIfExists('observasi');
    }
};
