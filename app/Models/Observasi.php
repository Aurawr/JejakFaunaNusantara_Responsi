<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Observasi extends Model
{
    protected $table = 'observasi';

    protected $fillable = [
        'user_id', 'spesies_id', 'provinsi_id', 'nama_usulan',
        'foto', 'tanggal_amatan', 'sumber_lokasi', 'status_verifikasi', 'catatan',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function spesies()
    {
        return $this->belongsTo(Spesies::class);
    }

    public function provinsi()
    {
        return $this->belongsTo(Provinsi::class);
    }

    public function kuisSoal()
    {
        return $this->hasMany(KuisSoal::class);
    }

    public function namaTampil(): string
    {
        return $this->spesies?->nama_lokal ?? $this->nama_usulan ?? 'Belum diketahui';
    }
}
