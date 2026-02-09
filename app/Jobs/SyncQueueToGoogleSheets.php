<?php

namespace App\Jobs;

use App\Models\Queue;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Revolution\Google\Sheets\Facades\Sheets;

class SyncQueueToGoogleSheets implements ShouldQueue
{
    use Queueable;

    protected $ticket;

    /**
     * Create a new job instance.
     */
    public function __construct(Queue $ticket)
    {
        $this->ticket = $ticket;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Nama sheet berdasarkan Bulan & Tahun (Format: Januari 2026)
            $sheetName = $this->ticket->created_at->locale('id')->isoFormat('MMMM Y');
            
            $sheets = Sheets::spreadsheet(config('google.spreadsheet_id'));
            
            // Cek apakah sheet sudah ada
            $sheetList = $sheets->sheetList();
            $sheetExists = in_array($sheetName, $sheetList);

            // Jika belum ada, buat sheet baru dan isi header
            if (!$sheetExists) {
                $sheets->addSheet($sheetName);
                
                // Tunggu sebentar agar sheet siap
                sleep(2);
                
                $sheets->sheet($sheetName)->append([
                    ['Kode Tiket', 'Nama Tamu', 'NRP/NIP', 'No. HP', 'Keperluan', 'Waktu Dibuat']
                ]);
            }

            // Append data ke sheet yang sesuai
            $sheets->sheet($sheetName)->append([
                [
                    $this->ticket->ticket_code,
                    $this->ticket->guest_name,
                    $this->ticket->identity_number,
                    $this->ticket->phone_number,
                    $this->ticket->purpose,
                    $this->ticket->created_at->timezone('Asia/Jakarta')->format('Y-m-d H:i:s')
                ]
            ]);

            Log::info('Queue ' . $this->ticket->ticket_code . ' synced to Google Sheets successfully');

        } catch (\Exception $e) {
            Log::error('Google Sheets Sync Error for ticket ' . $this->ticket->ticket_code . ': ' . $e->getMessage());
            // Job akan di-retry otomatis jika gagal
        }
    }
}
