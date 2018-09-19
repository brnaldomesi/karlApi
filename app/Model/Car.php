<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'car_model_id','company_id',
        'license_plate',"pre_time",'img','description',
        'color','type','year','bags_max','seats_max'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'created_at','updated_at'
    ];

    const RATING_DB="case when cars.count_rating > 10
    THEN 
        round(cars.count_quality/cars.count_rating,2)
    ELSE 0.00
    END as avr_rating";

//    const SEARCH_BD_8="cars.count_rating>=10 and cars.count_quality/cars.count_rating >=8.0";
    const SEARCH_BD_8="cars.count_rating>=0";

}
