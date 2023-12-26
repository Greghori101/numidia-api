<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'browser',
        'details',
        'ip_address',
        'location',
        'device',
        'platform',
        'access_token_id',
    ];

    protected $keyType = 'string';
    public $incrementing = false;


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
