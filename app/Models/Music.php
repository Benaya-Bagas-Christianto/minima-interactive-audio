<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Music extends Model
{
    use HasFactory;

    protected $table = 'musics';

    protected $fillable = [
        'title',
        'artist',
        'album',
        'year',
        'genre',
        'duration',
        'rating',
        'description',
        'image',
        'audio_url',
        'video_id',
    ];

    public function playlists()
    {
        return $this->belongsToMany(Playlist::class, 'playlist_music', 'music_id', 'playlist_id')->withTimestamps();
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'music_id');
    }
}
