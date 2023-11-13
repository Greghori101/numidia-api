<?php

namespace App\Models;

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
        $message =  'new notification';

        $data = [
            'user_id' => $user_id,
            'message' => $message,
        ];
        $data = json_encode($data);

        $web_socket_url = 'ws://localhost:8090?user_id=webserver';

        Client\connect($web_socket_url)->then(function ($conn) use ($data) {
            $conn->send($data);
            $conn->close();
        }, function ($e) {
            echo "Could not connect: {$e->getMessage()}\n";
        });
        return $model;
    }
}
