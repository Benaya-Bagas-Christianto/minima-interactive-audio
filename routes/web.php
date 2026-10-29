<?php

use App\Http\Controllers\MusicController;
use App\Http\Controllers\PlaylistController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

// Halaman Utama Minima Platform
Route::get('/', [MusicController::class, 'index'])->name('home');

// API Routes untuk Interaksi Multimedia Front-end
Route::prefix('api')->group(function () {
    // Musik & Artis
    Route::get('/musics/search', [MusicController::class, 'apiSearch'])->name('api.musics.search');
    Route::get('/musics/suggestions', [MusicController::class, 'apiSuggestions'])->name('api.musics.suggestions');
    Route::get('/musics/{id}', [MusicController::class, 'show'])->name('api.musics.show');
    Route::get('/musics/{id}/audio', [MusicController::class, 'getRealAudio'])->name('api.musics.audio');
    Route::get('/musics/{id}/stream', [MusicController::class, 'streamAudio'])->name('api.musics.stream');
    Route::get('/musics/{id}/lyrics', [MusicController::class, 'getLyrics'])->name('api.musics.lyrics');
    Route::match(['get', 'post'], '/musics/{id}/prepare-audio', [MusicController::class, 'prepareFullAudio'])->name('api.musics.prepareAudio');
    Route::get('/artists/{name}', [MusicController::class, 'showArtist'])->name('api.artists.show');

    // Playlist
    Route::get('/playlists', [PlaylistController::class, 'index'])->name('api.playlists.index');
    Route::get('/playlists/{id}', [PlaylistController::class, 'show'])->name('api.playlists.show');
    Route::post('/playlists', [PlaylistController::class, 'store'])->name('api.playlists.store');
    Route::put('/playlists/{id}', [PlaylistController::class, 'update'])->name('api.playlists.update');
    Route::post('/playlists/{id}/image', [PlaylistController::class, 'updateImage'])->name('api.playlists.updateImage');
    Route::post('/playlists/{id}/add', [PlaylistController::class, 'addSong'])->name('api.playlists.addSong');
    Route::delete('/playlists/{id}/songs/{musicId}', [PlaylistController::class, 'removeSong'])->name('api.playlists.removeSong');
    Route::delete('/playlists/{id}', [PlaylistController::class, 'destroy'])->name('api.playlists.destroy');

    // Review
    Route::get('/reviews', [ReviewController::class, 'index'])->name('api.reviews.index');
    Route::post('/reviews', [ReviewController::class, 'store'])->name('api.reviews.store');
    Route::put('/reviews/{id}', [ReviewController::class, 'update'])->name('api.reviews.update');
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy'])->name('api.reviews.destroy');
});
