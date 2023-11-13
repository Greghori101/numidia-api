<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use  HasFactory,   HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'row',
        'seat',
        'section',
        'title',
        'date',
        'price',
        'payed',
        'location',
        'discount',
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

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
    public function presence()
    {
        return $this->belongsTo(Presence::class);
    }
    public function dawarat(){
        return $this->belongsTo(Dawarat::class);
    }
}
