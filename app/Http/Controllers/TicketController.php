<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TicketController extends Controller
{
    // 1. Menampilkan Halaman Kiosk (Layar Peserta)
    public function index()
    {
        return Inertia::render('Kiosk/Index', [
            'services' => Service::all() // Kirim data layanan ke Vue (untuk jadi tombol)
        ]);
    }

    // 2. Proses Mencetak Tiket (Saat tombol diklik)
    public function store(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'guest_name' => 'required|string|max:100',
            'identity_number' => 'required|numeric', // Pastikan Angka (supaya NIK/HP valid)
        ]);

        // Gunakan Transaction agar nomor tidak balapan/ganda jika diklik bersamaan
        $ticket = DB::transaction(function () use ($request) {
            $service = Service::find($request->service_id);
            
            // Cek antrian terakhir HARI INI untuk layanan TERSEBUT
            $today = Carbon::today();
            $lastQueue = Queue::where('service_id', $service->id)
                ->whereDate('created_at', $today)
                ->orderBy('number', 'desc')
                ->lockForUpdate() // Kunci database sebentar
                ->first();

            // Tentukan nomor baru (Kalau belum ada mulai dari 1, kalau ada tambah 1)
            $newNumber = $lastQueue ? $lastQueue->number + 1 : 1;

            // Format Kode Tiket: A-001
            // str_pad(1, 3, '0', STR_PAD_LEFT) hasilnya "001"
            $ticketCode = $service->code . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

            // Simpan ke Database
            return Queue::create([
                'service_id' => $service->id,
                'counter_id' => null,
                'guest_name' => $request->guest_name,        // Simpan Nama Tamu
                'identity_number' => $request->identity_number, // Simpan NRP
                'number' => $newNumber,
                'ticket_code' => $ticketCode,
                'status' => 'waiting'
            ]);
        });

        // Kembalikan data tiket ke Frontend (Vue) untuk diprint
        return response()->json([
            'message' => 'Tiket berhasil dibuat',
            'ticket' => $ticket,
            'service_name' => $ticket->service->name,
            'date' => $ticket->created_at->format('d/m/Y H:i')
        ]);
    }
}