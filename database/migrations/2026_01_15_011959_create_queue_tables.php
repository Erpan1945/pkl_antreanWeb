<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Layanan (Service) - Contoh: CS, Teller
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');      // Nama Layanan
            $table->string('code', 5);   // Kode Cetak (A, B, C)
            $table->timestamps();
        });

        // 2. Tabel Loket (Counter) - Contoh: Loket 1, Loket 2
        Schema::create('counters', function (Blueprint $table) {
            $table->id();
            $table->string('name');      // Nama Loket
            $table->boolean('is_active')->default(true); // Status Buka/Tutup
            $table->timestamps();
        });

        // 3. Tabel Antrian (Queues) - Transaksi Utama
        Schema::create('queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->foreignId('counter_id')->nullable()->constrained()->onDelete('set null');
            
            // --- UBAH DARI STUDENT JADI GUEST/UMUM ---
            $table->string('guest_name');        // Nama Pengunjung
            $table->string('identity_number');   // NRP
            // ------------------------------------------

            $table->integer('number');
            $table->string('ticket_code');
            $table->enum('status', ['waiting', 'called', 'completed', 'skipped'])->default('waiting');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queues');
        Schema::dropIfExists('counters');
        Schema::dropIfExists('services');
    }
};