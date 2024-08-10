<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DawaratPresence extends Model
{
    use  HasFactory,  HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'status',
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

    function ticket()
    {
        return $this->hasOne(Ticket::class);
    }
    function dawarat()
    {
        return $this->belongsTo(Group::class);
    }
    function student()
    {
        return $this->belongsTo(Student::class);
    }
}
