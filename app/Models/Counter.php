<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Counter extends Model
{
    protected $fillable = ['name', 'is_active'];
    
    // Satu loket melayani banyak antrian
    public function queues() {
        return $this->hasMany(Queue::class);
    }
}