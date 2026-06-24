<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Spesies extends Model
{
    protected $table = 'spesies';

    protected $fillable = [
        'nama_ilmiah', 'nama_lokal', 'kelas_taksonomi',
        'status_konservasi', 'foto_referensi',
    ];

    public function provinsi()
    {
        return $this->belongsToMany(Provinsi::class, 'spesies_provinsi')
            ->withPivot('deskripsi');
    }

    public function observasi()
    {
        return $this->hasMany(Observasi::class);
    }

    public function kuisSoal()
    {
        return $this->hasMany(KuisSoal::class);
    }
}
