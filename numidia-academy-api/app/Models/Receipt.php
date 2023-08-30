<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Receipt extends Model
{
    use HasFactory, HasUuids,SoftDeletes;

    protected $fillable = [
        'total',
        'type',
    ];

    protected $keyType = 'string';
    public $incrementing = false;


    public function user(){
        return $this->belongsTo(User::class);
    }

    public function checkouts(){
        return $this->hasMany(Checkout::class);
    }

}
