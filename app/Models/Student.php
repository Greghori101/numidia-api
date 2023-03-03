<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'active',
        'activated_at',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    function user(){
        return $this->belongsTo(User::class);
    }
    function plan(){
        return $this->belongsTo(Plan::class);
    }
    function supervisor(){
        return $this->belongsTo(Supervisor::class);
    }
    function groups(){
        return $this->belongsToMany(Group::class,'group_student','student_id','group_id');      
    }
}
