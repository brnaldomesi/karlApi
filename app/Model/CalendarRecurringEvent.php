<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CalendarRecurringEvent extends Model
{

    public $timestamps = false;
    const UNREPEAT = 0;
    const REPEAT = 1;

    const REPEAT_TYPE_DAY = 0;
    const REPEAT_TYPE_WEEK = 1;
    const REPEAT_TYPE_MONTH = 2;
    const REPEAT_TYPE_YEAR = 3;

    /**
     * The attributes that are mass assignable.
     * repeat_type 0 日重复,1 周重复,2 月重复,3 年重复
     *  creator_id 为创建者id
     *  creator_type 创建者类型 0 booking , 1 admin , 2 自建
     * @var array
     */
    protected $fillable = [
        'owner_id','owner_type',
        'content','start_time',
        'duration_time','repeat_type',
        'creator_id','creator_type',
        'time_zone'
    ];

}
