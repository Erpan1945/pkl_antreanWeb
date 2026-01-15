<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['name', 'code'];
    
    // Satu layanan punya banyak antrian
    public function queues() {
        return $this->hasMany(Queue::class);
    }
}