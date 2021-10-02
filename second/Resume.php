<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resume extends Model
{
    use HasFactory;

    protected $fillable = [
        'stage',
        'grade',
        'city',
        'body',
        'active',
        'user_id',
        'name',
        'abilities',
        'phone',
        'email',
        'category_id',
        'sphere_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function additionals()
    {
        return $this->hasMany(Additional::class);
    }

    public function feedbacks()
    {
        return $this->belongsToMany(Vacancy::class, 'feedback');
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function stages()
    {
        return $this->hasMany(Stage::class);
    }

    public function sphere()
    {
        return $this->belongsTo(Sphere::class);
    }

    public function delete()
    {
        $this->stages()->delete();
        $this->grades()->delete();
        $this->feedbacks()->detach();
        $this->additionals()->delete();
        return parent::delete();
    }
}
