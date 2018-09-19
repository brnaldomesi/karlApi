<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 2016/9/30
 * Time: 下午4:03
 */

namespace App\Method;


use App\Constants;
use App\ErrorCode;
use App\Model\Bill;
use App\Model\Booking;
use App\Model\BookingTransactionHistory;
use App\Model\Company;
use App\Model\CompanyPayMethod;
use App\Model\CompanySetting;
use App\Model\Customer;
use App\Model\Order;
use App\Model\RunningError;
use App\Model\StripeCustomer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use \App\Model\CreditCard;

class PaymentAlgorithm
{

    const PRE_CHARGE_REFUND = 1;
    const FINAL_CHARGE_REFUND = 1;

    /**
     * @param $booking_id
     * @param $company_id
     */
    private static $_instance;

    /**
     * PaymentAlgorithm constructor.
     */
    private function __construct()
    {
    }

    public static function getPayment()
    {
        if (self::$_instance == null) {
            self::$_instance = new PaymentAlgorithm();
        }
        return self::$_instance;
    }

    /**
     * @param $company_id
     * @param $client_id
     * @param $secret
     * @param $account
     * @param $pay_type
     * @param $active
     * @return mixed
     * @throws \Exception
     */
    public function addCompanyPayMethod(
        $company_id, $client_id,
        $secret, $account, $pay_type, $active
    )
    {
        $company = Company::where('id', $company_id)->count();
        if ($company <= 0) {
            throw new \Exception(ErrorCode::errorNotExist("company"));
        }
        if ($active != 1 && $active != 0) {
            $active = 0;
        }

        switch ($pay_type) {
//            case CompanyPayMethod::PAY_TYPE_PAY_PAL:
//                $payMethod = PayPalMethod::getPayPal()
//                    ->addPayPalMethod($company_id, $client_id, $secret, $account, $active);
//                break;
            case CompanyPayMethod::PAY_TYPE_STRIPE:
                if ($_SERVER["APP_PUB"] === "true" && !preg_match("/^sk_live_/", $secret)) {
                    throw new \Exception(ErrorCode::errorParam("secret"));
                }
                $payMethod = StripeMethod::getStrip()
                    ->addStripeMethod($company_id, $secret, $active);
                break;
            default:
                throw new \Exception(ErrorCode::errorParam("unknown pay type"));
        }
        if ($active == 1) {
            CompanyPayMethod::where('company_id', $company_id)
                ->where('id', '!=', $payMethod->id)
                ->update(['active' => CompanyPayMethod::NEGATIVE]);

            DB::delete("
                DELETE FROM stripe_customers WHERE customer_id IN (
    SELECT concat('customer_',customers.id) FROM customers LEFT JOIN users
      ON customers.user_id=users.id WHERE users.company_id=1);
                ");
        }
        return CompanyPayMethod::where('company_id', $company_id)->orderBy('active', 'desc')->get();
    }

//    public function addCustomerCreditCard($customerId, $card_type,
//                                          $card_number, $expire_month,
//                                          $expire_year, $cvv2, $first_name,
//                                          $last_name, $company_id, $address_zip,
//                                          $address_line1 = "",
//                                          $address_line2 = "",
//                                          $address_city = "",
//                                          $address_country = "")
    public function addCustomerCreditCard($customerId, $card_type,
                                          $card_number, $expire_month,
                                          $expire_year, $cvv2, $first_name,
                                          $last_name, $company_id, $address_zip)
    {
        $checkResult = CreditCard::checkCreditCardInfo($card_type,
            $card_number, $expire_month, $expire_year,
            $cvv2, $first_name, $last_name);

        if ($checkResult != null) {
            return $checkResult;
        }

        $paymentMethod = CompanyPayMethod::leftjoin("companies", "companies.id", "=", "company_pay_methods.company_id")
            ->leftjoin('card_zip_code_setting', "card_zip_code_setting.country_code", "=", "companies.country")
            ->where("company_pay_methods.company_id", $company_id)
            ->where("company_pay_methods.active", 1)
            ->select(
                "company_pay_methods.id",
                "company_pay_methods.secret",
                "companies.stripe_acct_id",
                DB::raw("ifnull(card_zip_code_setting.proving,1) as proving")
            )
            ->first();
        if (empty($paymentMethod)) {
            return ErrorCode::errorNotExist('company payment method');
        }
        $count = CreditCard::where('owner_id', $customerId)
            ->where('type', CreditCard::TYPE_CUSTOMER)
            ->where('pay_method_id', $paymentMethod->id)
            ->whereRaw('valid_until > now()')
            ->count();
        if ($count >= 10) {
            return ErrorCode::errorTooManyCreditCard();
        }
        if ($paymentMethod->proving == 1 && !PaymentMethod::checkCreditCardAbility(
                $card_number, $expire_month, $expire_year, $cvv2, $first_name . " " . $last_name, $address_zip)) {
            return ErrorCode::errorCreditCardCheck();
        }
        try {
//            switch ($paymentMethod->pay_type) {
//                case CompanyPayMethod::PAY_TYPE_PAY_PAL:
//                    $card = PayPalMethod::getPayPal()->transferCardInfoToToken(
//                        $customerId, $card_type,
//                        $card_number, $expire_month,
//                        $expire_year, $cvv2, $first_name,
//                        $last_name, $company_id,
//                        $address_line1,
//                        $address_line2,
//                        $address_city, $address_country, $address_zip,
//                        $paymentMethod->client_id, $paymentMethod->secret
//                    );
//                    break;
//                case CompanyPayMethod::PAY_TYPE_STRIPE:
            $card = StripeMethod::getStrip()->
            transferCardInfoToToken(
                $customerId, $card_type,
                $card_number, $expire_month,
                $expire_year, $cvv2,
                $first_name, $last_name,
                $address_zip, $paymentMethod->secret, $paymentMethod->proving == 1);
//                    break;
//                default :
//                    return ErrorCode::errorSelectPayMethodCantUse();
//            }
            $card['check_pass'] = CreditCard::CHECK_PASSED;
            $card['pay_method_id'] = $paymentMethod->id;
            CreditCard::create($card);
            $card['valid_until'] = strtotime($card['valid_until']);
            return ErrorCode::success($card);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function getCustomerCreditCards($customerId, $companyId)
    {
        $paymentMethod = CompanyPayMethod::leftjoin("companies", "companies.id", "=", "company_pay_methods.company_id")
            ->leftjoin("card_zip_code_setting", "card_zip_code_setting.country_code", "=", "companies.country")
            ->where("company_pay_methods.company_id", $companyId)
            ->where("company_pay_methods.active", 1)
            ->select(
                DB::raw("ifnull(card_zip_code_setting.proving,1) as proving"),
                "company_pay_methods.secret"
            )
            ->first();
        if (empty($paymentMethod)) {
            return ErrorCode::errorNotExist('company payment method');
        }
//
//        switch ($paymentMethod->pay_type) {
//            case CompanyPayMethod::PAY_TYPE_PAY_PAL:
//                return PayPalMethod::getPayPal()->getCustomerCreditCard(
//                    $customerId, $paymentMethod->client_id, $paymentMethod->secret
//                );
//            case CompanyPayMethod::PAY_TYPE_STRIPE:
        return StripeMethod::getStrip()->
        getCustomerCreditCard(
            $customerId, $paymentMethod->secret, 0
        );
//            default :
//                return ErrorCode::errorSelectPayMethodCantUse();
//        }
    }

    public function removeCustomerAllCard($companyId, $customerId)
    {
        $paymentMethods = CompanyPayMethod::where('company_id', $companyId)->get();
        foreach ($paymentMethods as $paymentMethod) {
            switch ($paymentMethod->pay_type) {
                case CompanyPayMethod::PAY_TYPE_PAY_PAL:
                    PayPalMethod::getPayPal()->deleteCustomerAllCards($customerId, $paymentMethod->client_id, $paymentMethod->secret);
                    break;
                case CompanyPayMethod::PAY_TYPE_STRIPE:
                    StripeMethod::getStrip()->
                    deleteCustomerAllCards(
                        $customerId, $paymentMethod->secret
                    );
                    break;
                default :
                    return ErrorCode::errorSelectPayMethodCantUse();
            }
        }
    }

    public function deleteCustomerCreditCard($customerId, $companyId, $cardToken)
    {
        $paymentMethod = CompanyPayMethod::where('company_id', $companyId)
            ->where('active', 1)
            ->first();
        if (empty($paymentMethod)) {
            return ErrorCode::errorNotExist('company payment method');
        }
        $result = \App\Model\CreditCard::where([
            ['owner_id', $customerId],
            ['type', \App\Model\CreditCard::TYPE_CUSTOMER],
            ['card_token', $cardToken],
        ])->first();
        if (empty($result)) {
            return ErrorCode::errorNotExist('card');
        }
        switch ($paymentMethod->pay_type) {
            case CompanyPayMethod::PAY_TYPE_PAY_PAL:
                $result = PayPalMethod::getPayPal()->deleteCustomerCreditCard(
                    $cardToken, $paymentMethod->client_id, $paymentMethod->secret
                );
                break;
            case CompanyPayMethod::PAY_TYPE_STRIPE:
                $result = StripeMethod::getStrip()->
                deleteCustomerCreditCard(
                    $customerId, $cardToken, $paymentMethod->secret
                );
                break;
            default :
                return ErrorCode::errorSelectPayMethodCantUse();
        }
        if (is_null($result)) {
            CreditCard::where("owner_id", $customerId)
                ->where("type", CreditCard::TYPE_CUSTOMER)
                ->where("card_token", $cardToken)
                ->delete();
            return ErrorCode::success("success");
        } else {
            return $result;
        }
    }

    public function bookingCharge($cost, $tva, $bookingId, $customerId, $cardToken, $companyId, $isAN, $coupon = "", $couponOff = 0)
    {


        $amount = round(($cost - $couponOff) * (1 + $tva / 100), 2);
//        $paymentMethod = CompanyPayMethod::leftjoin('company_settings', 'company_settings.company_id', '=', 'company_pay_methods.company_id')
//            ->leftjoin("companies","company_pay_methods.company_id","=",'companies.id')
//            ->where('company_pay_methods.company_id', $companyId)
//            ->where('company_pay_methods.active', 1)
//            ->select(
//                "companies.ccy",
//                'company_pay_methods.company_id',
//                'company_pay_methods.pay_type',
//                'company_pay_methods.client_id',
//                'company_pay_methods.secret',
//                'company_settings.pay_auth'
//            )
//            ->first();

        $stripeCustomer = StripeCustomer::where("customer_id", "customer_" . $customerId)->first();
        $paymentSetting = Booking::leftjoin("company_settings as own_com_set", "own_com_set.company_id", "=", "bookings.company_id")
            ->leftjoin("companies as own_com", "own_com.id", "=", "bookings.company_id")
            ->leftjoin("company_settings as exe_com_set", "exe_com_set.company_id", "=", "bookings.exe_com_id")
            ->leftjoin("companies as exe_com", "exe_com.id", "=", "bookings.exe_com_id")
            ->where("bookings.id", $bookingId)
            ->select(
                "own_com.stripe_acct_id",
                "own_com_set.pay_auth",
                "own_com.rate",
                "exe_com.ccy"
            )->first();
        if ($amount > 0 && $amount < 1) {
            $amount = 1;
        }
        if ($amount < 0) {
            $amount = 0;
        }
//        if (empty($paymentMethod)) {
//            return ErrorCode::errorNotExist('company payment method');
//        }


        try {
            $authId = '';
            $payType = $paymentSetting->pay_auth;
            Log::info($payType);
            if ($amount == 0) {
                $payPlatform = CompanyPayMethod::PAY_TYPE_FREE;
                $payId = "free_" . str_random(16);
            } else {
                if ($amount < Constants::MIN_COST) {
                    throw new \Exception(ErrorCode::errorLessAmountFault());
                }
                if ($isAN) {
                    $appFee = round($amount * (1 - Constants::OWN_COMPANY_TVA), 2);
                } else {
                    $appFee = round($amount * $paymentSetting->rate, 2);
                }
                $payPlatform = CompanyPayMethod::PAY_TYPE_STRIPE;//$paymentMethod->pay_type;

//                switch ($paymentMethod->pay_type) {
//                    case CompanyPayMethod::PAY_TYPE_PAY_PAL:
//                        try {
//                            if ($payType == CompanySetting::PAY_AUTH_ENABLE) {
//                                $payInfo = PayPalMethod::getPayPal()->makeAuthCharge($amount, $tva,
//                                    $cardToken, $paymentMethod->client_id, $paymentMethod->secret,
//                                $paymentMethod->ccy
//                                );
//                                $payId = $payInfo['payId'];
//                                $authId = $payInfo['authId'];
//                            } else {
//                                $payId = PayPalMethod::getPayPal()->makeCharge($amount, $tva,
//                                    $cardToken, $paymentMethod->client_id, $paymentMethod->secret,
//                                    $paymentMethod->ccy
//                                );
//                            }
//                        } catch (\Exception $ex) {
//                            throw new \Exception(ErrorCode::errorPayFailedWith(CompanyPayMethod::getPaymentMethod(CompanyPayMethod::PAY_TYPE_PAY_PAL)));
//                        }
//                        break;
//                    case CompanyPayMethod::PAY_TYPE_STRIPE:

                try {
                    if ($payType == CompanySetting::PAY_AUTH_ENABLE) {
                        $payId = StripeMethod::getStrip()->
                        makeAuthCharge(
                            $amount, $cardToken, $stripeCustomer->stripe_customer_id, $_SERVER['STRIP_S_KEY'],
                            $coupon, $couponOff, $paymentSetting->stripe_acct_id, $appFee,
                            $paymentSetting->ccy
                        );
                        $authId = $payId;
                    } else {
                        $payId = StripeMethod::getStrip()->
                        makeCharge(
                            $amount, $cardToken, $stripeCustomer->stripe_customer_id, $_SERVER['STRIP_S_KEY'],
                            $paymentSetting->stripe_acct_id,
                            $appFee, $coupon, $couponOff, $paymentSetting->ccy
                        );
                        $authId = '';
                    }
                } catch (\Exception $ex) {
                    throw new \Exception(ErrorCode::errorPayFailedWith(CompanyPayMethod::getPaymentMethod(CompanyPayMethod::PAY_TYPE_STRIPE)));
                }
//                        break;
//                    default :
//                        throw new \Exception(ErrorCode::errorSelectPayMethodCantUse());
//                }
            }
            BookingTransactionHistory::create([
                "booking_id" => $bookingId,
                "pay1_amount" => $amount,
                "pay1_id" => $payId,
                "auth_id" => $authId,
                "pay_auth" => $payType,
                "pay1_platform" => $payPlatform,
                "pay1_success" => BookingTransactionHistory::PAY_SUCCESS,
                "pay1_refund" => BookingTransactionHistory::NO_REFUND,
                "tva" => $tva,
                "ccy" => $paymentSetting->ccy
            ]);
            RunningError::recordRunningError(
                RunningError::STATE_SUCCESS,
                RunningError::TYPE_PAY,
                "booking {$bookingId} final pay success"
            );
            return null;
        } catch (\Exception $ex) {
            RunningError::recordRunningError(
                RunningError::STATE_FAULT,
                RunningError::TYPE_PAY,
                "booking {$bookingId} final payed fault. error info " . $ex
            );
            throw $ex;
        }
    }


    public function refundBooking($bookingId, $type, $amount, $companyId)
    {
        $bth = BookingTransactionHistory::where("booking_id", $bookingId)->first();
        if (empty($bth)) {
            return ErrorCode::errorNotExist("booking");
        }
        $chargeId = $type == self::PRE_CHARGE_REFUND ? $bth->pay1_id : $bth->pay2_id;
        $order = Order::leftjoin("bookings", "bookings.id", "=", "orders.booking_id")
            ->leftjoin("companies", "companies.id", "=", "bookings.company_id")
            ->where("orders.booking_id", $bookingId)
            ->select("companies.stripe_acct_id", "orders.order_state", "orders.trip_state")->first();
        \Log::info("order is " . json_encode($order));
        $paymentMethod = CompanyPayMethod::where("company_id", $companyId)->where("active", 1)->first();
        if (empty($paymentMethod)) {
            return ErrorCode::errorNotExist('company payment method');
        }
        try {
//            switch ($paymentMethod->pay_type) {
//                case CompanyPayMethod::PAY_TYPE_PAY_PAL:
//                    $refundId = PayPalMethod::getPayPal()->refund(
//                        $amount, $chargeId, $paymentMethod->client_id, $paymentMethod->secret
//                    );
//                    break;
//                case CompanyPayMethod::PAY_TYPE_STRIPE:
            $refundId = StripeMethod::getStrip()->
            refunds(
                $chargeId, $_SERVER['STRIP_S_KEY'], $order->stripe_acct_id,
                $order->order_state == Order::ORDER_STATE_BOOKING && $order->order_state == Order::TRIP_STATE_WAIT_TO_DEPARTURE
            );
//                    break;
//                default :
//                    return ErrorCode::errorSelectPayMethodCantUse();
//            }

            RunningError::recordRunningError(
                RunningError::STATE_SUCCESS,
                RunningError::TYPE_PAY_REFUND_PAY . $paymentMethod->pay_type,
                "booking {$bookingId} ,{$chargeId} refund success"
            );

            if ($type == self::PRE_CHARGE_REFUND) {
                $bth->pay1_refund_id = $refundId;
                $bth->pay1_refund_amount = $amount;
                $bth->pay1_refund_success = BookingTransactionHistory::REFUND_SUCCESS;
                $bth->pay1_refund = BookingTransactionHistory::REFUND;
            } else {
                $bth->pay2_refund_id = $refundId;
                $bth->pay2_refund_amount = $amount;
                $bth->pay2_refund_success = BookingTransactionHistory::REFUND_SUCCESS;
                $bth->pay2_refund = BookingTransactionHistory::REFUND;
            }
            $bth->save();
            return null;
        } catch (\Exception $ex) {
            Order::where("booking_id", $bookingId)->update(["order_state" => Order::ORDER_STATE_SETTLE_ERROR]);
            Log::error("refund info " . $ex);
            RunningError::recordRunningError(
                RunningError::STATE_FAULT,
                RunningError::TYPE_PAY_REFUND_PAY . $paymentMethod->pay_type,
                "booking {$bookingId},{$chargeId} refund fault " . $ex->getMessage()
            );
            if ($type == self::PRE_CHARGE_REFUND) {
                $bth->pay1_refund_amount = $amount;
                $bth->pay1_refund_success = BookingTransactionHistory::REFUND_FAULT;
                $bth->pay1_refund = BookingTransactionHistory::REFUND;
            } else {
                $bth->pay2_refund_amount = $amount;
                $bth->pay2_refund_success = BookingTransactionHistory::REFUND_FAULT;
                $bth->pay2_refund = BookingTransactionHistory::REFUND;
            }
            $bth->save();
            return ErrorCode::errorRefundFault(CompanyPayMethod::getPaymentMethod($paymentMethod->pay_type));
        }
    }

    /**
     * @param $bookingId
     * @param $companyId
     * @return Bill
     */
    public function tripFinalSettle($bookingId, $companyId)
    {
//        $paymentMethod = CompanyPayMethod::where("company_id", $companyId)->where("active", 1)->first();
//        if (empty($paymentMethod)) {
//            RunningError::recordRunningError(
//                RunningError::TYPE_COMPANY_PAY_INFO,
//                RunningError::STATE_FAULT,
//                "company " . $companyId . " get payment info error"
//            );
//            Log::error('error there is no companies payment info when settle booking ' . $bookingId . ' at ' . date('Y-m-d H:i:s', time()));
//            return;
//        }
        $booking = BookingTransactionHistory::
        leftjoin('bookings', 'bookings.id', '=', 'booking_transaction_histories.booking_id')
            ->leftjoin('companies as own_com', 'own_com.id', '=', 'bookings.company_id')
            ->leftjoin('companies as exe_com', 'exe_com.id', '=', 'bookings.exe_com_id')
            ->leftjoin('orders', 'orders.booking_id', '=', 'bookings.id')
            ->select('bookings.total_cost',          //booking 预计总花费
                'bookings.base_cost',                //booking 基础花费
                'bookings.option_cost',              //booking option 花费
                'booking_transaction_histories.pay1_amount',  // 第一笔支付  (base+option)
                'bookings.coupon_off',
                'orders.actual_fee',                           // 实际trip 花费
                'bookings.tva',
                'booking_transaction_histories.pay1_id',
                'booking_transaction_histories.auth_id',
                'booking_transaction_histories.ccy',
                'booking_transaction_histories.pay_auth',
                'bookings.card_token',
                'bookings.company_id',
                'bookings.exe_com_id',
                'bookings.customer_id',
                DB::raw("if(bookings.company_id=bookings.exe_com_id,own_com.rate,exe_com.rate) as rate")
            )
            ->where('booking_transaction_histories.booking_id', $bookingId)
            ->where('orders.trip_state', Order::TRIP_STATE_SETTLING)
            ->first();
        Log::info("booking is " . $booking);
        $couponOff = $booking->coupon_off;
        $estimateCost = $booking->total_cost;
        $actualCost = $booking->actual_fee - round($couponOff * (1 + $booking->tva / 100), 2);
//        echo "act fee is ".$booking->actual_fee ." coupon off is ".(round($couponOff*(1+$booking->tva/100),2))."actrul fee ".$actualCost;
        $prePayMoney = $booking->pay1_amount;
        if ($estimateCost == $prePayMoney) {
            $refundAmount = 0.00;
            $secondAmount = $actualCost - $estimateCost;
        } elseif ($estimateCost > $prePayMoney) {
            $refundAmount = 0.00;
            $secondAmount = $actualCost - $prePayMoney;
        } else {
            if ($actualCost <= $prePayMoney) {
                $refundAmount = $prePayMoney - $actualCost;
                $secondAmount = 0.00;
            } else {
                $refundAmount = 0.00;
                $secondAmount = $actualCost - $prePayMoney;
            }
        }

        if ($booking->pay_auth == CompanySetting::PAY_AUTH_ENABLE) {
            //capture
            $this->captureAuth($bookingId, $booking->tva, $booking->card_token, $booking->auth_id, $booking->pay1_amount,
                $companyId);
//        } else {
//            if ($refundAmount > 0) {
//                $this->refundBooking($bookingId, self::PRE_CHARGE_REFUND, $refundAmount, $companyId);
//            }
        }
        if ($secondAmount > 0) {
            $this->finalSettle($secondAmount, $booking->tva, $bookingId, $booking->customer_id, $booking->card_token, $companyId);
        }
        //平台根据公司不同抽取平台比例，
        $platFee = round($actualCost * $booking->rate, 2);
        //剩余
        //an
        if ($booking->company_id != $booking->exe_com_id) {
            $income = round($actualCost * Constants::OWN_COMPANY_TVA, 2);
            $anFee = $actualCost - $income - $platFee;
        } else {
            $anFee = 0.00;
            $income = $actualCost - $platFee;
        }
        return Bill::create([
            "booking_id" => $bookingId,
            "ccy" => $booking->ccy,
            "own_com_id" => $booking->company_id,
            "exe_com_id" => $booking->exe_com_id,
            "order_actual_fee" => $actualCost,
            "settle_fee" => $actualCost,
            "com_income" => $income,
            "platform_income" => $platFee,
            "an_fee" => $anFee,
            "settle_time" => MethodAlgorithm::formatTimestampToDate(time())
        ]);
    }

    public function checkCreditCardTokenAvailability($card_token, $customerId, $company_id)
    {
        $paymentInfo = CompanyPayMethod::leftjoin("companies", "companies.id", "=", "company_pay_methods.company_id")
            ->leftjoin("card_zip_code_setting", "card_zip_code_setting.country_code", "=", "companies.country")
            ->where("company_pay_methods.company_id", $company_id)
            ->where("company_pay_methods.active", 1)
            ->select(
                DB::raw("ifnull(card_zip_code_setting.proving,1) as proving"),
                "company_pay_methods.secret"
            )
            ->first();
        if (empty($paymentInfo)) {
            throw new \Exception(ErrorCode::errorSelectPayMethodCantUse());
        }
//        switch ($paymentInfo->pay_type) {
//            case CompanyPayMethod::PAY_TYPE_PAY_PAL:
//                return PayPalMethod::getPayPal()->checkPayPalCreditCardTokenAvailability($card_token, $paymentInfo->client_id, $paymentInfo->secret);
//            case CompanyPayMethod::PAY_TYPE_CHASE:
//                return null;
//            case CompanyPayMethod::PAY_TYPE_STRIPE:
        return StripeMethod::getStrip()->checkStripeCreditCardTokenAvailability($card_token, $customerId, $paymentInfo->secret, $paymentInfo->proving == 1);
//            default:
//                return null;
//        }
    }


    private function finalSettle($amount, $tva, $bookingId, $customerId, $cardToken, $companyId)
    {
//        $paymentMethod = CompanyPayMethod::where('company_id', $companyId)
//            ->where('active', 1)
//            ->first();
//        if (empty($paymentMethod)) {
//            return ErrorCode::errorNotExist('company payment method');
//        }
        $stripeCustomer = StripeCustomer::where("customer_id", "customer_" . $customerId)->first();
        $company = Company::where("id", $companyId)->select("rate", "stripe_acct_id")->first();
        $anCount = Booking::where("id", $bookingId)->where("company_id", $companyId)->where("exe_com_id", $companyId)->count();
        if ($anCount == 0) {
            $appFee = round($amount * (Constants::PLATFORM_SETTLE_TVA + Constants::EXE_COMPANY_TVA), 2);
        } else {
            $appFee = round($amount * $company->rate, 2);
        }
        $secret = $_SERVER['STRIP_S_KEY'];
        $bth = BookingTransactionHistory::where("booking_id", $bookingId)
            ->first();
        $bth->pay2_amount = $amount;
        $bth->pay2_platform = CompanyPayMethod::PAY_TYPE_STRIPE;
        $bth->pay2_success = BookingTransactionHistory::PAY_FAULT;
        $bth->save();


        try {
//            switch ($paymentMethod->pay_type) {
//                case CompanyPayMethod::PAY_TYPE_PAY_PAL:
//                    $payId = PayPalMethod::getPayPal()->makeCharge($amount, $tva,
//                        $cardToken, $paymentMethod->client_id, $paymentMethod->secret,
//                    $bth->ccy
//                    );
//                    break;
//                case CompanyPayMethod::PAY_TYPE_STRIPE:
            $payId = StripeMethod::getStrip()->
            makeCharge(
                $amount, $cardToken, $stripeCustomer->stripe_customer_id, $secret, $company->stripe_acct_id, $appFee, "", 0,
                $bth->ccy
            );
//                    break;
//                default :
//                    return ErrorCode::errorSelectPayMethodCantUse();
//            }
            $bth->pay2_id = $payId;
            $bth->pay2_success = BookingTransactionHistory::PAY_SUCCESS;
            $bth->save();
            return null;
        } catch (\Exception $ex) {
            Order::where("booking_id", $bookingId)->update(["order_state" => Order::ORDER_STATE_SETTLE_ERROR]);
            Log::error("final pay " . $ex);
            RunningError::recordRunningError(
                RunningError::STATE_FAULT,
                RunningError::TYPE_PAY,
                "booking {$bookingId} final payed fault. error info " . $ex
            );
            throw new \Exception(ErrorCode::errorPayFailedWith(CompanyPayMethod::getPaymentMethod(CompanyPayMethod::PAY_TYPE_STRIPE)));
        }
    }


    private function captureAuth($bookingId, $tva, $cardToken, $authId, $payAmount, $companyId)
    {
        $bth = BookingTransactionHistory::where("booking_id", $bookingId)
            ->first();
        $stripeCustomer = StripeCustomer::leftjoin("bookings", DB::raw("concat('customer_',bookings.customer_id)"), "=", "stripe_customers.customer_id")
            ->where('bookings.id', $bookingId)
            ->select("stripe_customers.stripe_customer_id")->first();
        $company = Company::where("id", $companyId)->select("rate", "stripe_acct_id")->first();
        $anCount = Booking::where("id", $bookingId)->where("company_id", $companyId)->where("exe_com_id", $companyId)->count();
        if ($anCount == 0) {
            $appFee = round($payAmount * (Constants::PLATFORM_SETTLE_TVA + Constants::EXE_COMPANY_TVA), 2);
        } else {
            $appFee = round($payAmount * $company->rate, 2);
        }
        try {
//            switch ($paymentMethod->pay_type) {
//                case CompanyPayMethod::PAY_TYPE_PAY_PAL:
//                    $repayId = PayPalMethod::getPayPal()->captureAuth($authId, $payAmount,
//                        $paymentMethod->client_id, $paymentMethod->secret);
//                    break;
//                case CompanyPayMethod::PAY_TYPE_STRIPE:
            $repayId = StripeMethod::getStrip()->captureAuth($authId, $payAmount, $_SERVER['STRIP_S_KEY'], $company->stripe_acct_id);
//                    break;
//                default :
//                    return ErrorCode::errorSelectPayMethodCantUse();
//            }
            $bth->capture_id = $repayId;
            $bth->capture_amount = $payAmount;
            $bth->capture_success = BookingTransactionHistory::PAY_SUCCESS;
            $bth->save();
        } catch (\Exception $ex) {
            Order::where("booking_id", $bookingId)->update(["order_state" => Order::ORDER_STATE_SETTLE_ERROR]);
            Log::error("final pay " . $ex);
            RunningError::recordRunningError(
                RunningError::STATE_FAULT,
                RunningError::TYPE_PAY,
                "booking {$bookingId} final payed fault. error info " . $ex
            );
            try {
//                switch ($paymentMethod->pay_type) {
//                    case CompanyPayMethod::PAY_TYPE_PAY_PAL:
//                        $repayId = PayPalMethod::getPayPal()->makeAuthCharge($payAmount, $tva, $cardToken,
//                            $paymentMethod->client_id, $paymentMethod->secret,$bth->ccy);
//                        break;
//                    case CompanyPayMethod::PAY_TYPE_STRIPE:
                $repayId = StripeMethod::getStrip()->makeCharge($payAmount, $cardToken, $stripeCustomer->stripe_customer_id, $_SERVER['STRIP_S_KEY'], $company->stripe_acct_id, $appFee, "", 0, $bth->ccy);
//                        break;
//                    default :
//                        return ErrorCode::errorSelectPayMethodCantUse();
//                }
                $bth->repay1_id = $repayId;
                $bth->repay1_amount = $payAmount;
                $bth->repay1_success = BookingTransactionHistory::PAY_SUCCESS;
                $bth->save();
            } catch (\Exception $ex) {
                $bth->repay1_amount = $payAmount;
                $bth->repay1_success = BookingTransactionHistory::PAY_FAULT;
                $bth->save();

                Log::error("", $ex);
                throw new \Exception(ErrorCode::errorPayFailedWith(CompanyPayMethod::getPaymentMethod(CompanyPayMethod::PAY_TYPE_STRIPE)));
            }
        }
    }

    public function getPayMethodExistCustomer(CompanyPayMethod $payInfo, $customerId)
    {
        try {
            switch ($payInfo->pay_type) {
                case CompanyPayMethod::PAY_TYPE_STRIPE:
                    $customer = Customer::leftjoin("users", "users.id", "=", "customers.user_id")
                        ->leftjoin("stripe_customers", DB::raw("concat('customer_',customers.id)"), "=", "stripe_customers.customer_id")
                        ->select(
                            "users.first_name",
                            "users.last_name"
                        )
                        ->where("users.company_id", $payInfo->company_id)
                        ->where("stripe_customers.stripe_customer_id", $customerId)
                        ->first();
                    $stripeCustomer = StripeMethod::getStrip()->getCustomerInfo($payInfo->secret, $customerId);
                    if (!empty($customer) && !empty($stripeCustomer)) {
                        $stripeCustomer->exist_customer = $customer;
                    }
                    return $stripeCustomer;
                default:
                    return null;
            }
        } catch (\Exception $ex) {
            return null;
        }
    }

    public function syncPayMethodCustomerCreditCard(CompanyPayMethod $payInfo, $customerId, $scId)
    {
        try {
            switch ($payInfo->pay_type) {
                case CompanyPayMethod::PAY_TYPE_STRIPE:
                    $cardList = StripeMethod::getStrip()->checkCustomerCreditCard($scId, $payInfo->secret, $payInfo->proving == 1);
                    if (count($cardList) > 0) {
                        foreach ($cardList as $card) {
                            $card['owner_id'] = $customerId;
                            $card['type'] = CreditCard::TYPE_CUSTOMER;
                            $card['pay_method_id'] = $payInfo->id;

                            CreditCard::create($card);
                        }
                    }
                    break;
                default:
                    break;
            }
        } catch (\Exception $ex) {
        }
    }

    /**
     * @param CompanyPayMethod $payInfo
     * @param $coupon
     * @return mixed
     */
    public function getCouponInfo(CompanyPayMethod $payInfo, $coupon)
    {
        try {
            switch ($payInfo->pay_type) {
                case CompanyPayMethod::PAY_TYPE_STRIPE :
                    return StripeMethod::getStrip()->checkCouponKey($coupon, $payInfo->secret);
                default:
                    return null;
            }

        } catch (\Exception $ex) {
            return null;
        }
    }
}