<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'status',
        'amount',
    ];

    protected $keyType = 'string';
    public $incrementing = false;


    public function from(){
        return $this->belongsTo(Wallet::class,'from');
    }

    public function to()
    {
        return $this->belongsTo(Wallet::class,'to');
    }
}
