<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasUuids, HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'email',
        'code',
        'name',
        'phone_number',
        'gender',
        'password',
        'google_id',
        'facebook_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'code',
        'google_id',
        'facebook_id',
    ];


    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'id' => 'string',
    ];
    function activities()
    {
        return $this->hasMany(Activity::class)->orderBy('created_at', 'desc');
    }
    public function profile_picture()
    {
        return $this->morphOne(File::class, "fileable");
    }
    function posts()
    {
        return $this->hasMany(Post::class)->orderBy('created_at', 'desc');
    }
    function received_notifications()
    {
        return $this->hasMany(Notification::class, 'user_id')->orderBy('created_at', 'desc');
    }

    function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    function parent()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    function transactions()
    {
        return $this->hasMany(Transaction::class)->orderBy('created_at', 'asc');
    }

    function received_messages()
    {
        return $this->hasMany(Message::class, 'to')->orderBy('created_at', 'desc');
    }
    function sent_messages()
    {
        return $this->hasMany(Message::class, 'from')->orderBy('created_at', 'desc');
    }

    function permissions()
    {
        return $this->hasMany(Permission::class);
    }
    function has(array $permissions)
    {
        if (!is_array($permissions)) {
            $permissions = [$permissions];
        }
    
        foreach ($permissions as $permission) {
            if (!isset($permission['department']) || !isset($permission['name'])) {
                continue; // Skip if department or name is not provided
            }
            
            if (!$this->permissions()->where('department', $permission['department'])
                                     ->where('name', $permission['name'])
                                     ->exists()) {
                return false;
            }
        }
        return true;
    }
}
