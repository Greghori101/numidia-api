<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class Presence extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'group_id',
        'month',
        'session_id',
        'session_number',
        'status',
        'type',
        'starts_at',
        'ends_at',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new OrderBySessionNumberAndMonthScope);
    }

    protected $keyType = 'string';
    public $incrementing = false;

    public function students()
    {
        return $this->belongsToMany(
            Student::class,
            'presence_student',
            'presence_id',
            'student_id'
        )->withPivot(['status', 'type', 'notes']);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function session()
    {
        return $this->belongsTo(Session::class);
    }
}

class OrderBySessionNumberAndMonthScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $builder->orderBy('month', 'asc');
        $builder->orderBy('session_number', 'asc');
    }
}
