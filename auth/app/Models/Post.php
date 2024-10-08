<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'title',
        'content',
        'department',
    ];

    protected $keyType = 'string';
    public $incrementing = false;



    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function photos()
    {
        return $this->morphMany(File::class, "fileable");
    }
}
