<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class CompanyController extends Controller
{
    public function getProfile(Request $request)
    {
        $request->validate([
            'id' => ['required', 'exists:companies,id'],
        ]);
        return Company::with(['vacancies', 'urls'])->find($request->id);
    }

    public function subscribe(Request $request)
    {
        $request->validate([
            'days' => ['required']
        ]);
        $c = Auth::user();
        if ($c->subscribe > Carbon::now()) $c->subscribe = Carbon::createFromTimeString($c->subscribe)->addDays($request->days);
        else $c->subscribe = Carbon::today()->addDays($request->days);
        $c->save();
        return 'Successfully subscribed';
    }

    public function subStatus()
    {
        return Auth::user()->subscribe;
    }
}
