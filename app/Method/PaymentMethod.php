<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 2016/9/26
 * Time: 下午3:42
 */

namespace App\Method;


use App\Constants;
use App\Model\CompanySetting;
use App\Model\CreditCard;
use App\Model\Offer;
use Stripe\Customer;
use Stripe\Stripe;
use Stripe\Token;

class PaymentMethod
{

    public static function isPayActive()
    {
        return $_SERVER['PAY_ACTIVE'] === 'false' ? false : true;
    }

    public static function offerPriceSettlement($min_cost, $calc_method,
                                                $duration, $distance, $price,
                                                $unit, $companyUnit,
                                                $d_is_port = 0,
                                                $d_port_price = 0,
                                                $a_is_port = 0,
                                                $a_port_price = 0
    )
    {
        $basic_cost = 0.00;
        if (is_numeric($price)) {
            switch ($calc_method) {
                case 0:
                    $basic_cost = $min_cost;
                    break;
                case 1:
                    $basic_cost = round($distance * $price, 2);
                    break;
                case 2:
                    $basic_cost = round($duration * $price, 2);
                    break;
                case 3:
                    $basic_cost = round(floatval($price), 2);
                    break;
            }
        } else {
            $prices = json_decode($price, true);
            switch ($calc_method) {
                case 0:
                    $basic_cost = $min_cost;
                    break;
                case 1:
                    $prices = MethodAlgorithm::sortPrices($prices);
                    $basic_cost = -1;
                    foreach ($prices as $item) {
                        if ($unit == $companyUnit) {
                            if ($item['invl_start'] <= $distance && $item['invl_end'] > $distance) {
                                $basic_cost = round($distance * $item['price'], 2);
                                break;
                            }
                        } else {
                            if ($unit == CompanySetting::UNIT_MI &&
                                $companyUnit == CompanySetting::UNIT_KM
                            ) {
                                if ($item['invl_start'] <= $distance * Constants::MI_2_KM
                                    && $item['invl_end'] > $distance * Constants::MI_2_KM
                                ) {
                                    $basic_cost = round($distance * Constants::MI_2_KM * $item['price'], 2);
                                    break;
                                }
                            } else {
                                if ($item['invl_start'] <= $distance * Constants::KM_2_MI
                                    && $item['invl_end'] > $distance * Constants::KM_2_MI
                                ) {
                                    $basic_cost = round($distance * Constants::KM_2_MI * $item['price'], 2);
                                    break;
                                }
                            }
                        }

                    }
                    if ($basic_cost == -1) {
                        if ($unit == $companyUnit) {
                            if ($distance >= array_last($prices)['invl_end']) {
                                $basic_cost = round($distance * array_last($prices)['price'], 2);
                            }
                            if ($distance < array_first($prices)['invl_start']) {
                                $basic_cost = round($distance * array_first($prices)['price'], 2);
                            }
                        } else {

                            if ($unit == CompanySetting::UNIT_MI &&
                                $companyUnit == CompanySetting::UNIT_KM
                            ) {
                                if ($distance * Constants::MI_2_KM >= array_last($prices)['invl_end']) {
                                    $basic_cost = round($distance * Constants::MI_2_KM * array_last($prices)['price'], 2);
                                }
                                if ($distance * Constants::MI_2_KM < array_first($prices)['invl_start']) {
                                    $basic_cost = round($distance * Constants::MI_2_KM * array_first($prices)['price'], 2);
                                }
                            } else {
                                if ($distance * Constants::KM_2_MI >= array_last($prices)['invl_end']) {
                                    $basic_cost = round($distance * Constants::KM_2_MI * array_last($prices)['price'], 2);
                                }
                                if ($distance * Constants::KM_2_MI < array_first($prices)['invl_start']) {
                                    $basic_cost = round($distance * Constants::KM_2_MI * array_first($prices)['price'], 2);
                                }
                            }
                        }


                    }
                    break;
                case 2:
                    $prices = MethodAlgorithm::sortPrices($prices);
                    $basic_cost = -1;
                    foreach ($prices as $item) {
                        if ($item['invl_start'] <= $duration && $item['invl_end'] >= $duration) {
                            $basic_cost = round($duration * $item['price'], 2);
                            break;
                        }
                    }
                    if ($basic_cost == -1) {
                        if ($duration >= array_last($prices)['invl_end']) {
                            $basic_cost = round($duration * array_last($prices)['price'], 2);
                        }
                        if ($duration < array_first($prices)['invl_start']) {
                            $basic_cost = round($duration * array_first($prices)['price'], 2);
                        }
                    }
                    break;
                case 3:
                    $basic_cost = round(floatval($price), 2);
                    break;
            }

        }
        $basic_cost = $basic_cost > $min_cost ? $basic_cost : $min_cost;

        if ($d_is_port == Offer::IS_AIRPORT) {
            $basic_cost = $basic_cost + $d_port_price;
        }
        if ($a_is_port == Offer::IS_AIRPORT) {
            $basic_cost = $basic_cost + $a_port_price;
        }

        return $basic_cost;
    }


    public static function getCardTypeNumber($cardShort)
    {
        switch (strtolower($cardShort)) {
            case 'visa':
                return CreditCard::VISA;
            case 'mastercard':
                return CreditCard::MasterCard;
            case 'amex':
                return CreditCard::AmericanExpress;
            case "american express":
                return CreditCard::AmericanExpress;
            case 'discover':
                return CreditCard::DISCOVER;
        }
    }

    public static function ccyCvt($from, $to, $amount)
    {
        if ($from == $to) {
            return $amount;
        }

        $result = \DB::select(
            "select round(rate*{$amount}/100,2) as a from ccy_cvt_records WHERE `from`='{$from}' and `to`='{$to}' ORDER BY created_at desc LIMIT 1;"
        );

        return $result[0]->a;
    }

//    public static function checkCreditCardAbility(
//        $number, $exp_month, $exp_year,
//        $cvc, $name,
//        $address_line1,
//        $address_line2,
//        $address_city, $address_country, $address_zip
//    )
    public static function checkCreditCardAbility($number, $exp_month, $exp_year, $cvc, $name, $address_zip)
    {
        try {
            if (!is_null($address_zip)) {
                $cardInfo = ["number" => $number,
                    "exp_month" => $exp_month,
                    "exp_year" => $exp_year,
                    "cvc" => $cvc,
                    "name" => $name];
            } else {
                $cardInfo = ["number" => $number,
                    "exp_month" => $exp_month,
                    "exp_year" => $exp_year,
                    "cvc" => $cvc,
                    "name" => $name
                ];
            }

            Stripe::setApiKey($_SERVER['BILLING_CHECK_KEY']);
            $card = Token::create(["card" => $cardInfo]);
            $sc = Customer::retrieve($_SERVER['BILLING_CHECK_CUS']);
            $sc->sources->create(["source" => $card->id]);
            $sc->save();
            $card = Customer::retrieve($_SERVER['BILLING_CHECK_CUS'])->sources->retrieve($card->card->id);
            if (!is_null($address_zip)) {
                $result = $card->cvc_check == "pass";
            } else {
                $result = $card->cvc_check == "pass";
            }
            $card->delete();
            return $result;
        } catch (\Exception $ex) {
            \Log::info("**** **** **** " . substr($number, -4) . 'get failed ' . json_encode($ex));
            return false;
        }
    }

    /**
     * @param $address
     * @return mixed|null
     */
    public static function checkBillingAddress($address)
    {
        $address = json_decode($address);
        if (!isset($address->address_components) || count($address->address_components) == 0) {
            return null;
        }
        $streetNumber = false;
        $formatAddress = json_decode(json_encode(["line1" => null]));

        foreach ($address->address_components as $address_component) {
            foreach ($address_component->types as $type) {
                if ($type == 'street_number') {
                    if (is_null($formatAddress->line1)) {
                        $formatAddress->line1 = $address_component->long_name;
                    } else {
                        $formatAddress->line1 = $address_component->long_name . " " . $formatAddress->line1;
                    }
                    $streetNumber = true;
                }
                if ($type == 'street_address') {
                    if (is_null($formatAddress->line1)) {
                        $formatAddress->line1 = $address_component->long_name;
                    } else {
                        $formatAddress->line1 = $formatAddress->line1 . " " . $address_component->long_name;
                    }
                }


                if ($type == 'locality') {
                    $formatAddress->city = $address_component->long_name;
                }
                if ($type == 'postal_code') {
                    $formatAddress->zip = $address_component->long_name;
                }

                if ($type == 'country') {
                    $formatAddress->country = $address_component->short_name;
                }
                if ($type == 'route') {
                    $formatAddress->line2 = $address_component->long_name;
                }

            }
        }
        if (!$streetNumber) {
            return null;
        }

        return $formatAddress;
    }

}