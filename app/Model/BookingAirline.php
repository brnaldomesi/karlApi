<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class BookingAirline extends Model
{

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
       "booking_id",
        "a_airline",
        "a_airline_code",
        "d_airline",
        "d_airline_code",
        "a_flight",
        "d_flight"
    ];

}
