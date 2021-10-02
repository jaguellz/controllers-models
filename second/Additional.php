<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Additional extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'resume_id'
    ];

    public function resume()
    {
        return $this->belongsTo(Resume::class);
    }
}
