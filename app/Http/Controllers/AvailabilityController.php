<?php

namespace App\Http\Controllers;

use App\Models\Availability;
use App\Models\District;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AvailabilityController extends Controller
{
    public function index() {
        return Inertia::render('Paseador/MySchedule', [
            'availabilities' => auth()->user()->availabilities()->with('districts')->get(),
            'districts' => District::orderBy('name')->get(),
            'days' => Availability::DAYS,
        ]);
    }

    public function store(Request $request) {
        $data = $request->validate([
            'day'          => "required|in:" . implode(",", array_keys(Availability::DAYS)),
            'start_time'   => 'required|date_format:H:i',
            'end_time'     => 'required|date_format:H:i|after:start_time',
            'district_ids' => 'required|array',
            'district_ids.*' => 'exists:districts,id'
        ]);

        $availability = auth()->user()->availabilities()->create($data);
        $availability->districts()->attach($data['district_ids']);
        return back();
    }

    public function update(Request $request, Availability $availability) {

        $data = $request->validate([
            'day'          => "required|in:" . implode(",", array_keys(Availability::DAYS)),
            'start_time'   => 'required|date_format:H:i',
            'end_time'     => 'required|date_format:H:i|after:start_time',
            'district_ids' => 'required|array',
            'district_ids.*' => 'exists:districts,id'
        ]);


        /**
         * validate if exist an item with the same hrs and district
         */
        $exists = Availability::where('user_id', auth()->id())
            ->where('day', $data['day'])
            ->where('start_time', $data['start_time'])
            ->where('end_time', $data['end_time'])
            ->whereHas('districts', fn ($q) => $q->whereIn('districts.id', $data['district_ids']))
            ->where('id', '!=', $availability->id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Ya existe una disponibilidad con las mismas horas y distrito');
        }

        $availability->update($data);
        $availability->districts()->sync($data['district_ids']);
        return back();
    }

    public function destroy(Availability $availability) {
        $availability->delete();
        return back();
    }
}
