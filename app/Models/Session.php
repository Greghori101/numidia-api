<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Session extends Model
{
    use HasFactory,HasUuids,SoftDeletes;
    
    protected $fillable = [
        'classroom',
        'starts_at',
        'ends_at',
        'status',
        'repeating'
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
