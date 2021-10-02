<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vacancy extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'city',
        'grade',
        'stage',
        'schedule', //график работы
        'category',
        'body',
        'views',
        'company_id',
        'minsalary',
        'maxsalary',
        'type', //тип занятости
        'active',
        'abilities',
        'sphere_id'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category');
    }

    public function feedbacks()
    {
        return $this->belongsToMany(Resume::class, 'feedback');
    }

    public function sphere()
    {
        return $this->belongsTo(Sphere::class);
    }

    public function delete()
    {
        $this->feedbacks()->detach();
        return parent::delete();
    }
}
