<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CarModelImg extends Model
{


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'car_model_id','image_path','priority'
    ];

    protected $hidden = [
        'created_at','updated_at'
    ];
  
}
