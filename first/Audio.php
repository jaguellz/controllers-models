<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Audio extends Model
{
    use HasFactory;

    /**
     * @var array
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'url',
        'picture',
        'user_id',
        'duration',
    ];
}
