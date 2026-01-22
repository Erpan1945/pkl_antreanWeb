<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use App\Models\Counter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class StaffController extends Controller
{
    /**
     * 1. Halaman Pilih Loket (Setelah Login sebagai Staff)
     */
    public function index()
    {
        // Ambil data loket yang tersedia
        $counters = Counter::all();

        return Inertia::render('Staff/Index', [
            'counters' => $counters,
            'auth' => [
                'user' => Auth::user()
            ]
        ]);
    }

    /**
     * 2. Dashboard Staff (Tempat Kerja Utama)
     */
    public function dashboard($counterId)
    {
        $counter = Counter::findOrFail($counterId);
        $today = Carbon::today();

        // Cek apakah loket ini sedang melayani seseorang (Status 'called' atau 'serving')
        $currentServing = Queue::where('counter_id', $counter->id)
            ->whereIn('status', ['called', 'serving'])
            ->whereDate('created_at', $today)
            ->first();

        // Hitung sisa antrian yang masih menunggu (Waiting)
        $waitingCount = Queue::where('status', 'waiting')
            ->whereDate('created_at', $today)
            ->count();

        return Inertia::render('Staff/Dashboard', [
            'counter' => $counter,
            'currentServing' => $currentServing,
            'waitingCount' => $waitingCount,
            // 'auth' otomatis dikirim oleh Inertia via HandleInertiaRequests middleware
        ]);
    }

    /**
     * 3. Logic: Panggil Antrian Berikutnya
     */
    public function callNext(Request $request)
    {
        $counter = Counter::findOrFail($request->counter_id);
        $today = Carbon::today();

        // A. Selesaikan dulu antrian sebelumnya (jika ada yg lupa diselesaikan)
        Queue::where('counter_id', $counter->id)
            ->whereIn('status', ['called', 'serving'])
            ->update(['status' => 'completed']);

        // B. Cari antrian 'waiting' paling lama (First In First Out)
        // Disini kita bisa filter by Service jika perlu. Untuk skrg ambil global.
        $nextQueue = Queue::where('status', 'waiting')
            ->whereDate('created_at', $today)
            ->orderBy('id', 'asc') // ID kecil berarti datang duluan
            ->first();

        if (!$nextQueue) {
            return back()->with('message', 'Tidak ada antrian menunggu.');
        }

        // C. Update Status Antrian jadi 'called' & Masukkan ID Loket
        $nextQueue->update([
            'status' => 'called',
            'counter_id' => $counter->id,
            // 'updated_at' otomatis terupdate untuk trigger Display TV
        ]);

        return back(); // Tidak perlu kirim data, Dashboard.vue akan auto-refresh via props/polling
    }

    /**
     * 4. Logic: Tandai Selesai
     */
    public function complete(Request $request)
    {
        $queue = Queue::findOrFail($request->queue_id);
        $queue->update(['status' => 'completed']);
        
        return back();
    }

    /**
     * 5. Logic: Panggil Ulang (Recall)
     */
    public function recall(Request $request)
    {
        // Cari antrian yang sedang "called" di loket ini hari ini
        $queue = Queue::where('counter_id', $request->counter_id)
            ->whereIn('status', ['called', 'serving'])
            ->whereDate('created_at', Carbon::today())
            ->first();

        if ($queue) {
            $queue->touch(); // FUNGSI AJAIB: Update 'updated_at' jadi sekarang agar terdeteksi TV sebagai "baru"
        }

        return back();
    }

    /**
     * 6. API: Data Realtime untuk Polling (Dashboard Staff Auto-Update)
     */
    public function getStats($counterId)
    {
        $counter = Counter::findOrFail($counterId);
        $today = Carbon::today();

        // Cek antrian yang sedang dilayani
        $currentServing = Queue::where('counter_id', $counter->id)
            ->whereIn('status', ['called', 'serving'])
            ->whereDate('created_at', $today)
            ->first();

        // Hitung antrian menunggu
        $waitingCount = Queue::where('status', 'waiting')
            ->whereDate('created_at', $today)
            ->count();

        return response()->json([
            'currentServing' => $currentServing,
            'waitingCount' => $waitingCount
        ]);
    }
}