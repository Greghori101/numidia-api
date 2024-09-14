<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'answer',
        'score',
    ];

    protected $keyType = 'string';
    public $incrementing = false;
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }
    public function student()
    {
        return $this->belongsTo(Student::class);
    }
    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
