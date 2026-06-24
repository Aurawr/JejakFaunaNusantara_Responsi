<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KuisHasil extends Model
{
    protected $table = 'kuis_hasil';

    protected $fillable = ['user_id', 'skor', 'jumlah_benar', 'jumlah_soal'];

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
