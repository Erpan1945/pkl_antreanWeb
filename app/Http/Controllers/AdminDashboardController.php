<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use App\Exports\QueueExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Http\Controllers\Controller;

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

    // public function export()
    // {
    //     return Excel::download(new QueueExport(Carbon::today()), 'Laporan_Antrian_'.date('Y-m-d').'.xlsx');
    // }

    // public function export()
    // {
    //     // Ambil data hanya untuk hari ini (WIB)
    //     $todayWIB = Carbon::today('Asia/Jakarta');

    //     // Konversi ke UTC untuk query yang akurat jika DB menyimpan timestamp UTC
    //     $startUtc = $todayWIB->copy()->startOfDay()->setTimezone('UTC');
    //     $endUtc = $todayWIB->copy()->endOfDay()->setTimezone('UTC');

    //     $queues = Queue::with(['service', 'counter'])
    //         ->whereBetween('created_at', [$startUtc, $endUtc])
    //         ->orderBy('created_at', 'desc')
    //         ->get();

    //     // Map data ke format kolom yang diinginkan
    //     $rows = $queues->map(function ($q) {
    //         return [
    //             'Kode Tiket' => $q->ticket_code,
    //             'Nama Tamu' => $q->guest_name,
    //             'NRP/NIP' => $q->identity_number,
    //             'No. HP' => $q->phone_number,
    //             'Keperluan' => $q->purpose,
    //             'Waktu Dibuat' => $q->created_at ? $q->created_at->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') : '',
    //         ];
    //     });

    //     // Download file dengan nama berisi tanggal WIB
    //     $filename = 'laporan-antrian_'.Carbon::now('Asia/Jakarta')->format('Y-m-d').'.xlsx';
    //     return (new FastExcel($rows))->download($filename);
    // }

    public function export(Request $request)
    {
        // 1. Ambil Parameter Filter dari Frontend
        $type = $request->query('type', 'daily'); // daily, monthly, yearly
        $date = $request->query('date', date('Y-m-d'));
        $month = $request->query('month', date('m'));
        $year = $request->query('year', date('Y'));

        // 2. Siapkan Query Dasar
        $query = Queue::with(['service', 'counter'])->orderBy('created_at', 'asc');
        
        $filename = 'Laporan_Antrian';

        // 3. Filter Berdasarkan Tipe
        switch ($type) {
            case 'daily':
                $query->whereDate('created_at', $date);
                $filename .= '_Harian_' . $date;
                break;

            case 'monthly':
                $query->whereMonth('created_at', $month)
                    ->whereYear('created_at', $year);
                // Nama bulan biar cantik (opsional)
                $monthName = Carbon::createFromDate($year, $month, 1)->translatedFormat('F');
                $filename .= '_Bulanan_' . $monthName . '-' . $year;
                break;

            case 'yearly':
                $query->whereYear('created_at', $year);
                $filename .= '_Tahunan_' . $year;
                break;
        }

        // Ambil Data dari Database
        $queues = $query->get(); 

        // 4. Download Menggunakan FastExcel
        return (new FastExcel($queues))->download($filename . '.xlsx', function ($queue) {
            return [
                'No Tiket'      => $queue->ticket_code,
                'Nama Tamu'     => $queue->guest_name,
                'NRP/Identitas' => "'" . $queue->identity_number, // Kasih kutip agar excel baca sebagai teks (cegah scientific notation)
                'Keperluan'     => $queue->purpose,
                'Layanan'       => $queue->service ? $queue->service->name : '-',
                'Loket'         => $queue->counter ? $queue->counter->name : '-',
                'Status'        => ucfirst($queue->status),
                'Waktu Datang'  => $queue->created_at->format('d-m-Y H:i:s'),
                'Waktu Selesai' => $queue->updated_at->format('d-m-Y H:i:s'),
            ];
        });
    }
}