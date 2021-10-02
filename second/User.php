<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'phone',
        'password',
        'api_token',
        'auth_code',
        'avatar'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'api_token',
        'auth_code'
    ];

    public function urls()
    {
        return $this->hasMany(Contact::class);
    }

    public function resumes()
    {
        return $this->hasMany(Resume::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function favourites()
    {
        return $this->belongsToMany(Vacancy::class, 'favourites');
    }

}
