<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provinsi extends Model
{
    protected $table = 'provinsi';

    protected $fillable = ['nama_provinsi', 'zona_biogeografi'];

    public function spesies()
    {
        return $this->belongsToMany(Spesies::class, 'spesies_provinsi')
            ->withPivot('deskripsi');
    }

    public function observasi()
    {
        return $this->hasMany(Observasi::class);
    }

    public function getWarnaZonaAttribute(): string
    {
        return match ($this->zona_biogeografi) {
            'asiatis' => '#A5D6A7',
            'peralihan' => '#FFE082',
            'australis' => '#90CAF9',
            default => '#E0E0E0',
        };
    }
}
