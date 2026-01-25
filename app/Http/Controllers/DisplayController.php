<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Queue;
use Carbon\Carbon;

class DisplayController extends Controller
{
    public function index()
    {
        return Inertia::render('Display/Index');
    }

    // FUNGSI API DATA
    public function getData()
    {
        $queues = Queue::with(['service', 'counter']) 
            ->whereDate('created_at', Carbon::today())
            // PERBAIKAN DI SINI: Gunakan 'called' (bukan calling)
            ->whereIn('status', ['waiting', 'called']) 
            
            // Prioritaskan yang statusnya 'called' di paling atas
            ->orderByRaw("CASE WHEN status = 'called' THEN 1 ELSE 2 END") 
            ->orderBy('updated_at', 'desc') 
            ->get();

        return response()->json($queues);
    }
}