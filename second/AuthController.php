<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\Concerns\Has;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use PHPUnit\Framework\Error;

class AuthController extends Controller
{
    public function sendCompanyCode(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'exists:companies,phone'],
        ]);
        $company = Company::where('phone', $request->phone)->first();
        $code = /*rand(100000, 999999);*/ 1234;
        $company->forceFill([
            'auth_code' => $code,
        ])->save();
        return new Response(['message' => "Successfully sent"]);
    }
    public function sendUserCode(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'exists:users,phone'],
        ]);
        $user = User::where('phone', $request->phone)->first();
        $code = /*rand(100000, 999999);*/ 1234;
        $user->forceFill([
            'auth_code' => $code,
        ])->save();
        return new Response(['message' => "Successfully sent"]);
    }

    /**
     * Updates user's api token
     * @param $user
     * @param $password
     * @return Response
     */
    public function login($user, $password)
    {
        if ($password != $user->auth_code)
        {
            return new Response(['message' => 'Wrong code'], 400);
        }
        $token = $user->api_token;
        $user->forceFill([
            'auth_code' => null,
        ])->save();
        return new Response(['token' => $token], 200);
    }

    public function loginUser(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'exists:users,phone'],
        ]);
        $user = User::where('phone', $request->phone)->first();
        return $this->login($user, $request->code);
    }

    public function loginCompany(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'exists:companies,phone'],
        ]);
        $company = Company::where('phone', $request->phone)->first();
        return $this->login($company, $request->code);
    }

    public function registerUser(Request $request)
    {
        $request->validate([
            'phone' => ['required', /*'regex:/[7-8]{1}[0-9]{10}/',*/ 'size:11', 'unique:users,phone'],
            'name' => ['required']
        ]);
        $token = Str::random(60);
        return User::create([
            'phone' => $request->phone,
            'name' => $request->name,
            'api_token' =>  $token,
        ]);
    }

    public function loginAdmin(Request $request)
    {
        $request->validate([
            'username' => ['required', 'exists:admins,username'],
        ]);
        $user = Admin::where('username', $request->username)->first();
        if (!Hash::check($request->password, $user->password))
        {
            return new Response(['message' => 'Wrong password'], 400);
        }
        return $user->api_token;
    }

    public function registerCompany(Request $request)
    {
        $request->validate([
            'phone' => ['required', /*'regex:/[7-8]{1}[0-9]{10}/',*/ 'size:11','unique:companies,phone'],
            'bin' => ['required',],
            'bik' => ['required',],
            'address' => ['required',],
            'inn' => ['required',],
            'name' => ['required',],
        ]);
        $token = Str::random(60);
        return Company::create([
            'name' =>$request->name,
            'phone' => $request->phone,
            'bin' => $request->bin,
            'bik' => $request->bik,
            'address' => $request->address,
            'inn' => $request->inn,
            'api_token' =>  $token,
        ]);
    }

    public function changepassword(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'exists:users,phone'],
            'password' => ['required', 'min:8'],
        ]);
        $user = User::where('phone', $request->phone)->first();

    }

    public function updatetoken($user)
    {
        $token = Str::random(60);
        $user->forceFill([
            'api_token' => $token,
        ])->save();
        return $token;
    }

    public function accounts()
    {
        $user = Auth::user();
        $p = $user->phone;
        if ($user->inn)
        {
            if (!User::where('phone', $p)->exists())
            {
                $second = 'Second account not exists';
            }
            else
            {
                $second = User::where('phone', $p)->first();
            }
        }
        else
        {
            if (!Company::where('phone', $p)->exists())
            {
                $second = 'Second account not exists';
            }
            else
            {
                $second = Company::where('phone', $p)->first();
            }
        }
        return new Response([
            'Current' => $user,
            "Second" => $second
        ]);
    }

    public function changeUser()
    {
        $user = Auth::user();
        $p = $user->phone;
        if ($user->inn)
        {
            if (!User::where('phone', $p)->exists())
            {
                return new Response(['message' => 'Second account not exists']);
            }
            return new Response(['token' => User::select('*')->where('phone', $p)->first()->api_token]);
        }
        else
        {
            if (!Company::where('phone', $p)->exists())
            {
                return new Response(['message' => 'Second account not exists']);
            }
            return new Response(['token' => Company::select('*')->where('phone', $p)->first()->api_token]);
        }
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param $phone
     * @return User
     */
    protected function create($phone)
    {
        return User::create([
            'phone' => $phone,
        ]);
    }
}
