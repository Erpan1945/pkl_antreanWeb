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

        // ==========================================
        // 1. KELOMPOK ADMIN (Akses Penuh)
        // ==========================================
        
        // Admin 1 (Pak Deni)
        User::factory()->create([
            'name' => 'Admin Utama (Pak Deni)',
            'email' => 'admin001@asabri.co.id',
            'password' => bcrypt('pakdeni123'),
            'role' => 'admin', 
        ]);

        // Admin 2 (Bu Dian)
        User::factory()->create([
            'name' => 'Admin 2 (Bu Dian)',
            'email' => 'admin010@asabri.co.id',
            'password' => bcrypt('budian123'),
            'role' => 'admin',
        ]);

        // Admin 3 (Bu Farah)
        User::factory()->create([
            'name' => 'Admin 3 (Bu Farah)',
            'email' => 'admin011@asabri.co.id',
            'password' => bcrypt('bufarah123'),
            'role' => 'admin',
        ]);

        // ==========================================
        // 2. KELOMPOK STAFF LOKET (Akses Terbatas)
        // ==========================================

        // Staff 1 (Kak Linda) -> Role diubah jadi 'staff'
        User::factory()->create([
            'name' => 'Staff Loket 1 (Kak Linda)',
            'email' => 'admin100@asabri.co.id',
            'password' => bcrypt('kaklinda123'),
            'role' => 'staff', // <-- INI STAFF
        ]);

        // Staff 2 (Kak Alya) -> Role diubah jadi 'staff'
        User::factory()->create([
            'name' => 'Staff Loket 2 (Kak Alya)',
            'email' => 'admin101@asabri.co.id',
            'password' => bcrypt('kakalya123'),
            'role' => 'staff', // <-- INI STAFF
        ]);
    }
}
