<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Checkout extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'month',
        'status',
        'price',
        'discount',
        'paid_price',
        'nb_session',
        'pay_date',
        'teacher_percentage',
        'notes',
    ];

    protected $keyType = 'string';
    public $incrementing = false;
    
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
    
}
