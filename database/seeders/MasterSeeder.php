<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Data Layanan (UBAH JADI CUMA SATU)
        // Kita hanya masukkan 'Pelayanan Umum' agar sistem otomatis memilih ini.
        DB::table('services')->insert([
            [
                'name' => 'Pelayanan Umum', 
                'code' => 'A', 
                'created_at' => now()
            ],
        ]);

        // 2. Data Loket (Tetap biarkan ada beberapa untuk petugas)
        DB::table('counters')->insert([
            ['name' => 'Loket 1', 'is_active' => true, 'created_at' => now()],
            ['name' => 'Loket 2', 'is_active' => true, 'created_at' => now()],
        ]);
    }
}