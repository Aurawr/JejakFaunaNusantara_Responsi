<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KuisSoal extends Model
{
    protected $table = 'kuis_soal';

    protected $fillable = ['spesies_id', 'observasi_id', 'tipe_soal'];

    public function spesies()
    {
        return $this->belongsTo(Spesies::class);
    }

    public function observasi()
    {
        return $this->belongsTo(Observasi::class);
    }

    public function fotoSoal(): string
    {
        return $this->tipe_soal === 'tantangan_identifikasi' && $this->observasi
            ? $this->observasi->foto
            : $this->spesies->foto_referensi;
    }
}
