<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departement extends Model
{
    use HasFactory,HasUuids;
    
    protected $fillable = [
        'name',

    ];

    protected $keyType = 'string';
    public $incrementing = false;


    
    function levels(){
        return $this->hasMany(Level::class);
    }
}
