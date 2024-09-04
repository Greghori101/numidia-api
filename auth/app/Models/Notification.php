<?php

namespace App\Models;

use App\Events\NewNotifications;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Ratchet\Client;

class Notification extends Model
{
    use HasFactory, SoftDeletes, HasUuids;
    protected $fillable = [
        'type',
        'title',
        'content',
        'displayed',
        'user_id',
        'department',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function create(array $attributes = [])
    {
        $model = static::query()->create($attributes);

        $user_id = $model->user_id;
        
        broadcast(new NewNotifications($user_id));
        
        return $model;
    }
}
