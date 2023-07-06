<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use HasFactory, HasUuids,SoftDeletes;

    protected $fillable = [
        'title',
        'details',
        'status',
        'mode',
        'ip_address',
        'location',
        'coordinates',
        'device',
    ];

    protected $keyType = 'string';
    public $incrementing = false;


    public function user(){
        return $this->belongsTo(User::class);
    }

   
}
