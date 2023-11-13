<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'date',
    ];

    protected $keyType = 'string';
    public $incrementing = false;


    public function user(){
        return $this->belongsTo(User::class);
    }

}
