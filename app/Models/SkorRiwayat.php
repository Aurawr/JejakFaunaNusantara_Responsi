<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkorRiwayat extends Model
{
    protected $table = 'skor_riwayat';

    protected $fillable = ['user_id', 'poin', 'sumber_poin', 'keterangan'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
