<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use HasFactory, SoftDeletes,HasUuids;
    protected $fillable = [
        'type',
        'title',
        'content',
        'displayed',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    function user(){
        return $this->belongsTo(User::class);
    }
}
