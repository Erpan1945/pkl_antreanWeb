<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(MasterSeeder::class);

        // Di dalam method run():
        \App\Models\User::factory()->create([
            'name' => 'Admin Utama',
            'email' => 'admin1@asabri.co.id',
            'password' => bcrypt('asabri123'), // Password default
            'role' => 'admin', // Pastikan Anda punya kolom role atau logikanya
        ]);

        // Buat 4 admin lainnya jika perlu, atau pakai loop
        for ($i = 2; $i <= 5; $i++) {
            \App\Models\User::factory()->create([
                'name' => 'Staff Loket ' . $i,
                'email' => 'admin' . $i . '@asabri.co.id',
                'password' => bcrypt('asabri123'), // Password default
            ]);
        }
    }
}
