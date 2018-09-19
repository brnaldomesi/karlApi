<?php

namespace App\Model;

use App\Method\MethodAlgorithm;
use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{
    
    const CREATOR_TYPE_BOOKING = 0;
    const CREATOR_TYPE_ADMIN = 1;
    const CREATOR_TYPE_SELF = 2;
    
    const EVENT_ENABLE = 1;
    const EVENT_DISABLE = 0;
    /**
     * The attributes that are mass assignable.
     *  creator_id 为创建者id
     *  creator_type 创建者类型 0 booking , 1 admin , 2 自建  
     * @var array
     */
    protected $fillable = [
        'calendar_id','start_time','end_time',
        'content','re_owner_id',
        're_type','re_company_id',
        'creator_id','creator_type','enable','repeat_id'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'created_at','updated_at','re_owner_id','re_type','re_company_id'
    ];


    public static function checkEventTime($startTime, $endTime)
    {
        return "
        (('" . MethodAlgorithm::formatTimestampToDate($startTime) . "' <= start_time AND
                '" . MethodAlgorithm::formatTimestampToDate($endTime) . "' >= start_time AND
                '" . MethodAlgorithm::formatTimestampToDate($endTime) . "' <= end_time)
            OR
            ('" . MethodAlgorithm::formatTimestampToDate($startTime) . "'<= start_time AND
                '" . MethodAlgorithm::formatTimestampToDate($endTime) . "' >= end_time)
            OR
            ('" . MethodAlgorithm::formatTimestampToDate($startTime) . "'>= start_time AND
                '" . MethodAlgorithm::formatTimestampToDate($endTime) . "' <= end_time)
            OR
            ('" . MethodAlgorithm::formatTimestampToDate($startTime) . "'>= start_time AND
                '" . MethodAlgorithm::formatTimestampToDate($startTime) . "'<= end_time AND
                '" . MethodAlgorithm::formatTimestampToDate($endTime) . "' >= end_time)
        )";
    }
}
