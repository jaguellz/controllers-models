<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stage extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'position',
        'description',
        'period',
        'resume_id',
    ];

    public function resume()
    {
        return $this->belongsTo(Resume::class);
    }
}
