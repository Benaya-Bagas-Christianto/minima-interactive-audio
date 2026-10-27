<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Artist extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'listeners',
        'description',
        'image',
    ];

    public function musics()
    {
        return $this->hasMany(Music::class, 'artist', 'name');
    }
}
