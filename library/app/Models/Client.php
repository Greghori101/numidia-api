<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
    ];

    protected $keyType = 'string';
    protected $casts = [
        'id' => 'string',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function orders(){
        return $this->hasMany(Order::class);
    }

    public function receipts(){
        return $this->hasManyThrough(Receipt::class , Order::class);
    }

    
}
