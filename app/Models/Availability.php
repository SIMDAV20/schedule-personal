<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Availability extends Model
{
    protected $fillable = [
        'user_id',
        'day',
        'start_time',
        'end_time',
    ];

    const DAYS = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo',
    ];

    protected $casts = [
        'day' => 'integer',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    protected $appends = ['day_name'];

    /**
     * Get the day name in Spanish.
     */
    public function getDayNameAttribute(): string
    {
        return self::DAYS[$this->day] ?? '--';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function districts()
    {
        return $this->belongsToMany(District::class);
    }
}
