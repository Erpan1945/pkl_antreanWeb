<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DisplayController extends Controller
{
    // 1. Tampilkan Halaman TV
    public function index()
    {
        return Inertia::render('Display/Index');
    }

    // 2. API untuk mengambil data terbaru (Dipanggil setiap 3 detik oleh Vue)
    public function getData()
    {
        // Ambil antrian yang statusnya SEDANG DIPANGGIL ('called')
        // Urutkan dari yang paling baru diupdate (supaya yang baru dipanggil muncul paling atas)
        $activeQueues = Queue::with(['service', 'counter'])
            ->where('status', 'called')
            ->whereDate('created_at', now())
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json($activeQueues);
    }
}