<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if ($user->inn)
        {
            return new Response(['type' => 'Company', 'profile' => Company::with(['urls', 'vacancies'])->find($user->id)]);
        }
        elseif ($user->username) return new Response(['type' => 'Admin', 'profile' => $user]);
        else
        {
            return new Response(['type' => 'User', 'profile' => User::with(['resumes', 'urls'])->find($user->id)]);
        }

    }

    public function getProfile(Request $request)
    {
        $request->validate([
            'id' => ['required', 'exists:users,id'],
        ]);
        $user = User::with(['resumes', 'urls'])->find($request->id);
        return $user->only('id', 'name', 'resumes', 'urls', 'avatar');
    }

    public function getFavourites()
    {
        $user = Auth::user();
        return $user->favourites()->with(['company', 'category'])->get();
    }

    public function addFavourite(Request $request)
    {
        $request->validate([
            'vacancy' => ['required', 'exists:vacancies,id'],
        ]);
        $user = Auth::user();
        $user->favourites()->attach($request->vacancy);
    }

    public function deleteFavourite(Request $request)
    {
        $request->validate([
            'vacancy' => ['required', 'exists:vacancies,id'],
        ]);
        $user = Auth::user();
        $user->favourites()->detach($request->vacancy);
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'mimes:jpg,png']
        ]);
        $user = Auth::user();
        $avatar = $request->file('avatar')->storePublicly('public/avatars/'.$user->id);
        $path = Storage::url($avatar);
        $user->avatar = url($path);
        $user->save();
        return $user;
    }

    public function addContact(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'url' => ['required', 'url']
        ]);
        $user = Auth::user();
        if ($user->inn)
        {
            return new Response(['Contact' => Contact::create([
                'name' => $request->name,
                'url' => $request->url,
                'company_id' => $user->id,
            ])]);
        }
        return new Response(['Contact' => Contact::create([
            'name' => $request->name,
            'url' => $request->url,
            'user_id' => $user->id,
        ])]);
    }

    public function deleteContact(Request $request)
    {
        $request->validate([
            'id' => ['required', 'exists:contacts,id']
        ]);
        $contact = Contact::find($request->id);
        $contact->delete();
        return new Response(['message' => 'Successfully deleted']);
    }

    public function getUrls()
    {
        return Auth::user()->urls;
    }

    public function stats()
    {
        $user = Auth::user();
        $feedbacks = 0;
        $invites = 0;
        foreach ($user->resumes as $resume)
        {
            $feedbacks += $resume->feedbacks()->count();
            $invites += $resume->feedbacks()->whereNotNull('answer')->count();
        }
        return new Response([
            'Feedbacks' => $feedbacks,
            'Invites' => $invites,
        ]);
    }
}
