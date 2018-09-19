<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 2016/10/21
 * Time: 下午3:19
 */

namespace App\Method;


use App\ErrorCode;
use App\Model\CompanyPayMethod;
use App\Model\CreditCard;
use App\Model\StripeCustomer;
use Illuminate\Support\Facades\Log;
use Stripe\Charge;
use Stripe\Coupon;
use Stripe\Customer;
use Stripe\Refund;
use Stripe\Stripe;
use Stripe\Token;

class StripeMethod
{

    private static $_instance = null;
    /**
     * StripeMethod constructor.
     */
    private $payActive;

    private function __construct()
    {
        $this->payActive = PaymentMethod::isPayActive();
    }

    public static function getStrip()
    {
        if (self::$_instance == null) {
            self::$_instance = new StripeMethod();
        }
        return self::$_instance;
    }

//    public function transferCardInfoToToken($owner_id, $card_type,
//                                            $card_number, $expire_month,
//                                            $expire_year, $cvv2, $first_name,
//                                            $last_name,
//                                            $address_line1,
//                                            $address_line2,
//                                            $address_city, $address_country, $address_zip,
//                                            $secret, $proving)
    public function transferCardInfoToToken($owner_id, $card_type,
                                            $card_number, $expire_month,
                                            $expire_year, $cvv2,
                                            $first_name, $last_name,
                                            $address_zip, $secret, $proving)
    {
        try {
            $stripeCustomerId = $this->getStripeCustomer($owner_id, $secret);
            Stripe::setApiKey($secret);

            if ($proving) {
                $cardInfo = [
                    "number" => $card_number,
                    "exp_month" => $expire_month,
                    "exp_year" => $expire_year,
                    "cvc" => $cvv2,
                    "name" => $first_name . " " . $last_name
                ];
            } else {
                $cardInfo = [
                    "number" => $card_number,
                    "exp_month" => $expire_month,
                    "exp_year" => $expire_year,
                    "cvc" => $cvv2,
                    "name" => $first_name . " " . $last_name
                ];
            }

            $card = Token::create([
                "card" => $cardInfo
            ]);

            $sc = Customer::retrieve($stripeCustomerId);
            $sc->sources->create(["source" => $card->id]);
            $sc->save();
            \Log::info(json_encode($sc));
        } catch (\Exception $ex) {
            Log::error("stripe " . $ex);
            throw new \Exception(ErrorCode::errorCreateCreditCard("Stripe "));
        }

        return [
            'owner_id' => $owner_id,
            'type' => \App\Model\CreditCard::TYPE_CUSTOMER,
            'card_number' => "xxxxxxxxxxxx" . $card->card->last4,
            'card_type' => $card_type,
            'card_token' => $card->card->id,
            'valid_until' => $this->spellExpireTime($expire_year, $expire_month)
        ];
    }

    public function addStripeMethod($company_id, $secret, $active)
    {
        if (is_null($secret)) {
            throw new \Exception(ErrorCode::errorMissingParam());
        }
        if (empty($secret)) {
            throw new \Exception(ErrorCode::errorParam(''));
        }
        $method = CompanyPayMethod::create([
            'pay_type' => CompanyPayMethod::PAY_TYPE_STRIPE,
            'client_id' => "",
            'secret' => $secret,
            'account' => "",
            'active' => $active,
            'company_id' => $company_id,
        ]);
        return $method;
    }

    private function getStripeCustomer($customerId, $secret)
    {
        $stripe = StripeCustomer::where('customer_id', 'customer_' . $customerId)->first();
        if (empty($stripe)) {
            Stripe::setApiKey($secret);
            $customer = Customer::create([
                "description" => "customer_" . $customerId
            ]);
            $stripe = StripeCustomer::create([
                "customer_id" => "customer_" . $customerId,
                "stripe_customer_id" => $customer->id
            ]);
        }
        return $stripe->stripe_customer_id;
    }


    public function getCustomerCreditCard($customerId, $secret, $checkZip)
    {
        $checkZip = 0;
        $stripe = StripeCustomer::where('customer_id', 'customer_' . $customerId)->first();
        if (empty($stripe)) {
            return ErrorCode::successEmptyResult("this customer has no credit card");
        }
        $creditCards = $this->checkCustomerCreditCard($stripe->stripe_customer_id, $secret, $checkZip);
        if (count($creditCards) > 0) {
            return ErrorCode::success($creditCards);
        } else {
            return ErrorCode::successEmptyResult("this customer has no cards");
        }
    }

    public function checkCustomerCreditCard($stripe_customer_id, $secret, $checkZip)
    {
        try {
            $checkZip = 0;
            Stripe::setApiKey($secret);
            $cards = Customer::retrieve($stripe_customer_id)->sources->all(["object" => "card"]);
            $creditCards = array();
            foreach ($cards->data as $card) {
                $creditCard = array();
                $name = empty($card->name) ? [0 => "", 1 => ""] : explode(" ", $card->name);
                $creditCard['card_token'] = $card->id;
                $creditCard['card_number'] = "xxxxxxxxxxxx" . $card->last4;
                $creditCard['expire_month'] = $card->exp_month;
                $creditCard['expire_year'] = $card->exp_year;
                $creditCard['first_name'] = isset($name[0]) ? $name[0] : "";
                $creditCard['last_name'] = isset($name[1]) ? $name[1] : "";
                $creditCard['card_type'] = PaymentMethod::getCardTypeNumber($card->brand);
                $creditCard['valid_until'] = $this->spellExpireTime($card->exp_year, $card->exp_month);
                $creditCard['address_line_1'] = $card->address_line1;
                $creditCard['address_line_2'] = $card->address_line2;
                $creditCard['address_city'] = $card->address_city;
                $creditCard['address_state'] = $card->address_state;
                $creditCard['address_country_code'] = $card->address_country; 
                if ($checkZip) {
                    if ( 
                        $card->cvc_check == "pass"
                    ) {
                        $creditCard['check_pass'] = CreditCard::CHECK_PASSED;
                    } else {
                        $creditCard['check_pass'] = CreditCard::CHECK_FAILED;
                    }
                } else {
                    $creditCard['check_pass'] = CreditCard::CHECK_PASSED;
                }
                array_push($creditCards, $creditCard);
            }
            return $creditCards;
        } catch (\Exception $ex) {
            return [];
        }
    }

    private function spellExpireTime($exp_year, $exp_month)
    {
        $startTimeDate = date_create($exp_year . "-" . $exp_month);
        date_add($startTimeDate, date_interval_create_from_date_string("1 month"));
        return date_format($startTimeDate, 'Y-m-d H:i:s');
    }

    public function deleteCustomerCreditCard($customerId, $cardToken, $secret)
    {
        \Stripe\Stripe::setApiKey($secret);
        $stripeCustomer = StripeCustomer::where('customer_id', 'customer_' . $customerId)->first();
        try {
            \Stripe\Customer::retrieve($stripeCustomer->stripe_customer_id)
                ->sources->retrieve($cardToken)->delete();
            return null;
        } catch (\Exception $ex) {
            Log::error("stripe " . $ex);
            return ErrorCode::errorNotExist("card");
        }
    }


    public function makeCharge($amount, $cardToken, $stripeCustomerId, $secret, $sAId, $appFee, $coupon = "", $couponOff = 0, $currency = "usd")
    {
        
        $totalPrice = $amount * 100;
        Stripe::setApiKey($secret);
        try {

            $chargeInfo = [
                "amount" => $totalPrice,
                "currency" => $currency,
                "source" => $cardToken,
                "customer" => $stripeCustomerId,
                "application_fee" => $appFee * 100,
                "metadata" => [
                    "coupon" => $coupon,
                    "coupon_off" => $couponOff,
                ]
            ];
            \Log::info("charge info is " . json_encode($chargeInfo));
            \Log::info("stripe id is " . $sAId);
            $charge = Charge::create($chargeInfo, ["stripe_account" => $sAId]);
            return $charge->id;
        } catch (\Exception $ex) {
            Log::error("stripe " . $ex);
            throw new \Exception(ErrorCode::errorPayFailedWith("Stripe"));
        }
    }

    public function refunds($chargeId, $secret, $sAId, $refundAppFee)
    {
        Stripe::setApiKey($secret);
        $refund = Refund::create(["charge" => $chargeId,
            "refund_application_fee" => $refundAppFee
        ], ["stripe_account" => $sAId]);
        return $refund->id;
    }

    public function checkStripeCreditCardTokenAvailability($cardToken, $customerId, $secret, $checkZip)
    {
        $checkZip = 0;
        Stripe::setApiKey($secret);
        $stripe = StripeCustomer::where('customer_id', 'customer_' . $customerId)->first();
        if (empty($stripe)) {
            return null;
        }
        try {
            $card = Customer::retrieve($stripe->stripe_customer_id)->sources->retrieve($cardToken);
            $creditCard = array();
            $name = empty($card->name) ? [0 => "", 1 => ""] : explode(" ", $card->name);
            $creditCard['card_token'] = $card->id;
            $creditCard['card_number'] = "xxxxxxxxxxxx" . $card->last4;
            $creditCard['expire_month'] = $card->exp_month;
            $creditCard['expire_year'] = $card->exp_year;
            $creditCard['first_name'] = $name[0];
            $creditCard['last_name'] = $name[1];
            $creditCard['card_type'] = PaymentMethod::getCardTypeNumber($card->brand);
            $creditCard['valid_until'] = strtotime($this->spellExpireTime($card->exp_year, $card->exp_month));
            if ($checkZip) {
                if ( 
                    $card->cvc_check == "pass"
                ) {
                    $creditCard['address_line_1'] = $card->address_line1;
                    $creditCard['address_line_2'] = $card->address_line2;
                    $creditCard['address_city'] = $card->address_city;
                    $creditCard['address_state'] = $card->address_state;
                    $creditCard['address_country_code'] = $card->address_country; 
                } else {
                    throw new \Exception(ErrorCode::errorParam('card not check'));
                }
            } else {
                $creditCard['address_line_1'] = $card->address_line1;
                $creditCard['address_line_2'] = $card->address_line2;
                $creditCard['address_city'] = $card->address_city;
                $creditCard['address_state'] = $card->address_state;
                $creditCard['address_country_code'] = $card->address_country; 
            }

            return $creditCard;
        } catch (\Exception $ex) {
            Log::error("stripe " . $ex);
            return null;
        }
    }


    public function deleteCustomerAllCards($customerId, $secret)
    {
        $stripe = StripeCustomer::where('customer_id', 'customer_' . $customerId)->first();
        if (empty($stripe)) {
            return ErrorCode::successEmptyResult("this customer has no credit card");
        }
        try {
            Stripe::setApiKey($secret);
            $cards = Customer::retrieve($stripe->stripe_customer_id)->sources->all(["object" => "card"]);
            foreach ($cards as $card) {
                try {
                    $card->delete();
                } catch (\Exception $ex) {
                    Log::error("stripe delete card fault", $card);
                }
            }
        } catch (\Exception $e) {
            Log::error("strip error " . $e);
        }
    }


    public function makeAuthCharge($amount, $cardToken, $stripeCustomerId, $secret, $coupon, $couponOff, $sAId, $appFee, $currency = "usd")
    {
        $totalPrice = $amount * 100;
        Stripe::setApiKey($secret);
        try {
            $chargeInfo = [
                "amount" => $totalPrice,
                "currency" => $currency,
                "source" => $cardToken,
                "capture" => false,
                "customer" => $stripeCustomerId,
                "application_fee" => $appFee * 100,
                "metadata" => [
                    "coupon" => $coupon,
                    "coupon_off" => $couponOff,
                ]
            ];
            $charge = Charge::create($chargeInfo, ["stripe_account" => $sAId]);
            return $charge->id;
        } catch (\Exception $ex) {
            Log::error("stripe " . $ex);
            throw new \Exception(ErrorCode::errorPayFailedWith("Stripe"));
        }
    }

    public function captureAuth($authId, $payAmount, $secret, $sAid)
    {
        Stripe::setApiKey($secret);
//        $payAmount = $payAmount * 100;
        try {
            $ch = Charge::retrieve($authId, ["stripe_account" => $sAid]);
//            $ch->amount = $payAmount;
            $ch->capture();
            return $authId;
        } catch (\Exception $ex) {
            Log::error("stripe " . $ex);
            throw new \Exception(ErrorCode::errorPayFailedWith("Stripe"));
        }
    }


    public function checkCouponKey($couponCode, $secret)
    {
        Stripe::setApiKey($secret);
        try {
            $coupon = Coupon::retrieve($couponCode);
            if ($coupon->valid) {
                $coupon->amount_off = $coupon->amount_off / 100;
                return $coupon;
            } else {
                return null;
            }
        } catch (\Exception $ex) {
            Log::info('error get coupon \n' . $ex);
            return null;
        }
    }

    /**
     * @param $secret
     * @return mixed|null
     */
    public function getCustomerInfo($secret, $scId)
    {
        Stripe::setApiKey($secret);
        try {
            $customer = Customer::retrieve($scId);
            return $customer;
        } catch (\Exception $ex) {
            return null;
        }
    }

}
