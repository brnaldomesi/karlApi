<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/2/29
 * Time: 17:09
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Order extends Model{
    /**
     * order_state  0:已下定,1:已执行,2:司机未出发,3:结算异常,4:订单完成,5:admin取消 ,
     * 6:super admin 取消 7:乘客取消 8:custom quote超时取消 9:custom quote待确认
     */
    const ORDER_STATE_BOOKING = 0;
    const ORDER_STATE_RUN = 1;
    const ORDER_STATE_DRIVER_UNRUN = 2;
    const ORDER_STATE_SETTLE_ERROR = 3;
    const ORDER_STATE_DONE = 4;
    const ORDER_STATE_ADMIN_CANCEL = 5;
    const ORDER_STATE_SUPER_ADMIN_CANCEL = 6;
    const ORDER_STATE_PASSENGER_CANCEL = 7;
    const ORDER_STATE_TIMES_UP_CANCEL = 8;
    const ORDER_STATE_WAIT_DETERMINE = 9;

    /**
     * trip_state   0:已预订未出发,1:去乘客上车地点,2:等待乘客上车,3:去目的地,4:等待司机结算,5:等待付费中,6:付费中,7:付款结束
     */
    const TRIP_STATE_WAIT_TO_DEPARTURE = 0;
    const TRIP_STATE_DRIVE_TO_PICK_UP = 1;
    const TRIP_STATE_WAITING_CUSTOMER = 2;
    const TRIP_STATE_GO_TO_DROP_OFF = 3;
    const TRIP_STATE_WAITING_DRIVER_DETERMINE = 4;
    const TRIP_STATE_WAITING_TO_SETTLE = 5;
    const TRIP_STATE_SETTLING = 6;
    const TRIP_STATE_SETTLE_DONE = 7;


    const ADMIN_ACTION_START=1;
    const ADMIN_ACTION_END=2;

    const ARCHIVE_TYPE_RESTORE=0;
    const ARCHIVE_TYPE_ARCHIVE=1;

    protected $fillable = [
        'booking_id','order_state',
        'trip_state','departure_time',
        'start_time','reach_time',
        'finish_time','settle_time',
        'actual_distance','actual_time',
        'actual_fee','free_fee',"unit",
        'admin_action','admin_id',
        'feedbacked','invoice_sent','archive',
        "last_lat",
        "last_lng",
        "last_address",
        "last_speed",
        "last_distance",
        "last_report_time"
    ];

    protected $hidden = [
        'updated_at','created_at'
    ];
}