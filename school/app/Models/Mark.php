<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mark extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'module',
        'coefficient',
        'mark',
        'notes',
    ];

    protected $keyType = 'string';
    public $incrementing = false;
    

   
    public function sheet()
    {
        return $this->belongsTo(MarkSheet::class,'mark_sheet_id');
    }
}
