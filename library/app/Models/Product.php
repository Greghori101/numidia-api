<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'name',
        'price',
        'qte',
        'description',
        'purchase_date'
    ];

    protected $keyType = 'string';
    protected $casts = [
        'id' => 'string',
    ];

    public function orders(){
        return $this->belongsToMany(Order::class , 'order_product' , 'product_id', 'order_id')->withPivot('price','qte');
    }

    public function pictures(){
        return $this->morphMany(File::class , 'fileable');
    }
}
