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
        'nb_session',
        'price',
        'nb_month',
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
