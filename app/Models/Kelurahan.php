<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kelurahan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kelurahans';
    protected $fillable = ['nama_kelurahan'];

    public function bsuList()
    {
        return $this->hasMany(BSU::class, 'kelurahan_id', 'id');
    }
}

