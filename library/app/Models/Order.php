<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'status',
        'total'
    ];

    protected $keyType = 'string';
    protected $casts = [
        'id' => 'string',
    ];

    public function client(){
        return $this->belongsTo(Client::class);
    }

    public function user(){
        return $this->belongsTo(Client::class);
    }

    public function products(){
        return $this->belongsToMany(Product::class , 'order_product' , 'order_id','product_id' )->withPivot('price','qte');
    }

    public function receipt(){
        return $this->hasOne(Receipt::class);
    }
}
