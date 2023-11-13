<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'city',
        'wilaya',
        'street',
    ];

    protected $keyType = 'string';
    protected $casts = [
        'id' => 'string',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

}
  