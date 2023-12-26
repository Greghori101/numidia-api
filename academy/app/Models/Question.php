<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'nb_choice',
        'content',
        'answer',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }
    public function choices()
    {
        return $this->hasMany(Choice::class);
    }
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }
    public function isCorrect($studentAnswer)
    {
        return $this->answer == $studentAnswer;
    }
}
