<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Exam extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'title',
        'duration',
        'nb_question',
        'status',
        'date',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
    public function questions()
    {
        return $this->hasMany(Question::class);
    }
    public function answers()
    {
        return $this->hasMany(Answer::class)
            ->with('question')
            ->orderBy("student_id");
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'answers')->distinct('id');
    }
}
