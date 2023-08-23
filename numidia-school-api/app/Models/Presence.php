<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Presence extends Model
{
    use HasFactory, HasUuids,SoftDeletes;

    protected $fillable = [
        'status',
        'starts_at',
        'ends_at',
    ];

    protected $keyType = 'string';
    public $incrementing = false;


    public function student(){
        return $this->belongsTo(Student::class);
    }
    public function group(){
        return $this->belongsTo(Group::class);
    }

}
