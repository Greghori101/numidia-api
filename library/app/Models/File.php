<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'url',
    ];

    protected $keyType = 'string';
    protected $casts = [
        'id' => 'string',
    ];

    public function fileable(){
        return $this->morphTo(__FUNCTION__, 'fileable_type', 'fileable_id');
    }
}
