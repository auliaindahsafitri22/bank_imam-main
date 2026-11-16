<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kecamatan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kecamatans';
    protected $fillable = ['nama_kecamatan'];

    public function bsuList()
    {
        return $this->hasMany(BSU::class, 'kecamatan_id', 'id');
    }
}
