<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Service;
use App\Models\Counter;

class MasterSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Pakai Eloquent untuk Service
        Service::updateOrCreate(
            ['code' => 'A'],
            ['name' => 'Pelayanan Umum']
        );

        // 2. Pakai Eloquent untuk Counter (Ini yang bikin lancar di Postgres)
        Counter::updateOrCreate(
            ['name' => 'Loket 1'],
            ['is_active' => DB::raw('true')]
        );

        Counter::updateOrCreate(
            ['name' => 'Loket 2'],
            ['is_active' => DB::raw('true')]
        );
    }
}