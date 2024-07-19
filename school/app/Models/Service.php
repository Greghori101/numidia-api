<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'discount',
        'text',
        'qte',
        'price',
        'notes',
    ];

    protected $keyType = 'string';
    public $incrementing = false;
    
    public function receipt()
    {
        return $this->belongsTo(Receipt::class);
    }
}
