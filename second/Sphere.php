<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sphere extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function vacancies()
    {
        return $this->hasMany(Vacancy::class);
    }

    public function resumes()
    {
        return $this->hasMany(Resume::class);
    }
}
