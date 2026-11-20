<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Playlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_name',
        'name',
        'description',
        'image',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function musics()
    {
        return $this->belongsToMany(Music::class, 'playlist_music', 'playlist_id', 'music_id')->withTimestamps();
    }
}
