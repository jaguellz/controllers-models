<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Playlist extends Model
{
    use HasFactory;

    /**
     * @var array
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'picture',
        'user_id'
    ];

    /**
     * Return audios from playlist
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function audios(){
        return $this->belongsToMany('App\Models\Audio', 'playlist_audio');
    }
}
