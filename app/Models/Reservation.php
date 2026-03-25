<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = [
        'status',
        'paseador_id',
        'date',
        'start_time',
        'end_time',
        'district_id',
        'client_name',
        'notes',
    ];

    const STATUS = [
        'pending' => 'Pendiente',
        'confirmed' => 'Confirmado',
        'cancelled' => 'Cancelado',
    ];

    protected $casts = [
        'status' => 'string',
        'date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    protected $appends = ['status_name'];

    public function paseador()
    {
        return $this->belongsTo(User::class, 'paseador_id');
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function getStatusNameAttribute(): string
    {
        return self::STATUS[$this->status] ?? '--';
    }
}
