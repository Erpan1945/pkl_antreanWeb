<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Revolution\Google\Sheets\Facades\Sheets;
use App\Jobs\SyncQueueToGoogleSheets;

class TicketController extends Controller
{
    public function index()
    {
        return Inertia::render('Kiosk/Index', [
            // Kirim data layanan (opsional, untuk debug)
            'services' => Service::all() 
        ]);
    }

    public function store(Request $request)
    {
        // 1. VALIDASI (Hanya Data Diri, TANPA service_id)
        $request->validate([
            'guest_name'      => 'required|string|max:100',
            'identity_number' => 'required|string|max:20',
            'phone_number'    => 'required|string|max:15',
            'purpose'         => 'nullable|string|max:255',
        ]);

        try {
            // --- LOGIKA OTOMATIS PILIH LAYANAN ---
            // Ambil layanan pertama yang ada di database
            $service = Service::first();

            // CEK JIKA DATABASE KOSONG (PENTING!)
            if (!$service) {
                throw new \Exception("ERROR: Data Layanan KOSONG. Jalankan 'php artisan db:seed' dulu!");
            }
            
            $ticket = DB::transaction(function () use ($request, $service) {
                
                // Cek antrian terakhir hari ini
                $today = Carbon::today();
                $lastQueue = Queue::where('service_id', $service->id)
                    ->whereDate('created_at', $today)
                    ->orderBy('number', 'desc')
                    ->lockForUpdate()
                    ->first();

                // Buat nomor antrian baru
                $newNumber = $lastQueue ? $lastQueue->number + 1 : 1;
                $ticketCode = $service->code . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

                // Simpan ke Database
                return Queue::create([
                    'service_id'      => $service->id, // ID Otomatis
                    'counter_id'      => null,
                    'guest_name'      => $request->guest_name,
                    'identity_number' => $request->identity_number,
                    'phone_number'    => $request->phone_number,
                    'purpose'         => $request->purpose,
                    'number'          => $newNumber,
                    'ticket_code'     => $ticketCode,
                    'status'          => 'waiting'
                ]);
            });

            $googleSheetsError = null;
            // OPTIMIZED: Kirim ke Google Sheets secara ASYNC (jangan block response)
            try {
                SyncQueueToGoogleSheets::dispatch($ticket);
            } catch (\Exception $e) {
                Log::error('Google Sheets Queue Error: ' . $e->getMessage());
                // Jangan return error ke user, tetap lanjutkan karena tiket sudah dibuat
            }

            return response()->json([
                'message' => 'Tiket berhasil dibuat',
                'ticket' => $ticket,
                'service_name' => $service->name,
                'date' => $ticket->created_at->timezone('Asia/Jakarta')->format('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            // Tangkap Error Spesifik (seperti Database Kosong)
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}