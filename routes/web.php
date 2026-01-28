<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use Inertia\Inertia;

// Import Controller
use App\Http\Controllers\TicketController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\DisplayController;
use App\Http\Controllers\AdminDashboardController;
use App\Models\Queue;
// use App\Http\Controllers\ProfileController; // (Dimatikan karena tidak dipakai)

/*
|--------------------------------------------------------------------------
| 1. ZONA PUBLIK (BISA DIAKSES TANPA LOGIN)
|--------------------------------------------------------------------------
| Area ini untuk:
| - Kiosk (Pendaftaran Mandiri oleh Tamu)
| - Display TV (Monitor Antrian)
| - Halaman Login
*/

// Redirect root ('/') langsung ke Login
// Route::get('/', function () {
//     return redirect()->route('login');
// });

// FIX: Logika Pintar untuk Halaman Depan
Route::get('/', function () {
    // 1. Cek apakah user sudah login?
    if (Auth::check()) {
        $role = Auth::user()->role;
        
        // 2. Jika sudah, arahkan sesuai Role
        if ($role === 'admin') {
            return redirect()->route('admin.dashboard');
        } elseif ($role === 'staff') {
            return redirect()->route('staff.index');
        }
    }

    // 3. Jika belum login, baru lempar ke halaman login
    return redirect()->route('login');
});

// --- KIOSK (PENDAFTARAN) ---
Route::get('/kiosk', [TicketController::class, 'index'])->name('kiosk.index');
Route::post('/kiosk/ticket', [TicketController::class, 'store'])->name('kiosk.store');

// --- DISPLAY TV ---
Route::get('/display', [DisplayController::class, 'index'])->name('display.index');
Route::get('/display/data', [DisplayController::class, 'getData'])->name('display.data'); // API Auto-refresh


/*
|--------------------------------------------------------------------------
| 2. ZONA TERKUNCI (WAJIB LOGIN)
|--------------------------------------------------------------------------
| Area ini hanya bisa diakses oleh user yang sudah login (Staff & Admin).
*/
Route::middleware(['auth'])->group(function () {

    // --- AREA STAFF / LOKET (Dipindah ke sini agar wajib login) ---
    
    // Halaman Pilih Loket (Pertama kali staff masuk)
    Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
    
    // Dashboard Utama Petugas (Kerja melayani antrian)
    Route::get('/staff/{counterId}/dashboard', [StaffController::class, 'dashboard'])->name('staff.dashboard');
    
    // API & Action Tombol Staff
    Route::get('/staff/{counterId}/stats', [StaffController::class, 'getStats'])->name('staff.stats');
    Route::post('/staff/call-next', [StaffController::class, 'callNext'])->name('staff.callNext');
    Route::post('/staff/recall', [StaffController::class, 'recall'])->name('staff.recall');
    Route::post('/staff/complete', [StaffController::class, 'complete'])->name('staff.complete');

    // --- EXPORT EXCEL (Bisa diakses Staff & Admin) ---
    // Saya taruh di luar grup admin supaya staff bisa download laporan juga
    Route::get('/admin/export', [AdminDashboardController::class, 'export'])->name('admin.export');


    /*
    |--------------------------------------------------------------------------
    | 3. ZONA KHUSUS ADMIN (ROLE: ADMIN)
    |--------------------------------------------------------------------------
    | Staff biasa TIDAK BISA masuk ke sini (akan error 403 Forbidden).
    | Pastikan Anda sudah membuat middleware 'role' di langkah sebelumnya.
    */
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    });

    // --- PROFILE (Dimatikan sesuai request "tidak ada page profile") ---
    // Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    // Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

/* =========================
   DASHBOARD STAFF (INERTIA)
========================= */
Route::get('/staff', function () {

    return Inertia::render('Staff/Dashboard', [
        'stats' => [
            'total'    => Queue::count(),
            'finished' => Queue::where('status', 'selesai')->count(),
        ],
        'waitingCount'   => Queue::where('status', 'menunggu')->count(),
        'waitingList'    => Queue::where('status', 'menunggu')
                                ->orderBy('created_at')
                                ->get(),
        'currentServing' => Queue::where('status', 'dipanggil')->first(),
        'counter'        => [
            'id' => 1
        ]
    ]);
})->name('staff.dashboard');

/* =========================
   ACTIONS
========================= */

// PANGGIL BERIKUTNYA
Route::post('/staff/next', function () {

    $next = Queue::where('status', 'menunggu')
        ->orderBy('created_at')
        ->first();

    if ($next) {
        $next->update(['status' => 'dipanggil']);
    }

    return response()->json(['success' => true]);
});

// SELESAI
Route::post('/staff/complete', function () {
    request()->validate([
        'queue_id' => 'required'
    ]);

    Queue::where('id', request('queue_id'))
        ->update(['status' => 'selesai']);

    return response()->json(['success' => true]);
});

// PANGGIL ULANG (dummy)
Route::post('/staff/recall', function () {
    return response()->json(['success' => true]);
});

/* =========================
   API POLLING REALTIME
========================= */
Route::get('/staff/stats/{counter}', function () {
    return response()->json([
        'currentServing' => Queue::where('status', 'dipanggil')->first(),
        'waitingCount'   => Queue::where('status', 'menunggu')->count(),
        'waitingList'    => Queue::where('status', 'menunggu')
                                ->orderBy('created_at')
                                ->get(),
        'stats' => [
            'total'    => Queue::count(),
            'finished' => Queue::where('status', 'selesai')->count(),
        ]
    ]);
});
