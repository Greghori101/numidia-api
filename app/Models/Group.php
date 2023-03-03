<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'members',
        'capacity',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    function students()
    {
        return $this->belongsToMany(Student::class, 'group_student', 'group_id', 'student_id');
    }

    function departement()
    {
        return $this->belongsTo(Departement::class);
    }

    function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    function sessions()
    {
        return $this->hasMany(Session::class);
    }
}
