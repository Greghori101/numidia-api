<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Level extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'education',
        'speciality',
        'year',

    ];

    protected $keyType = 'string';
    public $incrementing = false;

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    function students()
    {
        return $this->hasMany(Student::class);
    }
    function groups()
    {
        return $this->hasMany(Group::class);
    }
}
