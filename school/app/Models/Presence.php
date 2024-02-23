<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Presence extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'starts_at',
        'ends_at',
        'group_id',
        'status',
        'session_id',
        'month',
    ];

    protected $keyType = 'string';
    public $incrementing = false;


    public function students()
    {
        return $this->belongsToMany(
            Student::class,
            'presence_student',
            'presence_id',
            'student_id'
        )->withPivot('status');
    }
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function session()
    {
        return $this->belongsTo(Session::class);
    }
}
