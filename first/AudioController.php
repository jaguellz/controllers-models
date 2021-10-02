<?php

namespace App\Http\Controllers;

use App\Models\Audio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpParser\Error;

class AudioController extends Controller
{
    public function validator($data)
    {
        return Validator::make($data, [
            'name' => 'required',
            'audio' => ['required','max:'.$this->freeSpace(Auth::id())],
            'picture' => ['required', 'max:2097152'],
            'description' => 'required',
        ]);
    }

    public function access($id)
    {
        $audio = Audio::find($id);
        return $audio->user_id == Auth::id();
    }

    public function upload(Request $request)
    {
        $this->validator($request->all())->validate();
        $audio = $request->file('audio')->storePublicly('public/audios/'.Auth::id());
        $audio = Storage::url($audio);
        $pic = $request->file('picture')->storePublicly('public/pictures/'.Auth::id());
        $pic = Storage::url($pic);
        return Audio::create([
            'name' => $request->name,
            'description' => $request->description,
            'url' => asset($audio),
            'picture' => asset($pic),
            'user_id' => Auth::id(),
            'duration' => $request->duration,
        ]);
    }

    public function getUserAudios()
    {
        $audio = Audio::where('user_id', Auth::id())
            ->where('deleted', 0)
            ->get();
        return $audio;
    }

    public function getAudio(Request $request)
    {
        $request->validate([
            'id' => ['required', 'exists:audio,id']
        ]);
        return Audio::find($request->id);
    }

    public function recentlyDeleted()
    {
        $audio = Audio::where('user_id', Auth::id())
            ->where('deleted', 1)
            ->get();
        return $audio;
    }

    public function restoreAudio(Request $request)
    {
        $request->validate([
            'id' => ['required', 'exists:audio,id']
        ]);
        if (!$this->access($request->id))
        {
            abort(403, "User is not creator of this playlist");
        }
        $audio = Audio::find($request->id);
        if ($audio->deleted)
        {
            $audio->deleted = 0;
            return $audio->save();
        }
        abort(400, 'This audio is not deleted');
    }

    public function change(Request $request)
    {
        $request->validate([
            'picture' => ['max:2097152'],
            'id' => ['required', 'exists:audio,id'],
        ]);
        if (!$this->access($request->id))
        {
            abort(403, "User is not creator of this playlist");
        }
        $audio = Audio::find($request->id);
        if (empty($request->file('picture')))
        {
            $picture = $audio->picture;
        }
        else
        {
            $picture = $request->file('picture')->storePublicly('public/pictures/'.Auth::id());
            $picture = asset(Storage::url($picture));
        }
        $audio->name = empty($request->name) ?  $audio->name : $request->name;
        $audio->description = empty($request->description) ?  $audio->description : $request->description;
        $audio->picture = $picture;
        $audio->save();
        return $audio;
    }

    public function deleteAudio(Request $request)
    {
        $request->validate([
            'id' => ['required', 'exists:audio,id']
        ]);
        $audio = Audio::find($request->id);
        if (!$this->access($request->id))
        {
            abort(403, "User is not creator of this audio");
        }

        DB::table('playlist_audio')->where('audio_id', $audio->id)->delete();
        if (!$audio->deleted)
        {
            $audio->deleted = 1;
            return $audio->save();
        }
        $paths = [$audio->url, $audio->picture];
        Storage::delete($paths);
        return $audio->delete();
    }

    public function freeSpace($id)
    {
        $space = 500 * 1024 * 1024; //500 Megabytes
        $files = Storage::allFiles('public/audios/'.$id);
        foreach ($files as $file){
            $space -= Storage::size($file);
        }
        return $space;
    }
}
