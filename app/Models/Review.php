<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_name',
        'music_id',
        'music_title',
        'artist',
        'rating',
        'review',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function music()
    {
        return $this->belongsTo(Music::class, 'music_id');
    }
}
