<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [];

    protected $keyType = 'string';
    public $incrementing = false;

    function user()
    {
        return $this->belongsTo(User::class);
    }

    function supervisor()
    {
        return $this->belongsTo(Supervisor::class);
    }
    function groups()
    {
        return $this->belongsToMany(
            Group::class,
            'group_student',
            'student_id',
            'group_id'
        )
            ->withPivot([
                'first_session',
                'first_month',
                'status',
                'last_session',
                'last_month',
                'debt',
                'nb_absence',
                'nb_paid_session',
                'discount'
            ]);
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function checkouts()
    {
        return $this->hasMany(Checkout::class);
    }
    public function answers()
    {
        return $this->hasMany(Answer::class);
    }
    function fee_inscription()
    {
        return $this->hasOne(FeeInscription::class);
    }
    public function presences()
    {
        return $this->belongsToMany(
            Presence::class,
            'presence_student',
            'student_id',
            'presence_id',

        )->withPivot(['status', 'type', 'notes']);
    }

    public function exams()
    {
        return $this->belongsToMany(Exam::class, 'answers', 'student_id', 'exam_id')
            ->distinct('exams.id')
            ->with(['answers' => function ($query) {
                $query->where('student_id', $this->id)->select('*')->with(['question.choices', 'question.audio']);
            }]);
    }

    public function mark_sheets(){
        return $this->hasMany(MarkSheet::class);
    }
}
