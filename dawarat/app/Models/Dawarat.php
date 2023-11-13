<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dawarat extends Model
{
    use  HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'title',
        'date',
        'price',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [

        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */

    public function students()
    {
        return $this->belongsToMany(
            Student::class,
            'presence',
            'dawarat_id',
            'student_id',
        )->withPivot('status');
    }
    public function presences()
    {
        return $this->hasMany(Presence::class);
    }
    public function amphi()
    {
        return $this->belongsTo(Amphi::class);
    }
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
    public function photos()
    {
        return $this->morphMany(File::class, "fileable");
    }
}
