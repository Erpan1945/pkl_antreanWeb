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
            // Kirim ke Google Sheets
            try {
                Sheets::spreadsheet(config('google.spreadsheet_id'))
                    ->sheet('Sheet1')
                    ->append([
                        [
                            $ticket->ticket_code,
                            $ticket->guest_name,
                            $ticket->identity_number,
                            $ticket->phone_number,
                            $ticket->purpose,
                            $ticket->created_at->format('Y-m-d H:i:s')
                        ]
                    ]);
            } catch (\Exception $e) {
                Log::error('Google Sheets Error: ' . $e->getMessage());
                $googleSheetsError = $e->getMessage();
            }

            return response()->json([
                'message' => 'Tiket berhasil dibuat',
                'ticket' => $ticket,
                'service_name' => $service->name,
                'date' => $ticket->created_at->format('d/m/Y H:i'),
                'google_sheets_error' => $googleSheetsError
            ]);

        } catch (\Exception $e) {
            // Tangkap Error Spesifik (seperti Database Kosong)
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}