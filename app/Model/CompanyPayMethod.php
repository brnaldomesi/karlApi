<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/2/29
 * Time: 17:09
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CompanyPayMethod
 * String id
 * String secret
 * @package App\Model
 */
class CompanyPayMethod extends Model{

    const ACTIVE = 1;
    const NEGATIVE = 0;

    /**
     * @desc pay pal payment
     */
    const PAY_TYPE_FREE = 0;
    /**
     * @desc pay pal payment
     */
    const PAY_TYPE_PAY_PAL = 1;
    /**
     * @desc chase bank payment
     */
    const PAY_TYPE_CHASE = 2;
    /**
     * @desc stripe payment
     */
    const PAY_TYPE_STRIPE = 3;

    const PAY_AUTH_DAY_PAY_PAL = 3;
    const PAY_AUTH_DAY_STRIPE = 7;


    protected $fillable = [
        'pay_type','client_id',
        'secret','account','active',
        'company_id',
    ];

    protected $hidden = [
        'updated_at','created_at'
    ];


    public static function getPaymentMethod($type)
    {
        if($type == self::PAY_TYPE_PAY_PAL){
            return "PayPal ";
        }elseif ($type == self::PAY_TYPE_STRIPE){
            return "Stripe ";
        }
        return "Unknown ";
    }
}