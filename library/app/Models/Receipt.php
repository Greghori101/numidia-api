<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'date',
        'total'
    ];

    protected $keyType = 'string';
    protected $casts = [
        'id' => 'string',
    ];

    public function order(){
        return $this->belongsTo(Order::class);
    }
}
