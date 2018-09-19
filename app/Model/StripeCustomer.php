<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 2016/10/21
 * Time: 下午6:43
 */

namespace App\Model;


use Illuminate\Database\Eloquent\Model;

class StripeCustomer extends Model
{
    public $timestamps = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        self::CUSTOMER_ID,
        self::STRIPE_CUSTOMER_ID
    ];


    const CUSTOMER_ID = "customer_id";
    const STRIPE_CUSTOMER_ID = "stripe_customer_id";
}