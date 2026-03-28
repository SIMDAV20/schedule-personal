<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $search = $request->query('search');
        $status = $request->query('status');

        $query = Reservation::with(['paseador', 'district']);

        if ($user->role === 'paseador') {
            $query->where('paseador_id', $user->id);
        }

        if ($search) {
            $query->where('client_name', 'like', "%{$search}%");
        }

        if ($status) {
            $query->where('status', $status);
        }

        $reservations = $query->latest('date')->latest('start_time')->get();

        $props = [
            'reservations' => $reservations,
            'filters' => $request->only(['search', 'status']),
        ];

        return $user->role === 'admin'
            ? inertia('Admin/Reservations', $props)
            : inertia('Paseador/MyReservations', $props);
    }

    // ReservationController@store
    public function store(Request $request)
    {
        $data = $request->validate([
            'paseador_id'  => 'nullable|exists:users,id',
            'date'         => 'required|date',
            'start_time'   => 'required',
            'end_time'     => 'required',
            'district_id'  => 'required|exists:districts,id',
            'client_name'  => 'required|string|max:255',
            'notes'        => 'nullable|string',
        ]);

        $data['status'] = isset($data['paseador_id']) ? 'confirmed' : 'pending';

        Reservation::create($data);

        return to_route('admin.schedule');
    }

    public function assign(Request $request, Reservation $reservation)
    {
        $data = $request->validate([
            'paseador_id' => 'required|exists:users,id',
        ]);

        $reservation->update([
            'paseador_id' => $data['paseador_id'],
            'status'      => 'confirmed',
        ]);

        return to_route('admin.schedule', status: 303);
    }

    public function destroy(Reservation $reservation)
    {
        $reservation->delete();
        return to_route('admin.schedule', status: 303);
    }
}
