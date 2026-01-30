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
    // Halaman Utama
    public function index()
    {
        return Inertia::render('Staff/Index', [
            'counters' => Counter::all(),
            'auth' => ['user' => Auth::user()]
        ]);
    }

    // Dashboard Staff
    public function dashboard($counterId)
    {
        $counter = Counter::findOrFail($counterId);
        return Inertia::render('Staff/Dashboard', [
            'counter' => $counter,
            // Data awal (biar gak kosong saat loading pertama)
            'currentServing' => $this->getCurrentServing($counter->id),
            'waitingList' => $this->getWaitingList(),
            'stats' => $this->getDailyStats(),
            'waitingCount' => $this->getWaitingList()->count()
        ]);
    }

    // --- API REALTIME (Digunakan oleh Polling) ---
    public function getStats($counterId)
    {
        return response()->json([
            'currentServing' => $this->getCurrentServing($counterId),
            'waitingList'    => $this->getWaitingList(), // INI YANG DULU HILANG
            'waitingCount'   => $this->getWaitingList()->count(),
            'stats'          => $this->getDailyStats()
        ]);
    }

    // --- LOGIC TOMBOL ---
    public function callNext(Request $request)
    {
        $counterId = $request->counter_id;
        
        // 1. Selesaikan antrian lama (jika ada)
        Queue::where('counter_id', $counterId)
            ->whereIn('status', ['called', 'serving'])
            ->update(['status' => 'completed']);

        // 2. Ambil antrian berikutnya (FIFO)
        $next = Queue::where('status', 'waiting')
            ->whereDate('created_at', Carbon::today())
            ->orderBy('created_at', 'asc')
            ->first();

        if ($next) {
            $next->update([
                'status' => 'called',
                'counter_id' => $counterId,
                'updated_at' => now() // Trigger realtime display
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function recall(Request $request)
    {
        $queue = Queue::where('counter_id', $request->counter_id)
            ->whereIn('status', ['called', 'serving'])
            ->whereDate('created_at', Carbon::today())
            ->first();

        if ($queue) $queue->touch(); // Update waktu agar notifikasi bunyi lagi

        return response()->json(['success' => true]);
    }

    public function complete(Request $request)
    {
        Queue::where('id', $request->queue_id)->update(['status' => 'completed']);
        return response()->json(['success' => true]);
    }

    // --- HELPER QUERIES ---
    private function getCurrentServing($counterId) {
        return Queue::where('counter_id', $counterId)
            ->whereIn('status', ['called', 'serving'])
            ->whereDate('created_at', Carbon::today())
            ->first();
    }

    private function getWaitingList() {
        return Queue::where('status', 'waiting')
            ->whereDate('created_at', Carbon::today())
            ->orderBy('created_at', 'asc')
            ->get(); // Ambil semua data, bukan cuma count()
    }

    private function getDailyStats() {
        return [
            'total' => Queue::whereDate('created_at', Carbon::today())->count(),
            'finished' => Queue::where('status', 'completed')->whereDate('created_at', Carbon::today())->count(),
        ];
    }

    /**
     * 1. Halaman Pilih Loket (Setelah Login sebagai Staff)
     */
    // public function index()
    // {
    //     // Ambil data loket yang tersedia
    //     $counters = Counter::all();

    //     return Inertia::render('Staff/Index', [
    //         'counters' => $counters,
    //         'auth' => [
    //             'user' => Auth::user()
    //         ]
    //     ]);
    // }

    /**
     * 2. Dashboard Staff (Tempat Kerja Utama)
     */
    // public function dashboard($counterId)
    // {
    //     $counter = Counter::findOrFail($counterId);
    //     $today = Carbon::today();

    //     // Cek apakah loket ini sedang melayani seseorang (Status 'called' atau 'serving')
    //     $currentServing = Queue::where('counter_id', $counter->id)
    //         ->whereIn('status', ['called', 'serving'])
    //         ->whereDate('created_at', $today)
    //         ->first();

    //     // Hitung sisa antrian yang masih menunggu (Waiting)
    //     $waitingCount = Queue::where('status', 'waiting')
    //         ->whereDate('created_at', $today)
    //         ->count();

    //     return Inertia::render('Staff/Dashboard', [
    //         'counter' => $counter,
    //         'currentServing' => $currentServing,
    //         'waitingCount' => $waitingCount,
    //         // 'auth' otomatis dikirim oleh Inertia via HandleInertiaRequests middleware
    //     ]);
    // }

    /**
     * 3. Logic: Panggil Antrian Berikutnya
     */
    // public function callNext(Request $request)
    // {
    //     $counter = Counter::findOrFail($request->counter_id);
    //     $today = Carbon::today();

    //     // 1. Selesaikan antrian yang masih nyangkut (jika ada)
    //     Queue::where('counter_id', $counter->id)
    //         ->whereIn('status', ['called', 'serving'])
    //         ->update(['status' => 'completed']);

    //     // 2. Ambil antrian menunggu terlama (FIFO)
    //     $nextQueue = Queue::where('status', 'waiting')
    //         ->whereDate('created_at', $today)
    //         ->orderBy('created_at', 'asc') // Datang duluan, dilayani duluan
    //         ->first();

    //     if ($nextQueue) {
    //         $nextQueue->update([
    //             'status' => 'called',
    //             'counter_id' => $counter->id,
    //             // 'updated_at' otomatis berubah, jadi akan terdeteksi TV
    //         ]);
    //         return response()->json(['success' => true, 'message' => 'Antrian dipanggil']);
    //     }

    //     return response()->json(['success' => false, 'message' => 'Tidak ada antrian menunggu']);
    // }

    /**
     * 4. Logic: Tandai Selesai
     */
    // public function complete(Request $request)
    // {
    //     $queue = Queue::findOrFail($request->queue_id);
    //     $queue->update(['status' => 'completed']);
        
    //     return back();
    // }

    /**
     * 5. Logic: Panggil Ulang (Recall)
     */
    // public function recall(Request $request)
    // {
    //     // Cari antrian yang sedang "called" di loket ini hari ini
    //     $queue = Queue::where('counter_id', $request->counter_id)
    //         ->whereIn('status', ['called', 'serving'])
    //         ->whereDate('created_at', Carbon::today())
    //         ->first();

    //     if ($queue) {
    //         $queue->touch(); // FUNGSI AJAIB: Update 'updated_at' jadi sekarang agar terdeteksi TV sebagai "baru"
    //     }

    //     return back();
    // }

    /**
     * 6. API: Data Realtime untuk Polling (Dashboard Staff Auto-Update)
     */
    // public function getStats($counterId)
    // {
    //     $counter = Counter::findOrFail($counterId);
    //     $today = Carbon::today();

    //     // Data 1: Yang sedang dilayani
    //     $currentServing = Queue::where('counter_id', $counter->id)
    //         ->whereIn('status', ['called', 'serving'])
    //         ->whereDate('created_at', $today)
    //         ->first();

    //     // Data 2: Jumlah Menunggu
    //     $waitingQuery = Queue::where('status', 'waiting')->whereDate('created_at', $today);
    //     $waitingCount = $waitingQuery->count();
        
    //     // Data 3: LIST Menunggu (INI YANG DULU HILANG)
    //     $waitingList = $waitingQuery->orderBy('created_at', 'asc')->get();

    //     // Data 4: Statistik Harian
    //     $stats = [
    //         'total' => Queue::whereDate('created_at', $today)->count(),
    //         'finished' => Queue::where('status', 'completed')->whereDate('created_at', $today)->count(),
    //     ];

    //     return response()->json([
    //         'currentServing' => $currentServing,
    //         'waitingCount' => $waitingCount,
    //         'waitingList' => $waitingList, // Pastikan ini dikirim!
    //         'stats' => $stats
    //     ]);
    // }
}