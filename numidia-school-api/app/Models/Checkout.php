<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Checkout extends Model
{
    use HasFactory, HasUuids,SoftDeletes;

    protected $fillable = [
        'date',
        'pay_date',
        'price',
        'payed',
    ];

    protected $keyType = 'string';
    public $incrementing = false;
    public function user(){
        return $this->belongsTo(User::class);
    }

    public function group(){
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
