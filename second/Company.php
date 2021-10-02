<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',//aaaaaaaaaaaaa
        'phone',
        'password',
        'api_token',
        'name',
        'email',
        'bin',
        'bik',
        'address',
        'email',
        'inn',
        'subscribe',
        'avatar'
    ];
    protected $hidden = [
        'password',
        'api_token',
        'auth_code',
        'subscribe'
    ];

    public function vacancies()
    {
        return $this->hasMany(Vacancy::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function urls()
    {
        return $this->hasMany(Contact::class);
    }
}
