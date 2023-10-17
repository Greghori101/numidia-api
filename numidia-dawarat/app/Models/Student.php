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
        'email',
        'name',
        'phone_number',
        'role',
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


    function wallet()
    {
        return $this->hasOne(Wallet::class);
    }
    function attendance()
    {
        return $this->hasMany(Attendance::class);
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
