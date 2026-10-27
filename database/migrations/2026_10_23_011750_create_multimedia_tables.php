<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('artists', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('listeners')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });

        Schema::create('musics', function (Blueprint $table) {
            $table->id();
            $table->string('title')->index();
            $table->string('artist')->index();
            $table->string('album')->nullable();
            $table->integer('year')->nullable()->index();
            $table->string('genre')->index();
            $table->string('duration')->nullable();
            $table->decimal('rating', 3, 1)->default(4.5);
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('audio_url')->nullable();
            $table->string('video_id')->nullable();
            $table->timestamps();
        });

        Schema::create('playlists', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });

        Schema::create('playlist_music', function (Blueprint $table) {
            $table->id();
            $table->foreignId('playlist_id')->constrained('playlists')->onDelete('cascade');
            $table->foreignId('music_id')->constrained('musics')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('music_id')->constrained('musics')->onDelete('cascade');
            $table->string('music_title');
            $table->string('artist');
            $table->integer('rating')->default(5);
            $table->text('review');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('playlist_music');
        Schema::dropIfExists('playlists');
        Schema::dropIfExists('musics');
        Schema::dropIfExists('artists');
    }
};
