<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use HasFactory, HasUuids;


    protected $fillable = [
        'modules',
        'levels',
        'percentage',
    ];

    protected $keyType = 'string';
    public $incrementing = false;



    function user()
    {
        return $this->belongsTo(User::class);
    }
    function sessions()
    {
        return $this->hasManyThrough(Session::class, Group::class);
    }
    function groups()
    {
        return $this->hasMany(Group::class);
    }
    function exams()
    {
        return $this->hasMany(Exam::class);
    }
}
