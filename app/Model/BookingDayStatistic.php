<?php
/**
 * Created by PhpStorm.
 * User: lqh
 * Date: 2016/12/16
 * Time: 上午10:27
 */

namespace App\Model;


use Illuminate\Database\Eloquent\Model;

class BookingDayStatistic extends Model
{

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'completed_bookings',
        'total_bookings',
        'on_time',
        'invalid_count',
        'cancel_count',
        'invalid_count',
        'trouble_count',
        'cancel_count',
        'exe_an_count',
        'out_an_count',
        'an_count',
        'p2p_count',
        'hour_count',
        'cq_count',
        'appearance_count',
        'professionalism_count',
        'driving_count',
        'cleanliness_count',
        'quality_count',
        'total_est_amount',
        'total_income',
        'total_plate',
        'total_an_fee',
        'stat_date',
        'stat_day',
        'stat_week',
        'stat_week_year',
        'stat_month',
        'stat_year'
    ];
}