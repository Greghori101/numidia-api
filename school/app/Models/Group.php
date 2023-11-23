<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Group extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['module', 'capacity', 'price_per_month', 'nb_session', 'type', 'rest_session','current_nb_session','unit'];

    protected $keyType = 'string';
    public $incrementing = false;

    function students()
    {
        return $this->belongsToMany(
            Student::class,
            'group_student',
            'group_id',
            'student_id',
            
        );
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
    function presence()
    {
        return $this->hasMany(Presence::class);
    }
}
