<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{

    const DRIVER_TYPE = 1;
    const CAR_TYPE = 2;
    const OFFER_TYPE = 3;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type','owner_id','routine','dst_routine','dst','company_id',"timezone"
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'created_at','updated_at'
    ];
}
