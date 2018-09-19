<?php
/**
 * Created by PhpStorm.
 * User: lqh
 * Date: 2017/1/1
 * Time: 下午10:28
 */

namespace App\Model;


use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    //不收超出费用
    const SETTLE_TYPE_IGNORE=0;
    //收取超出费用
    const SETTLE_TYPE_ADD=1;
    //由司机决定是否收取
    const SETTLE_TYPE_DRIVER=2;

    const DRIVER_FEE_SHOWN=0;
    const DRIVER_FEE_HIDE=1;

    const PAY_AUTH_DISABLE=0;
    const PAY_AUTH_ENABLE=1;


    const UNIT_MI=1;
    const UNIT_KM=2;

    public $timestamps=false;
    protected $fillable = [
        'company_id',
        'distance_unit',
        'hide_driver_fee',
        'settle_type',
        'pay_auth',
        'disclaimer',
        'mc_key',
        "mc_list_id"
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
    ];
}