<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    use HasFactory,HasUuids;
    
    protected $fillable = [
        'classroom',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected $keyType = 'string';
    public $incrementing = false;


    
    function group(){
        return $this->belongsTo(Group::class);
    }

    function teacher(){
        return $this->belongsTo(Teacher::class);
    }
    
}
