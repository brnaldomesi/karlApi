<?php

namespace App\Http\Controllers\v1;


use App\Constants;
use App\ErrorCode;
use App\Method\MethodAlgorithm;
use App\Method\GeoLocationAlgorithm;
use App\Method\UserMethod;
use App\Model\Admin;
use App\Model\Company;
use App\Model\CompanyAnnex;
use App\Model\CompanyApprovalRecording;
use App\Jobs\SendEmailAdminPasswordJob;
use App\Jobs\C2CMatchJob;
use App\Model\CompanyAnSetting;
use App\Model\CompanyPayMethod;
use App\Model\CompanySetting;
use App\Model\Sale;
use App\Model\SaleCompany;
use Curl\Curl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Stripe\Order;
use Stripe\Coupon;
use Stripe\SKU;
use Stripe\Stripe;

class StripeController extends Controller
{
    public function getSkuInfo()
    {
        Stripe::setApiKey($_SERVER['STRIP_S_KEY']);

        try {
            $sku = SKU::retrieve($_SERVER['STRIP_SKU']);
            return ErrorCode::success($sku);
        } catch (\Exception $ex) {
            Log::info('error get product \n' . $ex);
            return ErrorCode::errorNotExist('product');
        }
    }

    public function getCouponInfo($couponCode)
    {
        Stripe::setApiKey($_SERVER['STRIP_S_KEY']);
        try {
            $coupon = Coupon::retrieve($couponCode);
            return ErrorCode::success($coupon);
        } catch (\Exception $ex) {
            Log::info('error get coupon \n' . $ex);
            return ErrorCode::errorNotExist('coupon');
        }
    }

    public function payOrder()
    {

        $company = Input::get('company', null);
        $admin = Input::get('admin', null);
        $couponCode = Input::get('coupon', null);
        $cardToken = Input::get('card_token', null);
        if (is_null($company) || is_null($admin)) {
            return ErrorCode::errorMissingParam();
        }

        $companyInfo = json_decode($company, true);
        if (!is_array($companyInfo)) {
            return ErrorCode::errorParam('company');
        }
        $adminInfo = json_decode($admin, true);
        if (!is_array($adminInfo)) {
            return ErrorCode::errorParam('admin');
        }

        $sku = json_decode($this->getSkuInfo());
        $sku = $sku->result;
        if (!$sku->active) {
            return ErrorCode::errorNotExist('product');
        }
        if ($sku->price != 0) {
            if (is_null($cardToken)) {
                return ErrorCode::errorMissingParam();
            }

            if (empty($cardToken)) {
                return ErrorCode::errorParam('card token');
            }

            $order = [
                "items" => [[
                    "type" => "sku",
                    "parent" => $_SERVER['STRIP_SKU']]
                ],
                "currency" => "usd",
            ];
            if (!empty($couponCode)) {
                $order['coupon'] = $couponCode;
            }
        } else {
            $order = null;
        }

        try {
            $result = DB::transaction(/**
             * @return array
             * @throws \Exception
             */
                function () use ($companyInfo, $adminInfo, $order, $cardToken) {
                    $companyName = isset($companyInfo['name']) ? $companyInfo['name'] : null;
                    $companyAddress = isset($companyInfo['address']) ? $companyInfo['address'] : null;
                    $companyLat = isset($companyInfo['lat']) ? $companyInfo['lat'] : null;
                    $companyLng = isset($companyInfo['lng']) ? $companyInfo['lng'] : null;
                    $ccy = isset($companyInfo["ccy"]) ? $companyInfo["ccy"] : "usd";
                    $country = isset($companyInfo["country"]) ? $companyInfo["country"] : null;
                    $lang = isset($companyInfo["lang"]) ? $companyInfo["lang"] : "en";
                    $saleId = isset($companyInfo["sale_id"]) ? $companyInfo["sale_id"] : null;
                    $companyMail = isset($companyInfo['mail']) ? $companyInfo['mail'] : "";
                    $companyDomain = isset($companyInfo['domain']) ? $companyInfo['domain'] : "";
                    $companyPhone1 = isset($companyInfo['phone1']) ? $companyInfo['phone1'] : "";
                    $companyPhone2 = isset($companyInfo['phone2']) ? $companyInfo['phone2'] : "";
                    $companyTcp = isset($companyInfo['tcp']) ? $companyInfo['tcp'] : null;
                    $companyEmailHost = isset($companyInfo['email_host']) ? $companyInfo['email_host'] : "";
                    $companyEmailPort = isset($companyInfo['email_port']) ? $companyInfo['email_port'] : "";
                    $companyEmailPWD = isset($companyInfo['email_password']) ? $companyInfo['email_password'] : "";
                    if (
                        is_null($companyName) ||
                        is_null($companyAddress) ||
                        is_null($companyLat) ||
                        is_null($companyLng) ||
                        is_null($companyTcp)
                    ) {
                        throw new \Exception(ErrorCode::errorMissingParam('in company'));
                    }
                    if (empty($companyName)) {
                        throw new \Exception(ErrorCode::errorParam('company name'));
                    }
                    if (empty($companyAddress)) {
                        throw new \Exception(ErrorCode::errorParam('company address'));
                    }
                    if (!is_numeric($companyLat) || $companyLat < -90 || $companyLat > 90) {
                        throw new \Exception(ErrorCode::errorParam('company lat'));
                    }
                    if (empty($country)) {
                        throw new \Exception(ErrorCode::errorParam('country'));
                    }

                    if (!is_numeric($companyLng) || $companyLng < -180 || $companyLng > 180) {
                        throw new \Exception(ErrorCode::errorParam('company lng'));
                    }
                    if (empty($companyTcp)) {
                        throw new \Exception(ErrorCode::errorParam('TCP'));
                    }
                    if (empty($lang)) {
                        throw new \Exception(ErrorCode::errorParam('lang'));
                    }
                    $saleCount = Sale::where('sale_id', $saleId)->count();
                    if ($saleCount <= 0) {
                        throw new \Exception(ErrorCode::errorNotExistSale());
                    }

                    $count = Company::where([['address', $companyAddress], ['name', $companyName]])->count();
                    if ($count > 0) {
                        throw new \Exception(ErrorCode::errorAlreadyExist('company'));
                    }

                    $timezone = GeoLocationAlgorithm::getInstance()
                        ->getLocationTime($companyLat, $companyLng, time());
                    $timezone = isset($timezone->timeZoneId) ? $timezone->timeZoneId : "UTC";
                    $company = Company::create([
                        'name' => $companyName,
                        'address' => $companyAddress,
                        'lat' => $companyLat,
                        'lng' => $companyLng,
                        'timezone' => $timezone,
                        "lang" => $lang,
                        "country" => $country,
                        'tcp' => $companyTcp,
                        "ccy" => $ccy,
                        'rate' => Constants::PLATFORM_SETTLE_TVA
                    ]);
                    CompanyAnSetting::create([
                        "company_id" => $company->id,
                        "locked" => CompanyAnSetting::AN_LOCKED,
                        "radius" => 20,
                    ]);
                    CompanyAnnex::create(["company_id" => $company->id]);
                    CompanySetting::create(["company_id" => $company->id]);

                    $password = MethodAlgorithm::getRandomPassword();
                    $adminInfo['gender'] = 2;
                    $adminInfo['address'] = '';
                    $adminInfo['password'] = $password;
                    $user = UserMethod::insertUserInfo($company->id, $adminInfo, true, true, true);
                    Admin::create(["user_id" => $user->id]);

                    if (!is_null($order)) {
                        Stripe::setApiKey($_SERVER['STRIP_S_KEY']);

                        try {
                            $order = Order::create($order);
                        } catch (\Exception $ex) {
                            Log::info('error strip create order \n' . $ex);
                            throw new \Exception(ErrorCode::errorPayFailedWith('stripe create order fault'));
                        }

                        try {
                            $order->pay(["source" => $cardToken, 'email' => $adminInfo['email']]);
                        } catch (\Exception $ex) {
                            Log::info('error stripe pay order \n ' . $ex);
                            throw new \Exception(ErrorCode::errorPayFailedWith('stripe order pay'));
                        }


                        if ($order->status != 'paid') {
                            throw new \Exception(ErrorCode::errorPayFailedWith('stripe'));
                        }

                        CompanyApprovalRecording::create([
                            'company_id' => $company->id,
                            'order_id' => $order->id,
                            'pay_id' => $order->charge,
                            'approval_state' => CompanyApprovalRecording::APPROVAL_STATE_PASS
                        ]);
                    } else {
                        CompanyApprovalRecording::create([
                            'company_id' => $company->id,
                            'order_id' => "free_" . str_random(8),
                            'pay_id' => "free_" . str_random(8),
                            'approval_state' => CompanyApprovalRecording::APPROVAL_STATE_PASS
                        ]);
                    }
                    SaleCompany::create(["sale_id" => $saleId, "company_id" => $company->id]);
                    $this->dispatch(new SendEmailAdminPasswordJob($adminInfo['email'], $password, $user->lang));
                    $this->dispatch(new C2CMatchJob($company->id));
                    return ['token' => $user->web_token];
                });
            return ErrorCode::success($result);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function bindStripeAccount(Request $request)
    {
        $companyId = $request->user->company_id;
        $code = Input::get("code", null);
        if (empty($code)) {
            return ErrorCode::errorAuthStripeAccount();
        }

        Stripe::setApiKey($_SERVER['STRIP_S_KEY']);
        $curl = new Curl();
        $result = $curl->post("https://connect.stripe.com/oauth/token", [
            "client_secret" => $_SERVER['STRIP_S_KEY'],
            "code" => $code,
            "grant_type" => "authorization_code"
        ]);
        if (isset($result->stripe_user_id) && isset($result->access_token)) {
            Company::where('id', $companyId)->update(['stripe_acct_id' => $result->stripe_user_id]);
            $method = CompanyPayMethod::firstOrNew(["company_id" => $companyId]);
            $method->client_id = "";
            $method->account = $result->stripe_user_id;
            $method->secret = $result->access_token;
            $method->pay_type = CompanyPayMethod::PAY_TYPE_STRIPE;
            $method->active = CompanyPayMethod::ACTIVE;
            $method->save();
            DB::delete("
            delete from stripe_customers where customer_id in 
            (select concat('customer_',customers.id) from customers 
            LEFT JOIN users on users.id=customers.user_id where users.company_id={$companyId})
            ");
            return ErrorCode::success($result->stripe_user_id);
        } else {
            return ErrorCode::errorAuthStripeAccount();
        }
    }
}