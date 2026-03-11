<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
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
        // 1. VALIDASI DILONGGARKAN
        // Kita tidak lagi mewajibkan input dari user
        $request->validate([
            'service_id' => 'nullable|exists:services,id',
        ]);

        try {
            // Ambil layanan dari request, jika tidak ada ambil yang pertama
            $service = $request->service_id 
                ? Service::find($request->service_id) 
                : Service::first();

            if (!$service) {
                throw new \Exception("Layanan tidak ditemukan.");
            }
            
            $ticket = DB::transaction(function () use ($service) {
                
                $today = Carbon::today();
                $lastQueue = Queue::where('service_id', $service->id)
                    ->whereDate('created_at', $today)
                    ->lockForUpdate() 
                    ->orderBy('number', 'desc')
                    ->first();

                $newNumber = $lastQueue ? $lastQueue->number + 1 : 1;
                $ticketCode = $service->code . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

                // 2. SIMPAN DENGAN DATA DEFAULT
                return Queue::create([
                    'service_id'      => $service->id,
                    'counter_id'      => null,
                    'guest_name'      => 'Tamu Umum', // Default
                    'identity_number' => '-',          // Default
                    'phone_number'    => '-',          // Default
                    'purpose'         => 'Layanan Mandiri', // Default
                    'number'          => $newNumber,
                    'ticket_code'     => $ticketCode,
                    'status'          => 'waiting'
                ]);
            });

            // 3. KIRIM KE GOOGLE SHEETS (Tetap jalan dengan data default)
            try {
                $webhookUrl = config('services.google.webhook_url');
                if ($webhookUrl) {
                    Http::post($webhookUrl, [
                        'kode_tiket'   => $ticket->ticket_code,
                        'nama_tamu'    => $ticket->guest_name,
                        'nrp_nip'      => $ticket->identity_number,
                        'no_hp'        => $ticket->phone_number,
                        'keperluan'    => $ticket->purpose,
                        'waktu_dibuat' => $ticket->created_at->timezone('Asia/Jakarta')->format('Y-m-d H:i:s')
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Webhook Google Sheets Error: ' . $e->getMessage());
            }

            // 4. RESPON KE VUE
            return response()->json([
                'message' => 'Tiket berhasil dibuat',
                'ticket' => [
                    'ticket_code'     => $ticket->ticket_code,
                    'guest_name'      => $ticket->guest_name,
                    'identity_number' => $ticket->identity_number,
                    'purpose'         => $ticket->purpose,
                ],
                'service_name' => $service->name,
                'date' => $ticket->created_at->timezone('Asia/Jakarta')->format('d M Y H:i')
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}