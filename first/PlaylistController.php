<?php

namespace App\Http\Controllers;

use App\Models\Audio;
use App\Models\Playlist;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpParser\Error;

class PlaylistController extends Controller
{
    public function create(Request $request)
    {
        $this->validator($request->all())->validate();
        $pic = $request->file('picture')->storePublicly('public/pictures/'.Auth::id());
        $pic = asset(Storage::url($pic));
        return Playlist::create([
            'name' => $request->name,
            'picture' => $pic,
            'description' => empty($request->description) ? null : $request->description,
            'user_id' => Auth::id(),
        ]);
    }

    public function addAudio(Request $request)
    {
        $request->validate([
            'audio' => ['required'],
            'id' => ['required', 'exists:playlists,id'],
        ]);
        $audios = explode(', ', $request->audio);
        $response = [];
        if (!$this->access($request->id))
        {
            abort(403, "User is not creator of this playlist");
        }
        foreach ($audios as $audio){
            if (empty(Audio::find($audio)))
            {
                $response[$audio] = ['code' => 404, 'message' => 'Audio not found'];
            }
            elseif (Audio::find($audio)->deleted == 1)
            {
                $response[$audio] = ['code' => 409, 'message' => 'This audio is deleted'];
            }
            elseif (DB::table('playlist_audio')->where('audio_id', $audio)->where('playlist_id', $request->id)->exists())
            {
                $response[$audio] = ['code' => 409, 'message' => 'This audio already in this playlist'];
            }
            else
            {
                $response[$audio] = DB::table('playlist_audio')->insert([
                'audio_id' => $audio,
                'playlist_id' => $request->id,
                ]);
            }
        }
        return $response;
    }

    public function allAudio($id)
    {
        $playlist = Playlist::find($id);
        if (empty($playlist))
        {
            abort(404, "Not found");
        }
        $audios = $playlist->audios()->get();
        $duration = 0;
        foreach ($audios as $audio){
            $audio->id=$audio->pivot->audio_id;
            $duration += $audio->duration;
        }
        return ['audios' => $audios, 'duration' => $duration];
    }

    public function access($id)
    {
        $playlist = Playlist::find($id);
        return $playlist->user_id == Auth::id();
    }

    public function deleteAudio(Request $request)
    {
        $request->validate([
            'audio' => ['required', 'exists:audio,id'],
            'playlist' => ['required', 'exists:playlists,id'],
        ]);
        if (!$this->access($request->playlist))
        {
            abort(403, "User is not creator of this playlist");
        }
        return DB::table('playlist_audio')
            ->where('audio_id', $request->audio)
            ->where('playlist_id', $request->playlist)
            ->delete();
    }

    public function change(Request $request)
    {
        $request->validate([
            'picture' => ['max:2097152'],
            'id' => ['required', 'exists:playlists,id'],
        ]);
        if (!$this->access($request->id))
        {
            abort(403, "User is not creator of this playlist");
        }
        $playlist = Playlist::find($request->id);
        if (empty($request->file('picture')))
        {
            $picture = $playlist->picture;
        }
        else
        {
            $picture = $request->file('picture')->storePublicly('public/pictures/'.Auth::id());
            $picture = asset(Storage::url($picture));
        }
        $playlist->name = empty($request->name) ?  $playlist->name : $request->name;
        $playlist->description = empty($request->description) ?  $playlist->description : $request->description;
        $playlist->picture = $picture;
        $playlist->save();
        return $playlist;
    }

    public function delete($id)
    {
        if (!$this->access($id))
        {
            abort(403, "User is not creator of this playlist");
        }
        $playlist = Playlist::find($id);
        if (empty($playlist))
        {
            abort(404, "Not found");
        }
        return $playlist->delete();
    }

    public function validator($data)
    {
        return Validator::make($data, [
            'picture' => ['required', 'max:2097152'],
            'name' => 'required',
        ]);
    }

    public function getUserPlaylists()
    {
        $playlists = Auth::user()->playlists()->get();
        foreach ($playlists as $playlist)
        {
            $playlist->count = DB::table('playlist_audio')
                ->where('playlist_id', $playlist->id)
                ->count();
            $audios = $playlist->audios;
            $duration = 0;
            foreach ($audios as $audio)
            {
                $duration += $audio->duration;
            }
            $playlist->duration = $duration;
        }
        return $playlists;
    }
}
