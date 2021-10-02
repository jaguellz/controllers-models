<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sphere_id'
    ];

    public function vacancies()
    {
        return $this->hasMany(Vacancy::class);
    }

    public function resumes()
    {
        return $this->hasMany(Resume::class);
    }
}
