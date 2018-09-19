<?php

namespace App\Model;

use App\ErrorCode;
use Illuminate\Database\Eloquent\Model;

class CreditCard extends Model
{

    const ACTIVE = 1;
    const NEGATIVE = 0;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    const VISA = 1;
    const MasterCard = 2;
    const AmericanExpress = 3;
    const DISCOVER = 4;

    const TYPE_CUSTOMER = 1;
    const TYPE_COMPANY = 2;

    const CHECK_PASSED = 1;
    const CHECK_FAILED = 0;

    protected $fillable = [
        'owner_id','type','card_type','card_token',
        'last_use','valid_until','card_number',
        'pay_method_id',
        'check_pass'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'id','owner_id','type','created_at','updated_at'
    ];


    public static function checkCreditCardInfo($card_type,
                                           $card_number, $expire_month,
                                               $expire_year, $cvv2, $first_name,
                                               $last_name)
    {
        if (is_null($card_number) || is_null($card_type) ||
            is_null($expire_month) || is_null($expire_year) ||
            is_null($cvv2) || is_null($first_name) ||
            is_null($last_name)
        ) {
            return ErrorCode::errorMissingParam("in card");
        }
        if (empty($card_type) || !is_numeric($card_type) || ($card_type != 1 && $card_type != 2 && $card_type != 3 && $card_type != 4)) {
            return ErrorCode::errorParam("card_type");
        }

        switch ($card_type) {
            case CreditCard::VISA:   //VISA
                if (!preg_match('/^(4\d{12}(?:\d{3})?)$/', $card_number)) {

                    return ErrorCode::errorParam('card number in ' . $card_type);
                }
                if (!preg_match('/^[0-9]{3}$/', $cvv2)) {
                    return ErrorCode::errorParam('cvv2 in error in visa');
                }
                break;
            case CreditCard::MasterCard:  //MasterCard
                if (!preg_match('/^(5[1-5][0-9]{14})$/', $card_number)) {
                    return ErrorCode::errorParam('card number in ' . $card_type);
                }
                if (!preg_match('/^[0-9]{3}$/', $cvv2)) {
                    return ErrorCode::errorParam('cvv2 in error in MasterCard');
                }
                break;
            case CreditCard::AmericanExpress: //AmericanExpress
                if (!preg_match('/^(3[47][0-9]{13})$/', $card_number)) {
                    return ErrorCode::errorParam('card number in ' . $card_type);
                }

                if (!preg_match('/^[0-9]{4}$/', $cvv2)) {
                    return ErrorCode::errorParam('cvv2 in error in AmericanExpress');
                }
                break;
            case CreditCard::DISCOVER: //DISCOVER
                if (!preg_match('/^(6(?:011|5[0-9]{2})[0-9]{12})$/', $card_number)) {
                    return ErrorCode::errorParam('card number in ' . $card_type);
                }
                if (!preg_match('/^[0-9]{3}$/', $cvv2)) {
                    return ErrorCode::errorParam('cvv2 in error in DISCOVER ');
                }
                break;
            default :
                return ErrorCode::errorParam('card_type');
        }

        if (floor($expire_month) != $expire_month ||
            $expire_month < 0 || $expire_month > 12
        ) {
           return ErrorCode::errorParam('expire_month');
        }
        if (!preg_match('/^20[0-9]{2}$/', $expire_year)) {
            return ErrorCode::errorParam('expire_year');
        }


        if (empty($last_name) || empty($first_name)) {
                return ErrorCode::errorParam('missing name');
        }
         return null;
    }

}
