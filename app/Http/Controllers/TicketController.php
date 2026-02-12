<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use App\Jobs\SyncQueueToGoogleSheets;
use App\Http\Controllers\Controller;

class TicketController extends Controller
{
    public function index()
    {
        return Inertia::render('Kiosk/Index', [
            'services' => Service::all() 
        ]);
    }

    public function store(Request $request)
    {
        // 1. VALIDASI DIPERKETAT
        // Kita gunakan 'in' agar input hanya menerima nilai yang ada di dropdown
        $request->validate([
            'guest_name'      => 'required|string|max:100',
            'identity_number' => 'required|string|max:20',
            'phone_number'    => 'required|string|max:15',
            'purpose'         => [
                'required',
                'string',
                // Pastikan hanya menerima value ini (sesuai dropdown Vue)
                'in:pdth,pengurusan pensiun,bppp,bpi,bps,bpa,lainnya'
            ],
        ]);

        try {
            // Ambil layanan pertama (default)
            $service = Service::first();

            if (!$service) {
                throw new \Exception("ERROR: Data Layanan KOSONG. Jalankan 'php artisan db:seed' dulu!");
            }
            
            $ticket = DB::transaction(function () use ($request, $service) {
                
                // Cek antrian terakhir hari ini
                $today = Carbon::today();
                $lastQueue = Queue::where('service_id', $service->id)
                    ->whereDate('created_at', $today)
                    ->lockForUpdate() // Mencegah nomor ganda
                    ->orderBy('number', 'desc')
                    ->first();

                // Buat nomor antrian baru
                $newNumber = $lastQueue ? $lastQueue->number + 1 : 1;
                $ticketCode = $service->code . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

                // Simpan ke Database
                return Queue::create([
                    'service_id'      => $service->id,
                    'counter_id'      => null,
                    'guest_name'      => $request->guest_name,
                    'identity_number' => $request->identity_number,
                    'phone_number'    => $request->phone_number,
                    'purpose'         => $request->purpose, // Data dari dropdown
                    'number'          => $newNumber,
                    'ticket_code'     => $ticketCode,
                    'status'          => 'waiting'
                ]);
            });

            // Kirim ke Google Sheets (Async)
            try {
                SyncQueueToGoogleSheets::dispatch($ticket);
            } catch (\Exception $e) {
                Log::error('Google Sheets Queue Error: ' . $e->getMessage());
            }

            // RESPON JSON KE VUE
            return response()->json([
                'message' => 'Tiket berhasil dibuat',
                'ticket' => [
                    'ticket_code'     => $ticket->ticket_code,
                    'guest_name'      => $ticket->guest_name,
                    'identity_number' => $ticket->identity_number,
                    'purpose'         => $ticket->purpose, // Pastikan ini terkirim
                ],
                'service_name' => $service->name,
                'date' => $ticket->created_at->timezone('Asia/Jakarta')->format('d M Y H:i')
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}