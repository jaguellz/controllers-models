<?php

namespace App\Http\Controllers;

use App\Models\Sphere;
use Illuminate\Http\Request;

class SphereController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'name' => ['required', 'unique:spheres,name']
        ]);
        return Sphere::create([
            'name' => $request->name,
        ]);
    }
    public function delete(Request $request)
    {
        $request->validate([
            'name' => ['required', 'exists:spheres,name']
        ]);
        $sphere = Sphere::where('name', $request->name)->first();
        $sphere->delete();
        return "Success";
    }

    public function index()
    {
        return Sphere::select('id', 'name')->get();
    }

    public function getSphere(Request $request)
    {
        $request->validate([
            'id' => ['required', 'exists:spheres,id']
        ]);
        return Sphere::find($request->id);
    }

    public function getSphereCategories(Request $request)
    {
        $request->validate([
            'id' => ['required', 'exists:spheres,id']
        ]);
        return Sphere::find($request->id)->categories;
    }
}
