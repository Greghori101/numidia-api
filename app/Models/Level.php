<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    use HasFactory,HasUuids;
    
    protected $fillable = [
        'education',
        'specialty',
        'year',

    ];

    protected $keyType = 'string';
    public $incrementing = false;

    public function departement(){
        return $this->belongsTo(Departement::class);
    }
    
    function plans(){
        return $this->hasMany(Plan::class);
    }
    function modules(){
        return $this->hasMany(Module::class);
    }

    function groups(){
        return $this->hasMany(Group::class);
    }
}
