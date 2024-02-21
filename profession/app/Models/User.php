<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class User extends Model
{
    use  HasFactory, Notifiable, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'email',
        'name',
        'role',
        'phone_number',
        'gender',
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

    function checkouts()
    {
        return $this->hasMany(Checkout::class);
    }
    function receipts()
    {
        return $this->hasMany(Receipt::class);
    }
    function student()
    {
        return $this->hasOne(Student::class);
    }
    function teacher()
    {
        return $this->hasOne(Teacher::class);
    }
    function supervisor()
    {
        return $this->hasOne(Supervisor::class);
    }
    function expenses()
    {
        return $this->hasMany(Expense::class);
    }
    function inscription_fees()
    {
        return $this->hasMany(FeeInscription::class, "user_id");
    }
}
