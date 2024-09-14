<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExceptionSession extends Model
{
    use HasFactory,HasUuids;
    
    protected $fillable = [
        'date',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    function session(){
        return $this->belongsTo(Group::class);
    }
    
}
