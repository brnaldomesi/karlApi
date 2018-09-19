<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/2/29
 * Time: 17:09
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model{
    protected $fillable = [
        'user_id',
        "hidden_last",
        'license_number',
        'company_id'//,'device_token'
        ,'delay_time',
        'count_rating',
        'count_appear',
        'count_profess',
        'count_drive',
        'count_clean'
    ];

    protected $hidden = [
        'updated_at','created_at'
    ];


    const RATING_DB="case when drivers.count_rating >= 10
    THEN round(drivers.count_appear/drivers.count_rating,2)
    ELSE 0.00
    END as avr_appear_rating,
  case when drivers.count_rating >= 10
    THEN round(drivers.count_profess/drivers.count_rating,2)
    ELSE 0.00
    END as avr_profess_rating,
  case when drivers.count_rating >= 10
    THEN round(drivers.count_drive/drivers.count_rating,2)
    ELSE 0.00
    END as avr_drive_rating,
  case when drivers.count_rating >= 10
    THEN round(drivers.count_clean/drivers.count_rating,2)
    ELSE 0.00
    END as avr_clean_rating";
    const AVG_DB=
        "case when drivers.count_rating >= 10
    THEN round((drivers.count_appear+drivers.count_drive+drivers.count_clean+drivers.count_profess)/drivers.count_rating/4,2)
    ELSE 0.00
    END as avg_rating";
//    const SEARCH_BD_8="drivers.count_rating>=10 and (drivers.count_appear+drivers.count_drive+drivers.count_clean+drivers.count_profess)/drivers.count_rating/4 >=8.0";
    const SEARCH_BD_8="drivers.count_rating>=0";
}