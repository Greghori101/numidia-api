<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Choice extends Model
{
    use HasFactory, HasUuids;
    
    protected $fillable = [
        'content',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    public function question(){
        return $this->belongsTo(Question::class);
    }
}
