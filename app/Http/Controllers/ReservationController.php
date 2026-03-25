<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    // ReservationController@store
    public function store(Request $request)
    {
        $data = $request->validate([
            'paseador_id'  => 'required|exists:users,id',
            'date'         => 'required|date',
            'start_time'   => 'required',
            'end_time'     => 'required',
            'district_id'  => 'required|exists:districts,id',
            'client_name'  => 'required|string|max:255',
            'notes'        => 'nullable|string',
        ]);

        Reservation::create($data);

        return to_route('admin.schedule');
    }

    public function destroy(Reservation $reservation)
    {
        $reservation->delete();
        return to_route('admin.schedule');
    }
}
