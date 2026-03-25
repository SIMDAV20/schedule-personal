<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = [
        'paseador_id',
        'date',
        'start_time',
        'end_time',
        'district_id',
        'client_name',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public function paseador()
    {
        return $this->belongsTo(User::class, 'paseador_id');
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }
}
