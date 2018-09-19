<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class OfferDriverCar extends Model
{

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'car_id','driver_id','offer_id'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
}
