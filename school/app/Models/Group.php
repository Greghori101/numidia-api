<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Group extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'annex',
        'type',
        'module',
        'capacity',
        'price_per_month',
        'percentage',
        'nb_session',
        'main_session',
        'current_month',
        'current_nb_session',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    function students()
    {
        return $this->belongsToMany(
            Student::class,
            'group_student',
            'group_id',
            'student_id',
        )->withPivot([
            'first_session',
            'first_month',
            'last_session',
            'last_month',
            'nb_paid_session',
            'nb_session',
            'status',
            'debt',
            'discount',
        ]);
    }

    function level()
    {
        return $this->belongsTo(Level::class);
    }

    function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    function sessions()
    {
        return $this->hasMany(Session::class);
    }

    public function checkouts()
    {
        return $this->hasMany(Checkout::class);
    }
    function presences()
    {
        return $this->hasMany(Presence::class);
    }

    
    public function amphi()
    {
        return $this->belongsTo(Amphi::class);
    }
    public function photos()
    {
        return $this->morphMany(File::class, "fileable");
    }
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
