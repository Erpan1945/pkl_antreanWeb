<?php

namespace App\Exports;

use App\Models\Queue;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class QueueExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $date;

    public function __construct($date)
    {
        $this->date = $date;
    }

    // Ambil data antrian sesuai tanggal yang diminta
    public function collection()
    {
        return Queue::with('service', 'counter')
            ->whereDate('created_at', $this->date)
            ->get();
    }

    // Judul Kolom (Header)
    public function headings(): array
    {
        return [
            'Waktu Kedatangan',
            'Kode Tiket',
            'Nama Tamu',
            'NRP/NIP',
            'No HP',
            'Keperluan',
            'Loket',
            'Status Akhir',
            'Waktu Selesai'
        ];
    }

    // Mapping Data per Baris
    public function map($queue): array
    {
        return [
            $queue->created_at->format('H:i'),
            $queue->ticket_code,
            $queue->guest_name,
            $queue->identity_number,
            $queue->phone_number,
            $queue->purpose,
            $queue->counter ? $queue->counter->name : '-',
            strtoupper($queue->status),
            $queue->updated_at->format('H:i'),
        ];
    }

    // Styling Header (Bold & Warna)
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}