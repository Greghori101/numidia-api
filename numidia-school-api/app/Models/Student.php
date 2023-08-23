<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, HasUuids,SoftDeletes;

    protected $fillable = [
        
    ];

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
        return $this->belongsToMany(Group::class, 'group_student', 'student_id', 'group_id')->withPivot("nb_sessions_rest");
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }
    function wallet()
    {
        return $this->hasOneThrough(Wallet::class,User::class);
    }
    public function checkouts()
    {
        return $this->hasMany(Checkout::class);
    }
    function fee_inscription()
    {
        return $this->hasOne(FeeInscription::class);
    }
    function presence()
    {
        return $this->hasMany(Presence::class);
    }
}
