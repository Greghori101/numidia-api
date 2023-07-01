<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'title',
        'details',
        'status',
        'mode',
    ];

    protected $keyType = 'string';
    public $incrementing = false;


    public function user(){
        return $this->belongsTo(User::class);
    }

   
}
