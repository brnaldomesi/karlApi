<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/2/29
 * Time: 17:09
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Track extends Model{
    protected $fillable = [
        'order_id','line',
        'lat','lng',"unit",
        'distance','address',
        'speed','trip_cost','pointed_at'
    ];

    protected $hidden = [
        'updated_at','created_at'
    ];
}