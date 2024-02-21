<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'content',
    ];

    protected $keyType = 'string';
    public $incrementing = false;


    public function from()
    {
        return $this->belongsTo(User::class,'from');
    }

    public function to()
    {
        return $this->belongsTo(User::class,'to');
    }
}
