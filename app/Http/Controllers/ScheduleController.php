<?php

namespace App\Http\Controllers;

use App\Models\Availability;
use App\Models\District;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $districtId = $request->query('district_id');

        $paseadores = User::where('role', 'paseador')
            ->with([
                'availabilities' => function ($q) use ($districtId) {
                    $q->with('districts');
                    if ($districtId) {
                        $q->whereHas('districts', fn($d) => $d->where('districts.id', $districtId));
                    }
                },
                'reservations' => function ($q) use ($districtId) {
                    $q->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]);
                    if ($districtId) {
                        $q->where('district_id', $districtId);
                    }
                },
            ])
            ->get();

        $pendingReservations = Reservation::whereNull('paseador_id')
                                    ->with('district')
                                    ->orderBy('date')
                                    ->get();

        return Inertia::render('Admin/Schedule', [
            'paseadores'       => $paseadores,
            'pendingReservations' => $pendingReservations,
            'districts'        => District::orderBy('name')->get(),
            'selectedDistrict' => $districtId ? (int)$districtId : null,
            'days'             => array_values(Availability::DAYS),
        ]);
    }
}
