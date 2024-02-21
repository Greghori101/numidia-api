<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Checkout extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'status',
        'pay_date',
        'date',
        'price',
        'paid',
        'nb_session',
        'discount',
        'month'
    ];

    protected $keyType = 'string';
    public $incrementing = false;
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
    public function receipt()
    {
        return $this->belongsTo(Receipt::class);
    }
}
