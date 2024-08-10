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
        'user_id'
    ];

    protected $keyType = 'string';
    public $incrementing = false;


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function employee()
    {
        return $this->belongsTo(User::class,'employee_id');
    }

    public function services()
    {
        return $this->hasMany(ReceiptService::class,'receipt_id');
    }
}
