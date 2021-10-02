<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'university_name',
        'grade',
        'period',
        'resume_id',
    ];

    public function resume()
    {
        return $this->belongsTo(Resume::class);
    }
}
