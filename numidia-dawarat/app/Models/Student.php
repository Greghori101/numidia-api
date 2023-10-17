<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use  HasFactory,  SoftDeletes, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
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

    public function dawarat()
    {
        return $this->belongsToMany(
            Dawarat::class,
            'presence',
            'student_id',
            'dawarat_id',
        )->withPivot('status');
    }
    public function presences()
    {
        return $this->hasMany(Presence::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
