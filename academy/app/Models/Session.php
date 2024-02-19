<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'classroom',
        'status',
        'starts_at',
        'ends_at',
        'repeating',
        'type',
    ];

    protected $keyType = 'string';
    public $incrementing = false;



    function group()
    {
        return $this->belongsTo(Group::class);
    }
    function exceptions()
    {
        return $this->hasMany(ExceptionSession::class);
    }

    function presences()
    {
        return $this->hasMany(Presence::class);
    }
}
