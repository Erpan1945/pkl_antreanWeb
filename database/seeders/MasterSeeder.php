<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Data Layanan
        DB::table('services')->insert([
            ['name' => 'Customer Service', 'code' => 'A', 'created_at' => now()],
            ['name' => 'Teller / Kasir',   'code' => 'B', 'created_at' => now()],
            ['name' => 'Pengaduan',        'code' => 'C', 'created_at' => now()],
        ]);

        // 2. Data Loket
        DB::table('counters')->insert([
            ['name' => 'Loket 1', 'is_active' => true, 'created_at' => now()],
            ['name' => 'Loket 2', 'is_active' => true, 'created_at' => now()],
        ]);
    }
}