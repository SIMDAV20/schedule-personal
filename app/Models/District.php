<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $fillable = ['name', 'slug', 'created_by'];

    public function availabilities() {
        return $this->belongsToMany(Availability::class);
    }

    public function reservations() {
        return $this->hasMany(Reservation::class);
    }
}
