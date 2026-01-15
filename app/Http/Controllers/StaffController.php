<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use App\Models\Counter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StaffController extends Controller
{
    // 1. Tampilkan Dashboard Petugas
    public function index()
    {
        // Ambil semua loket untuk dipilih di awal (Login sederhana)
        return Inertia::render('Staff/Index', [
            'counters' => Counter::all()
        ]);
    }

    // 2. Tampilkan Halaman Kontrol (Setelah pilih Loket)
    public function dashboard($counterId)
    {
        $counter = Counter::findOrFail($counterId);
        $today = Carbon::today();

        // Cek apakah loket ini sedang melayani seseorang (Status 'called')
        $currentServing = Queue::where('counter_id', $counter->id)
            ->where('status', 'called')
            ->whereDate('created_at', $today)
            ->first();

        // Hitung sisa antrian yang belum dipanggil (Waiting)
        // Logika: Ambil antrian yang service_id-nya cocok dengan loket ini? 
        // (Sederhana dulu: Kita anggap semua loket bisa panggil semua layanan, 
        // atau nanti difilter per layanan).
        $waitingCount = Queue::where('status', 'waiting')
            ->whereDate('created_at', $today)
            ->count();

        return Inertia::render('Staff/Dashboard', [
            'counter' => $counter,
            'currentServing' => $currentServing,
            'waitingCount' => $waitingCount,
            'lastCalled' => session('last_called_ticket') // Opsional: untuk notif
        ]);
    }

    // 3. Logic: Panggil Antrian Berikutnya
    public function callNext(Request $request)
    {
        $counter = Counter::findOrFail($request->counter_id);
        $today = Carbon::today();

        // A. Selesaikan dulu antrian sebelumnya (jika ada yg lupa diselesaikan)
        Queue::where('counter_id', $counter->id)
            ->where('status', 'called')
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
            'counter_id' => $counter->id
        ]);

        return back()->with('last_called_ticket', $nextQueue);
    }

    // 4. Logic: Tandai Selesai
    public function complete(Request $request)
    {
        $queue = Queue::findOrFail($request->queue_id);
        $queue->update(['status' => 'completed']);
        
        return back();
    }

    public function recall(Request $request)
    {
        // Cari antrian yang sedang "called" di loket ini hari ini
        $queue = Queue::where('counter_id', $request->counter_id)
            ->where('status', 'called')
            ->whereDate('created_at', Carbon::today())
            ->first();

        if ($queue) {
            $queue->touch(); // FUNGSI AJAIB: Update 'updated_at' jadi sekarang
        }

        return back();
    }
}