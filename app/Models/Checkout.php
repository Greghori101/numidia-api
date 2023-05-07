<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Checkout extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'price',
        'nb_session',
        'total',
        'nb_month',
        'end_date',
    ];

    protected $keyType = 'string';
    public $incrementing = false;


    public function group(){
        return $this->belongsTo(Group::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
