<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username')->nullable()->unique()->after('name');
            });
        }

        if (Schema::hasTable('playlists')) {
            Schema::table('playlists', function (Blueprint $table) {
                if (!Schema::hasColumn('playlists', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('id');
                }
                if (!Schema::hasColumn('playlists', 'user_name')) {
                    $table->string('user_name')->nullable()->after('user_id');
                }
            });
        }

        if (Schema::hasTable('reviews')) {
            Schema::table('reviews', function (Blueprint $table) {
                if (!Schema::hasColumn('reviews', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('id');
                }
                if (!Schema::hasColumn('reviews', 'user_name')) {
                    $table->string('user_name')->nullable()->after('user_id');
                }
            });
        }

        // Seed 4 akun anggota Kelompok 2 agar langsung siap digunakan saat demo maupun testing
        $defaultUsers = [
            [
                'name' => 'Muhammad Surya Triabadi',
                'username' => 'surya',
                'email' => 'surya@minima.id',
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Al Muzamil Sahri Ramadhan',
                'username' => 'muzamil',
                'email' => 'muzamil@minima.id',
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Benaya Bagas Christianto',
                'username' => 'benaya',
                'email' => 'benaya@minima.id',
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Reihan Rismana',
                'username' => 'reihan',
                'email' => 'reihan@minima.id',
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($defaultUsers as $u) {
            $exists = DB::table('users')
                ->where('email', $u['email'])
                ->orWhere('username', $u['username'])
                ->exists();
            if (!$exists) {
                DB::table('users')->insert($u);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('playlists')) {
            Schema::table('playlists', function (Blueprint $table) {
                $cols = array_filter(['user_id', 'user_name'], fn($c) => Schema::hasColumn('playlists', $c));
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }

        if (Schema::hasTable('reviews')) {
            Schema::table('reviews', function (Blueprint $table) {
                $cols = array_filter(['user_id', 'user_name'], fn($c) => Schema::hasColumn('reviews', $c));
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('username');
            });
        }
    }
};
