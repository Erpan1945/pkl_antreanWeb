<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Queue extends Model
{
        protected $fillable = [
        'service_id',
        'counter_id',
        'guest_name',       // UBAH INI
        'identity_number',  // UBAH INI
        'phone_number',
        'purpose',
        'number',
        'ticket_code',
        'status'
    ];

    // Antrian milik satu layanan
    public function service() {
        return $this->belongsTo(Service::class);
    }
    
    // Antrian dipanggil oleh satu loket (bisa null)
    public function counter() {
        return $this->belongsTo(Counter::class);
    }
}