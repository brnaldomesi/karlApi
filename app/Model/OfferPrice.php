<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class OfferPrice extends Model
{
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public $timestamps =false;
    protected $fillable = [
        'offer_id','company_id','invl_start','invl_end','price','calc_method'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'company_id','id'
    ];
}
