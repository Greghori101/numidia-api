<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, HasUuids,SoftDeletes;

    protected $fillable = [
        'status',
        'amount',
        'detail',
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
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
