<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Layanan
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 5);
            $table->timestamps();
        });

        // 2. Tabel Loket
        Schema::create('counters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Tabel Antrian (DENGAN NAMA KOLOM BARU)
        Schema::create('queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->foreignId('counter_id')->nullable()->constrained()->onDelete('set null');
            
            // --- KOLOM BARU ---
            $table->string('guest_name');      
            $table->string('identity_number'); 
            $table->string('phone_number')->nullable();
            $table->string('purpose')->nullable();
            // ------------------

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