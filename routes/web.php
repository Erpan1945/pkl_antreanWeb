<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\DisplayController;

// Halaman Display TV
Route::get('/display', [DisplayController::class, 'index'])->name('display.index');

// API Data Realtime (Penting untuk Auto-refresh)
Route::get('/display/data', [DisplayController::class, 'getData'])->name('display.data');

// Halaman Awal Petugas (Pilih Loket)
Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');

// Route untuk Panggil Ulang
Route::post('/staff/recall', [StaffController::class, 'recall'])->name('staff.recall');

// Dashboard Utama Petugas
Route::get('/staff/{counterId}/dashboard', [StaffController::class, 'dashboard'])->name('staff.dashboard');

// API Data Realtime Staff (Polling)
Route::get('/staff/{counterId}/stats', [StaffController::class, 'getStats'])->name('staff.stats');

// Action Tombol
Route::post('/staff/call-next', [StaffController::class, 'callNext'])->name('staff.callNext');
Route::post('/staff/complete', [StaffController::class, 'complete'])->name('staff.complete');

// Halaman Kiosk (Layar Sentuh)
Route::get('/kiosk', [TicketController::class, 'index'])->name('kiosk.index');

// Proses Ambil Tiket (API)
Route::post('/kiosk/ticket', [TicketController::class, 'store'])->name('kiosk.store');

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
