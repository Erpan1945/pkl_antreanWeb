<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Queue;
use Carbon\Carbon;
use App\Http\Controllers\Controller;

class DisplayController extends Controller
{
    public function index()
    {
        // 1. Ambil datanya terlebih dahulu (pindahkan logika dari getData ke sini)
        $dataAntrean = Queue::with(['service', 'counter']) 
            ->whereDate('created_at', Carbon::today())
            ->whereIn('status', ['waiting', 'called']) 
            ->orderByRaw("CASE WHEN status = 'called' THEN 1 ELSE 2 END") 
            ->orderBy('updated_at', 'desc') 
            ->get();

        // 2. Kirim datanya ke Vue Inertia
        return Inertia::render('Display/Index', [
            'queues' => $dataAntrean, 
        ]);
    }

    // Fungsi ini boleh dihapus jika tidak dipakai oleh aplikasi lain (misal Android), 
    // karena Inertia Vue sudah mengambil data langsung dari fungsi index() di atas.
    // public function getData()
    // {
    //     $queues = Queue::with(['service', 'counter']) 
    //         ->whereDate('created_at', Carbon::today())
    //         ->whereIn('status', ['waiting', 'called']) 
    //         ->orderByRaw("CASE WHEN status = 'called' THEN 1 ELSE 2 END") 
    //         ->orderBy('updated_at', 'desc') 
    //         ->get();

    //     return response()->json($queues);
    // }
}