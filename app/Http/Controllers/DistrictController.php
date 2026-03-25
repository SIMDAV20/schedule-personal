<?php

namespace App\Http\Controllers;

use App\Models\District;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DistrictController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Districts', [
            'districts' => District::withCount('availabilities')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|unique:districts,name']);
        District::create([
            'name'       => $data['name'],
            'slug'       => Str::slug($data['name']),
            'created_by' => auth()->id(),
        ]);
        return back();
    }

    public function destroy(District $district)
    {
        $district->delete();
        return back();
    }
}
