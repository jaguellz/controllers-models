<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    protected $fillable = [
        'answer',
        'expires_at',
        'resume_id',
        'vacancy_id',
        'accepted',
        'contact_type',
        'contact',
        'date'
    ];

    public function resume()
    {
        return $this->belongsTo(Resume::class);
    }

    public function vacancy()
    {
        return $this->belongsTo(Vacancy::class);
    }
}
