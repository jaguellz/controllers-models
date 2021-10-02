<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpParser\Error;

class UserController extends Controller
{
    public function validator($data)
    {
        return Validator::make($data, [
            'picture' => ['max:2097152'], //2 Mb
            'phone' => ['regex:/[7-8]{1}[0-9]{10}/', 'size:11']
        ]);
    }
    public function getUsers(UserRepository $userRepository)
    {
        return $userRepository->getUsers();
    }

    public function deleteUser(Request $request, UserRepository $userRepository)
    {
        $request->validate([
            'id' => ['required', 'exists:users,id'],
        ]);
        return $userRepository->deleteUser($request->id);
    }

    public function createUser(Request $request, UserRepository $userRepository)
    {
        $request->validate([
            'phone' => ['required', 'regex:/[7-8]{1}[0-9]{10}/', 'size:11'],
        ]);
        return $userRepository->createUser($request->only(['phone', 'name']));
    }

    public function updateUser(Request $request, UserRepository $userRepository)
    {
        $request->validate([
            'id' => ['required', 'exists:users,id'],
        ]);
        return $userRepository->updateUser($request->id, $request->only('name', 'phone'), $request->file( 'picture'));
    }

    public function change(Request $request, UserRepository $userRepository)
    {
        $this->validator($request->all())->validate();
        return $userRepository->updateUser(Auth::id(), $request->only('name'), $request->file('picture'));
    }

    public function getUser()
    {
        $user = User::find(Auth::id());
        if ($user->subscribe)
        {
            $space = 'subscriber';
        }
        else
        {
            $space = 500 * 1024 * 1024; //500 Megabytes
            $files = Storage::allFiles('public/audios/'.Auth::id());
            foreach ($files as $file){
                $space -= Storage::size($file);
            }
        }
        $user->space = ['free' => $space / (1024*1024), 'max' => 500];
        return $user;
    }

    public function delete()
    {
        $audios = DB::table('audio')->where('user_id', Auth::id())->get();
        foreach ($audios as $audio)
        {
            DB::table('playlist_audio')
                ->where('audio_id', $audio['id'])
                ->delete();
            $paths = [$audio['url'], $audio['picture']];
            Storage::delete($paths);
        }
        DB::table('playlists')->where('user_id', Auth::id())->delete();
        DB::table('audio')->where('user_id', Auth::id())->delete();
        DB::table('users')->where('id', Auth::id())->delete();
        return 'Deleted';
    }
}
