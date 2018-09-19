<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CompanyPushMsg extends Model
{

    public $timestamps = false;


    const PM_NEW_TRIP_D             = "You have a new trip";

    const PM_NEW_TRIP_C             = "Your trip has been booked! Check your 'upcoming rides' for details";

    const PM_TRIP_CHANGE_C          = "Your booking has been changed ,please check";

    const PM_TRIP_CANCEL_D          = "You have a trip has been canceled";

    const PM_TRIP_CHANGE_D          = "You have a trip has been changed";

    const PM_TRIP_CANCEL            = "Your trip has canceled";

    const PM_TRIP_BEGIN_IN24        = "your trip will begin in 24 hours";

    const PM_TRIP_BEGIN_IN1         = "your trip will begin in one hour";

    const PM_CUSTOMER_ARRIVED_C     = "You have arrived! Thank you for riding with";

    const PM_CUSTOMER_DEPARTURE_C   = "You are on your way! Enjoy your trip";

    const PM_DRIVER_ARRIVED_C       = "Your driver has arrived";

    const PM_DRIVER_DEPARTURE_C     = "Your driver is on their way! Go to 'Current Trip' to track their location";



    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
       "company_id",
        "ios_app",
        "android_app"
    ];

}
