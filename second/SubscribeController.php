<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SubscribeController extends Controller
{
    public function setPrice(Request $request)
    {
        $request->validate([
            'price' => ['required', 'numeric']
        ]);
        config(['app.subscription_price' => $request->price]);
        return $this->getPrice();
    }

    public function getPrice()
    {
        return config('app.subscription_price');
    }
}
