<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarkSheet extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'year',
        'season',
        'mark',
        'notes',
        'level_id',
        'student_id',
    ];

    protected $keyType = 'string';
    public $incrementing = false;


    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function marks()
    {
        return $this->hasMany(Mark::class);
    }
}
