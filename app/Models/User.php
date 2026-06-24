<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'total_poin'];

    protected $hidden = ['password', 'remember_token'];

    public function observasi()
    {
        return $this->hasMany(Observasi::class);
    }

    public function kuisHasil()
    {
        return $this->hasMany(KuisHasil::class);
    }

    public function skorRiwayat()
    {
        return $this->hasMany(SkorRiwayat::class);
    }

    public function isVerifikator(): bool
    {
        return in_array($this->role, ['verifikator', 'admin']);
    }

    public function tambahPoin(int $poin, string $sumber, ?string $keterangan = null): void
    {
        $this->skorRiwayat()->create([
            'poin' => $poin,
            'sumber_poin' => $sumber,
            'keterangan' => $keterangan,
        ]);

        $this->increment('total_poin', $poin);
    }
}
