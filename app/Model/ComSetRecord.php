<?php
/**
 * Created by PhpStorm.
 * User: lqh
 * Date: 2017/1/1
 * Time: 下午10:28
 */

namespace app\Model;


use Illuminate\Database\Eloquent\Model;

class ComSetRecord extends Model
{
    const TYPE_PUSH =0;
    const TYPE_PAY =1;
    const TYPE_AN = 2;
    const TYPE_OFFER=3;
    const TYPE_SETTING=4;
    const TYPE_OPTION=5;

    const CHANGE_TYPE_ADD=0;
    const CHANGE_TYPE_UPDATE=1;
    const CHANGE_TYPE_DELETE=2;


    protected $fillable = [
        'company_id',
        'admin_id',
        'user_id',
        'user_name',
        'type',
        'change_type',
        'from_info',
        'to_info',
        'change_time'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
    ];
}