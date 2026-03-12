<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Tetap jalankan MasterSeeder untuk Service & Counter
        $this->call(MasterSeeder::class);

        // 1. ADMIN (Username: su_admin | Pass: admin123)
        User::updateOrCreate(
            ['username' => 'su_admin'], 
            [
                'name' => 'Admin Utama',
                'email' => 'su_admin@asabri.co.id',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]
        );

        // 2. STAFF (Username: admin | Pass: admin123)
        User::updateOrCreate(
            ['username' => 'admin'], 
            [
                'name' => 'Staff Loket',
                'email' => 'admin@asabri.co.id',
                'password' => Hash::make('admin123'),
                'role' => 'staff',
            ]
        );
    }
}