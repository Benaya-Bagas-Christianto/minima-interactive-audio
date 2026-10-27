<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'music_id',
        'music_title',
        'artist',
        'rating',
        'review',
    ];

    public function music()
    {
        return $this->belongsTo(Music::class, 'music_id');
    }
}
