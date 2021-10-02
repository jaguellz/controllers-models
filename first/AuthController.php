<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use PHPUnit\Framework\Error;

class AuthController extends Controller
{
    /**
     * Updates user's api token
     * @param $user
     * @return array
     */
    public function login($user)
    {
        $token = Str::random(60);

        $user->forceFill([
            'api_token' => hash('sha256', $token),
        ])->save();

        return ['token' => $token];
    }

    public function sendCode(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'regex:/[7-8]{1}[0-9]{10}/', 'size:11'],
        ]);
        if(!User::where('phone', $request->phone)->exists())
        {
            $user = $this->create($request->phone);
        }
        else
        {
            $user = User::where('phone', $request->phone)->first();
        }
        $code = /*rand(100000, 999999);*/ 1234;
        $response = Http::get("https://sms.ru/sms/send",[
            'api_id' => '48033C48-6462-838D-196B-B339CD66874A',
            'to' => $request->phone,
            'msg' => $code,
            'json' => 1,
            'test' => 1,
        ]);
        $user->forceFill([
            'auth_code' => $code,
        ])->save();
        return ['status' => $response['sms'][$request->phone]['status_code']];
    }

    protected function checkCode($user, $code)
    {
        if ($user->auth_code == $code)
        {
            $user->forceFill([
                'auth_code' => null,
            ])->save();
            return true;
        }

        return false;
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
    public function auth(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'regex:/[7-8]{1}[0-9]{10}/', 'size:11', 'exists:users,phone'],
            'code' => ['required'],
        ]);
        $user = User::where('phone', $request->phone)->first();
        $code = $request->code;
        if (!$this->checkCode($user, $code)){
            abort(400, 'Invalid code');
        }
        return $this->login($user);
    }
}
