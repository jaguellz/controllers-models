<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'name' => ['required', 'unique:categories,name'],
            'sphere' => ['required', 'exists:spheres,id']
        ]);
        return Category::create([
            'name' => $request->name,
            'sphere_id' => $request->sphere,
        ]);
    }
    public function delete(Request $request)
    {
        $request->validate([
            'name' => ['required', 'exists:categories,name']
        ]);
        $cat = Category::where('name', $request->name)->first();
        $cat->delete();
        return "Success";
    }

    public function index()
    {
        return Category::select('id', 'name', 'sphere_id')->get();
    }

    public function getCategory(Request $request)
    {
        $request->validate([
            'id' => ['required', 'exists:categories,id']
        ]);
        return Category::find($request->id);
    }
}
