<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/2/29
 * Time: 17:09
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Bill extends Model{
    public $timestamps = false;

    /**
     * booking_id 订单id
     */
    const BookingId = 'bills.booking_id';
    /**
     * order_actual_fee 订单实际费用
     */
    const OrderActualFee = 'bills.order_actual_fee';
    /**
     * settle_fee 通过平台实际结算成功费用,
     */
    const SettleFee = 'bills.settle_fee';
    /**
     * an_fee AN费用,以settle_fee结算
     */
    const ANFee = 'bills.an_fee';
    /**
     * settle_time 结算时间
     */
    const SettleTime = 'bills.settle_time';
    /**
     * platform_income 平台应收费用,以settle_fee结算
     */
    const PlatformIncome = 'bills.platform_income';



    protected $fillable = [
        'booking_id',
        'order_actual_fee',
        'settle_fee',
        'com_income',
        'an_fee',
        "ccy",
        'settle_time',
        'exe_com_id',
        'own_com_id',
        'own_trans_id',
        'exe_trans_id',
        'platform_income'
    ];

}