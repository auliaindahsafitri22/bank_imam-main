<?php

namespace App\Models;

use App\Models\Reward;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TukarPoin extends Model
{
    use HasFactory;

    protected $table = 'tukar_poins';
    protected $guarded = [];

    public function reward()
    {
        return $this->belongsTo(Reward::class, 'reward_id');
    }

    public function kelurahan()
    {
        return $this->belongsTo(Kelurahan::class, 'kelurahan_id', 'id');
    }

    public function kecamatan()
    {
        return $this->kelurahan();
    }
}
