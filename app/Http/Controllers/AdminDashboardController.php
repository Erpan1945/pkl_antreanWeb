<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use App\Exports\QueueExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        
        // Ambil data hari ini
        $queues = Queue::with('service', 'counter')
            ->whereDate('created_at', $today)
            ->orderBy('created_at', 'desc')
            ->get();

        // --- 1. Statistik Kartu ---
        $completedQueues = $queues->where('status', 'completed');
        
        // Hitung rata-rata waktu (menit)
        $avgTime = 0;
        if ($completedQueues->count() > 0) {
            $totalDuration = $completedQueues->reduce(function ($carry, $item) {
                return $carry + $item->created_at->diffInMinutes($item->updated_at);
            }, 0);
            $avgTime = round($totalDuration / $completedQueues->count());
        }

        $stats = [
            'total' => $queues->count(),
            'completed' => $completedQueues->count(),
            'waiting' => $queues->whereIn('status', ['waiting', 'called'])->count(),
            'avg_time' => $avgTime . ' menit'
        ];

        // --- 2. Grafik Tren Per Jam (DIPERBAIKI) ---
        $chartHourly = ['labels' => [], 'data' => []];
        
        // PERBAIKAN: Konversi ke WIB (Asia/Jakarta) sebelum grouping
        $hourlyGroups = $queues->groupBy(function($date) {
            return $date->created_at->timezone('Asia/Jakarta')->format('H:00');
        });

        // PERBAIKAN: Loop diperluas dari jam 07:00 sampai 17:00 (5 sore)
        for($i=7; $i<=17; $i++) {
            $hour = str_pad($i, 2, '0', STR_PAD_LEFT) . ':00';
            $chartHourly['labels'][] = $hour;
            $chartHourly['data'][] = isset($hourlyGroups[$hour]) ? $hourlyGroups[$hour]->count() : 0;
        }

        // --- 3. Grafik Distribusi Status ---
        $statusCounts = $queues->groupBy('status')->map->count();
        $chartStatus = [
            'labels' => ['Menunggu', 'Dipanggil', 'Selesai', 'Dilewati'],
            'data' => [
                $statusCounts['waiting'] ?? 0,
                $statusCounts['called'] ?? 0,
                $statusCounts['completed'] ?? 0,
                $statusCounts['skipped'] ?? 0, // Pastikan status 'skipped' terhitung
            ]
        ];

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'chart_hourly' => $chartHourly,
            'chart_status' => $chartStatus,
            'queues' => $queues,
            'current_date' => $today->translatedFormat('d F Y')
        ]);
    }

    public function export()
    {
        return Excel::download(new QueueExport(Carbon::today()), 'Laporan_Antrian_'.date('Y-m-d').'.xlsx');
    }
}