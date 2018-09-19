<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/2/29
 * Time: 17:09
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model{
    protected $fillable = [
        'user_id',
        'company_id',
        "booking_total",
        "count_total",
        //,'device_token'
    ];

    protected $hidden = [
        'updated_at','created_at'
    ];
}