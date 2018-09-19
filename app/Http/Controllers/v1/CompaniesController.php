<?php

namespace App\Http\Controllers\v1;

use App\Constants;
use App\ErrorCode;
use App\Jobs\C2CMatchJob;
use App\Jobs\SendEmailAdminPasswordJob;
use App\Method\CompanyMethod;
use App\Method\MethodAlgorithm;
use App\Method\UrlSpell;
use App\Method\GeoLocationAlgorithm;
use App\Method\UserMethod;
use App\Model\Company;
use App\Model\Admin;
use App\Model\CompanyAnnex;
use App\Model\CompanyApprovalRecording;
use App\Model\CompanyPayMethod;
use App\Model\CompanyAnSetting;
use App\Model\CompanySetting;
use App\Model\CreditCard;
use App\Model\Sale;
use App\Model\SaleCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;


class CompaniesController extends Controller
{
    public function companies()
    {
        $page = Input::get('page', Constants::PAGE_DEFAULT);
        $per_page = Input::get('per_page', Constants::PER_PAGE_DEFAULT);
        return CompanyMethod::companies($page, $per_page);
    }

    public function companyDetail(Request $request, $company_id)
    {
        return Company::companyDetail($company_id);
    }

    public function getMyCompany(Request $request)
    {
        $company_id = $request->user->company_id;
        $token = $request->user->token;
        $result = Company::leftjoin("company_settings as cs", "cs.company_id", "=", "companies.id")
            ->where('companies.id', $company_id)
            ->select(
                'companies.id',
                'companies.name',
                'companies.tva',
                'companies.gmt',
                'companies.address',
                'companies.lng',
                'companies.lat',
                'companies.domain',
                'companies.email',
                'companies.phone1',
                'companies.phone2',
                'companies.tcp',
                'companies.country',
                'cs.lang',
                'companies.ccy',
                'companies.email_host',
                'companies.email_port',
                'companies.email_password',
                DB::raw(UrlSpell::getUrlSpell()->getCompaniesLogoByNameInDB())
            )
            ->first();
        if (empty($result)) {
            return ErrorCode::errorNoObject('company');
        }
        $result->email_password = base64_decode($result->email_password);
        return ErrorCode::success($result,false);
    }

    public function updateCompany($company_id, $token)
    {
        $company = Company::where('id', $company_id)->first();
        if (empty($company)) {
            return ErrorCode::errorNotExist();
        }
        $name = Input::get('name', null);
        $tva = Input::get('tva', null);
        $gmt = Input::get('gmt', null);
        $address = Input::get('address', null);
        $lat = Input::get('lat', null);
        $lng = Input::get('lng', null);
        $domain = Input::get('domain', null);
        $email = Input::get('email', null);
        $lang = Input::get('lang', null);
        $country = Input::get('country', null);
        $phone1 = Input::get('phone1', null);
        $phone2 = Input::get('phone2', null);
        $tcp = Input::get('tcp', null);
        $email_password = Input::get('email_password', null);
        $email_port = Input::get('email_port', null);
        $email_host = Input::get('email_host', null);
        if (is_null($name) &&
            is_null($tva) &&
            is_null($gmt) &&
            is_null($address) &&
            is_null($lang) &&
            is_null($country) &&
            is_null($lat) &&
            is_null($lng) &&
            is_null($domain) &&
            is_null($email) &&
            is_null($phone1) &&
            is_null($phone2) &&
            is_null($tcp) &&
            is_null($email_password) &&
            is_null($email_port) &&
            is_null($email_host)
        ) {
            return ErrorCode::errorMissingParam();
        }
        if (!is_null($name)) {
            if (empty($name)) {
                return ErrorCode::errorParam('name');
            }
            $company->name = $name;
        }
        if (!is_null($tva)) {
            if (is_numeric($tva) && $tva >= 0) {
                $company->tva = $tva;
            } else {
                return ErrorCode::errorParam('tva');
            }
        }
        if (!is_null($gmt)) {
            if (empty($gmt) || !is_numeric($gmt) || $gmt > 12 || $gmt < -12 || floor($gmt) != $gmt) {
                ErrorCode::errorParam('gmt');
            }
            $company->gmt = $gmt;
        }
        if (!is_null($address)) {
            if (empty($address)) {
                return ErrorCode::errorParam('address');
            }
            $company->address = $address;
            if (is_null($lat) || !is_numeric($lat) || $lat > 90 || $lat < -90) {
                return ErrorCode::errorParam('lat');
            }
            $company->lat = $lat;

            if (is_null($lng) || !is_numeric($lng) || $lng > 180 || $lng < -180) {
                return ErrorCode::errorParam('lng');
            }
            $company->lng = $lng;

            $timezone = GeoLocationAlgorithm::getInstance()
                ->getLocationTime($company->lat, $company->lng, time());
            $company->timezone = isset($timezone->timeZoneId) ? $timezone->timeZoneId : "UTC";
        }
        if (!is_null($domain)) {
            if (empty($domain)) {
                return ErrorCode::errorParam('domain');
            }
            $company->domain = $domain;
        }
        if (!is_null($email)) {
            if (!MethodAlgorithm::emailRegex($email)) {
                return ErrorCode::errorParam('email');
            }
            $company->email = $email;
        }
        if (!is_null($phone1)) {
            $company->phone1 = $phone1;
        }
        if (!is_null($phone2)) {
            $company->phone2 = $phone2;
        }
        if (!is_null($tcp)) {
            $company->tcp = $tcp;
        }

        if (!is_null($email_port)) {
            if (is_numeric($email_port)) {
                $company->email_port = $email_port;
            } else {
                ErrorCode::errorParam('email port');
            }
        }


        if (!is_null($country)) {
            if (empty($country)) {
                ErrorCode::errorParam('country');
            } else {
                $company->country = $country;
            }
        }

        if (!is_null($email_host)) {
            if (!empty($email_host)) {
                $company->email_host = $email_host;
            } else {
                ErrorCode::errorParam('email host');
            }
        }

        if (!is_null($email_password)) {
            if (!empty($email_password)) {
                $company->email_password = base64_encode($email_password);
            } else {
                ErrorCode::errorParam('email password');
            }
        }

        if (!$company->save()) {
            return ErrorCode::errorDB();
        }

        if (!is_null($lang)) {
            if (!empty($lang) && (strtolower($lang) == "en" || strtolower($lang) == "fr")) {
                CompanySetting::where("company_id", $company_id)->update(["lang" => $lang]);
            } else {
                ErrorCode::errorParam('lang');
            }
        }

        $company->email_password = ($email_password);
        $company->lang = $lang;
        $company->img = UrlSpell::getUrlSpell()->getCompaniesLogoByName($company->name, $company->updated_at);
        $this->dispatch(new C2CMatchJob($company_id));
        return ErrorCode::success($company,false);
    }

    public function updateMyCompany(Request $request)
    {
        $company_id = $request->user->company_id;
        $token = $request->user->token;
        return $this->updateCompany($company_id, $token);
    }

    public function createAllNewCompany(Request $request)
    {
        try {
            return ErrorCode::success(DB::transaction(function () use ($request) {

                $company_param = Input::get('company_param', null);
                $admin_param = Input::get('admin_param', null);
                $payment_param = Input::get('payment_param', null);
                $charge_param = Input::get('charge_param', null);

                // create company
                $company_info = json_decode($company_param, true);
                $name = isset($company_info['name']) ? $company_info['name'] : null;
                $tva = isset($company_info['tva']) ? $company_info['tva'] : null;
                $gmt = isset($company_info['gmt']) ? $company_info['gmt'] : null;
                $address = isset($company_info['address']) ? $company_info['address'] : null;
                $lat = isset($company_info['lat']) ? $company_info['lat'] : null;
                $lng = isset($company_info['lng']) ? $company_info['lng'] : null;
                $domain = isset($company_info['domain']) ? $company_info['domain'] : null;
                $email = isset($company_info['email']) ? $company_info['email'] : null;
                $phone1 = isset($company_info['phone1']) ? $company_info['phone1'] : null;
                $phone2 = isset($company_info['phone2']) ? $company_info['phone2'] : null;
                $email_host = isset($company_info['email_host']) ? $company_info['email_host'] : null;
                $email_port = isset($company_info['email_port']) ? $company_info['email_port'] : null;
                $email_password = isset($company_info['email_password']) ? $company_info['email_password'] : null;
                if (is_null($name) ||
                    is_null($tva) ||
                    is_null($gmt) ||
                    is_null($address) ||
                    is_null($lat) ||
                    is_null($lng) ||
                    is_null($domain) ||
                    is_null($email) ||
                    is_null($phone1) ||
                    is_null($phone2) ||
                    is_null($email_host) ||
                    is_null($email_port) ||
                    is_null($email_password)
                ) {
                    throw new \Exception(ErrorCode::errorMissingParam('in company'));
                }
                if (empty($name)) {
                    throw new \Exception(ErrorCode::errorParam('name'));
                }
                if (is_numeric($tva) && $tva < 0) {
                    throw new \Exception(ErrorCode::errorParam('tva'));
                }
                if (empty($gmt) || !is_numeric($gmt) || $gmt > 12 || $gmt < -12 || floor($gmt) != $gmt) {
                    throw new \Exception(ErrorCode::errorParam('gmt'));
                }
                if (empty($address)) {
                    throw new \Exception(ErrorCode::errorParam('address'));
                }
                if (!is_numeric($lat) || $lat > 90 || $lat < -90) {
                    throw new \Exception(ErrorCode::errorParam('lat'));
                }
                if (!is_numeric($lng) || $lng > 180 || $lng < -180) {
                    throw new \Exception(ErrorCode::errorParam('lng'));
                }
                if (empty($domain)) {
                    throw new \Exception(ErrorCode::errorParam('domain'));
                }
                if (!MethodAlgorithm::emailRegex($email)) {
                    throw new \Exception(ErrorCode::errorParam('email'));
                }
                if (empty($email_host)) {
                    throw new \Exception(ErrorCode::errorParam('email_host'));
                }
                if (!is_numeric($email_port) || $email_port < 0) {
                    throw new \Exception(ErrorCode::errorParam('email_port'));
                }
                if (empty($email_password)) {
                    throw new \Exception(ErrorCode::errorParam('email_password'));
                }

                $timezone = GeoLocationAlgorithm::getInstance()
                    ->getLocationTime($lat, $lng, time());
                $timezone = isset($timezone->timeZoneId) ? $timezone->timeZoneId : "UTC";

                $company = Company::create([
                    'name' => $name,
                    'tva' => $tva,
                    'gmt' => $gmt,
                    'address' => $address,
                    'lat' => $lat,
                    'lng' => $lng,
                    'timezone' => $timezone,
                    'domain' => $domain,
                    'email' => $email,
                    'phone1' => $phone1,
                    'phone2' => $phone2,
                    'email_host' => $email_host,
                    'email_port' => $email_port,
                    'email_password' => base64_encode($email_password),
                    "rate" => Constants::PLATFORM_SETTLE_TVA
                ]);
                $company_id = $company->id;
                CompanyApprovalRecording::create(
                    [
                        'company_id' => $company_id,
                        'approval_state' => CompanyApprovalRecording::APPROVAL_STATE_PASS
                    ]
                );


                CompanyAnSetting::create(["company_id" => $company_id]);
                CompanySetting::create(["company_id" => $company_id]);
                CompanyAnnex::create(["company_id" => $company->id]);
                // create admin
                $admin_info = json_decode($admin_param, true);
                $user = UserMethod::insertUserInfo($company_id, $admin_info, true, false, true);
                $admin = Admin::create(["user_id" => $user->id]);

                // create payment
                $payment_info = json_decode($payment_param, true);
                $card_type = isset($payment_info['card_type']) ? $payment_info['card_type'] : null;
                $card_number = isset($payment_info['card_number']) ? $payment_info['card_number'] : null;
                $expire_month = isset($payment_info['expire_month']) ? $payment_info['expire_month'] : null;
                $expire_year = isset($payment_info['expire_year']) ? $payment_info['expire_year'] : null;
                $cvv2 = isset($payment_info['cvv2']) ? $payment_info['cvv2'] : null;
                $first_name = isset($payment_info['first_name']) ? $payment_info['first_name'] : null;
                $last_name = isset($payment_info['last_name']) ? $payment_info['last_name'] : null;
                $result = CreditCard::checkCreditCardInfo($card_type,
                    $card_number, $expire_month,
                    $expire_year, $cvv2, $first_name, $last_name);
                if ($result['code'] == 2000) {
                } elseif ($result['code'] == 3000) {
                    throw new \Exception(ErrorCode::errorMissingParam($result['result']));
                } elseif ($result['code'] == 3001) {
                    throw new \Exception(ErrorCode::errorParam($result['result']));
                } else {
                    throw new \Exception('tommy lee bug');
                }

                // create charge
                $charge_info = json_decode($charge_param, true);
                $client_id = isset($charge_info['client_id']) ? $charge_info['client_id'] : null;
                $secret = isset($charge_info['secret']) ? $charge_info['secret'] : null;
                $pay_type = isset($charge_info['pay_type']) ? $charge_info['pay_type'] : null;
                $active = 1;
                if (is_null($client_id) || is_null($secret) || is_null($pay_type)) {
                    throw new \Exception(ErrorCode::errorMissingParam('in pay'));
                }
                if (empty($client_id)) {
                    throw new \Exception(ErrorCode::errorParam('client_id'));
                }
                if (empty($secret)) {
                    throw new \Exception(ErrorCode::errorParam('secret'));
                }
                if (empty($pay_type) || !is_numeric($pay_type) || $pay_type != 1) {
                    throw new \Exception(ErrorCode::errorParam('unknown pay type'));
                }
                $pay_method = CompanyPayMethod::create([
                    'company_id' => $company_id,
                    'client_id' => $client_id,
                    'secret' => $secret,
                    'pay_type' => $pay_type,
                    'active' => $active
                ]);
                $company->email_password = $email_password;
                return $company;
            }),true);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function getCompanyInfo($company_id)
    {

        if (empty($company_id) || !is_numeric($company_id)) {
            return ErrorCode::errorParam('CompanyId');
        }



        $company = Company::leftjoin('company_settings', 'company_settings.company_id', '=', 'companies.id')
            ->leftjoin("card_zip_code_setting", "card_zip_code_setting.country_code", "=", "companies.country")
            ->where('companies.id', $company_id)
            ->select('companies.id', 'companies.name',
                'companies.phone1', 'companies.phone2',
                'companies.email', 'company_settings.distance_unit',
                "companies.country", "companies.lat", "companies.lng",
                "card_zip_code_setting.proving"
            )
            ->first();



        if (empty($company)) {
            return ErrorCode::errorNotExist('company');
        }

        
        return ErrorCode::success($company,false);
    }

    public function updateCompanyRate($company_id)
    {
        $company_rate = Input::get('company_rate', null);

        if (is_null($company_rate) || is_null($company_id)) {
            return ErrorCode::errorMissingParam();
        }

        if (!is_numeric($company_rate) || $company_rate < 0) {
            return ErrorCode::errorParam('rate');
        }

        if (!is_numeric($company_id)) {
            return ErrorCode::errorParam('company_id');
        }

        try {
            return DB::transaction(function () use ($company_id, $company_rate) {
                $company_rate = $company_rate / 100;
                $count = Company::where('id', $company_id)->count();

                if ($count == 0) {
                    return ErrorCode::errorNotExist('company');
                }

                Company::where('id', $company_id)->update(['rate' => $company_rate]);

                return ErrorCode::success("success");
            });

        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function getCompaniesAppUrl($company_id)
    {

        if (empty($company_id) || !is_numeric($company_id)) {
            return ErrorCode::errorParam('CompanyId');
        }

        $app = CompanyAnnex::where("company_id", $company_id)
            ->select('ios_app', 'android_app')
            ->first();

        if (empty($app)) {
            return ErrorCode::errorNotExist('company annex');
        }

        return ErrorCode::success($app);
    }

    public function updateCompanyAppSetting($company_id)
    {
        try {
            return DB::transaction(function () use ($company_id) {

                $ios_url = Input::get('ios_url', null);
                $ios_id = Input::get('ios_id', null);
                $android_url = Input::get('android_url', null);
                $pkg_name = Input::get('pkg_name', null);

                if (is_null($company_id)) {
                    return ErrorCode::errorMissingParam();
                }

                if (empty($ios_id)) {
                    return ErrorCode::errorParam('ios_id');
                }
                if (empty($pkg_name)) {
                    return ErrorCode::errorParam('pkg_name');
                }


                if (!is_numeric($company_id)) {
                    return ErrorCode::errorParam('company_id');
                }

                $count = CompanyAnnex::where("company_id", $company_id)
                    ->select('ios_app', 'android_app')
                    ->count();

                if ($count == 0) {
                    CompanyAnnex::create([
                        'company_id' => $company_id,
                        'ios_app' => $ios_url,
                        "ios_id" => $ios_id,
                        "ios_version" => "0.0.0",
                        'android_app' => $android_url,
                        "pkg_name" => $pkg_name,
                        "android_version" => "0.0.0"

                    ]);
                } else {
                    CompanyAnnex::where("company_id", $company_id)
                        ->update([
                            'ios_app' => $ios_url,
                            "ios_id" => $ios_id,
                            "pkg_name" => $pkg_name,
                            'android_app' => $android_url]);
                }

                $annex = CompanyAnnex::where("company_id", $company_id)
                    ->select('ios_app', 'android_app')
                    ->first();

                return ErrorCode::success($annex);
            });
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function getCompanyPushConfig($company_id)
    {
        if (is_null($company_id)) {
            return ErrorCode::errorMissingParam();
        }

        if (!is_numeric($company_id)) {
            return ErrorCode::errorParam('company_id');
        }

        try {
            return DB::transaction(function () use ($company_id) {

                $company_push_config = DB::table('company_push_config')->where('company_id', $company_id)->get();

                if (empty($company_push_config)) {
                    return ErrorCode::errorNotExist('company_push_config');
                }

                return ErrorCode::success($company_push_config);
            });
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function addCompanyPushConfig($company_id)
    {
        $push_profile = Input::get('push_profile', null);
        $push_api_token = Input::get('push_api_token', null);
        $push_type = Constants::CUSTOMER_PUSH;

        if (is_null($company_id) || is_null($push_profile) || is_null($push_api_token)) {
            return ErrorCode::errorMissingParam();
        }

        if (!is_numeric($company_id)) {
            return ErrorCode::errorParam('company_id');
        }

        if (empty($push_profile)) {
            return ErrorCode::errorParam('push_profile');
        }

        if (empty($push_api_token)) {
            return ErrorCode::errorParam('push_api_token');
        }

        try {
            return DB::transaction(function () use ($company_id, $push_type, $push_profile, $push_api_token) {

                $count = DB::table('company_push_config')->where('company_id', $company_id)->count();

                if ($count > 0) {
                    return ErrorCode::errorAlreadyExist('company_push_config');
                }

                DB::table('company_push_config')
                    ->insert([
                        'company_id' => $company_id,
                        'push_type' => $push_type,
                        'push_profile' => $push_profile,
                        'push_api_token' => $push_api_token
                    ]);

                return ErrorCode::success('success');
            });
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function updateCompanyPushConfig($config_id)
    {
        $push_profile = Input::get('push_profile', null);
        $push_api_token = Input::get('push_api_token', null);
        $push_type = Constants::CUSTOMER_PUSH;

        if (is_null($config_id) || is_null($push_profile) || is_null($push_api_token)) {
            return ErrorCode::errorMissingParam();
        }

        if (!is_numeric($config_id)) {
            return ErrorCode::errorParam('company_id');
        }

        if (empty($push_profile)) {
            return ErrorCode::errorParam('push_profile');
        }

        if (empty($push_api_token)) {
            return ErrorCode::errorParam('push_api_token');
        }

        try {
            return DB::transaction(function () use ($config_id, $push_type, $push_profile, $push_api_token) {
                $count = DB::table('company_push_config')->where('id', $config_id)->count();

                if ($count == 0) {
                    return ErrorCode::errorNotExist('company_push_config');
                }

                DB::table('company_push_config')
                    ->where('id', $config_id)
                    ->update([
                        'push_type' => $push_type,
                        'push_profile' => $push_profile,
                        'push_api_token' => $push_api_token
                    ]);

                $company_push_config = DB::table('company_push_config')->where('id', $config_id)->first();

                return ErrorCode::success($company_push_config);
            });
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function getCompanyDetails($company_id)
    {
        if (is_null($company_id)) {
            return ErrorCode::errorMissingParam();
        }

        if (!is_numeric($company_id)) {
            return ErrorCode::errorParam('company_id');
        }

        try {
            return DB::transaction(function () use ($company_id) {

                $company = Company::leftjoin(
                    DB::raw("(select count(*) as count,company_id 
            from cars group by cars.company_id) as cars "),
                    'cars.company_id', '=', 'companies.id')
                    ->leftjoin(
                        DB::raw("(select count(*) as count,company_id 
            from offers group by offers.company_id) as offers "),
                        'offers.company_id', '=', 'companies.id'
                    )
                    ->leftjoin(
                        DB::raw("(select count(*) as count,company_id 
            from options group by options.company_id) as options "),
                        'options.company_id', '=', 'companies.id'
                    )
                    ->leftjoin(
                        DB::raw("(select count(*) as count,users.company_id 
            from drivers  left join users on users.id=drivers.user_id group by users.company_id) as drivers "),
                        'drivers.company_id', '=', 'companies.id'
                    )
                    ->leftjoin(
                        DB::raw("(select count(*) as count,users.company_id 
            from customers left join users on users.id=customers.user_id  group by users.company_id) as customers "),
                        'customers.company_id', '=', 'companies.id'
                    )
                    ->leftjoin('sale_asst_companies', 'sale_asst_companies.company_id', '=', 'companies.id')
                    ->leftjoin('assts', 'sale_asst_companies.asst_id', '=', 'assts.asst_id')
                    ->leftjoin('users as ausers', 'assts.user_id', '=', 'ausers.id')
                    ->leftjoin('sales', 'sale_asst_companies.sale_id', '=', 'sales.sale_id')
                    ->leftjoin('users as susers', 'sales.user_id', '=', 'susers.id')
                    ->leftjoin('company_annexes', 'company_annexes.company_id', '=', 'companies.id')
                    ->leftjoin('company_an_settings', 'company_an_settings.company_id', '=', 'companies.id')
                    ->leftjoin('company_push_config', 'company_push_config.company_id', '=', 'companies.id')
                    ->leftjoin(DB::raw("(select count(c.car_id) as active_car ,c.company_id from (select count(*) as car_count ,bookings.car_id , bookings.company_id
from bookings left join orders on orders.booking_id=bookings.id
  where orders.trip_state >= 4
group by bookings.car_id) as c where c.car_count >=6 GROUP BY c.company_id) as ac"), 'ac.company_id', '=', 'companies.id')
                    ->where('companies.id', $company_id)
                    ->select(
                        DB::raw(UrlSpell::getUrlSpell()->getCompaniesLogoByNameInDB(), ""),
                        'companies.id', 'companies.name',
                        'companies.address',
                        'company_an_settings.locked as an_locked',
                        DB::raw('companies.rate*100 as rate'),
                        'company_annexes.id as annexes_id', 'company_annexes.ios_app',
                        'company_annexes.android_app',
                        'company_annexes.ios_id',
                        'company_annexes.pkg_name',
                        'company_push_config.id as push_config_id',
                        'company_push_config.push_type',
                        'company_push_config.push_profile',
                        'company_push_config.push_api_token',
                        'sales.sale_id',
                        'sale_asst_companies.asst_id',
                        DB::raw("concat(susers.first_name,' ',susers.last_name) as sale_name"),
                        DB::raw("concat(ausers.first_name,' ',ausers.last_name) as asst_name"),
                        DB::raw('ifnull(cars.count,0) as car_count'),
                        DB::raw('ifnull(drivers.count,0) as driver_count'),
                        DB::raw('ifnull(customers.count,0) as customer_count'),
                        DB::raw('ifnull(options.count,0) as option_count'),
                        DB::raw('ifnull(offers.count,0) as offer_count'),
                        DB::raw("ifnull(ac.active_car,0) as active_car")
                    )
                    ->first();

                if (empty($company)) {
                    return ErrorCode::errorNotExist('company');
                }

                return ErrorCode::success($company);
            });
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function getCompanyByName()
    {
        $company_name = Input::get('search', null);

        if (is_null($company_name)) {
            return ErrorCode::errorMissingParam('company_name');
        }

        if (empty($company_name)) {
            return ErrorCode::errorParam('company_name');
        }

        $company = Company::where('name', 'LIKE', '%' . $company_name . '%')
            ->select('id as company_id', 'name as company_name')
            ->get();

        if (empty($company)) {
            return ErrorCode::errorNotExist('company');
        }

        return ErrorCode::success($company);
    }

    public function createCompanyByEasySignUp()
    {
        $company = Input::get('company', null);
        $admin = Input::get('admin', null);
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

        try {
            $result = DB::transaction(/**
             * @return array
             * @throws \Exception
             */
                function () use ($companyInfo, $adminInfo) {
                    $companyName = isset($companyInfo['name']) ? $companyInfo['name'] : null;
                    $companyAddress = isset($companyInfo['address']) ? $companyInfo['address'] : null;
                    $companyLat = isset($companyInfo['lat']) ? $companyInfo['lat'] : null;
                    $companyLng = isset($companyInfo['lng']) ? $companyInfo['lng'] : null;
                    $ccy = isset($companyInfo["ccy"]) ? $companyInfo["ccy"] : "";
                    $country = isset($companyInfo["country"]) ? $companyInfo["country"] : null;
                    $lang = isset($companyInfo["lang"]) ? $companyInfo["lang"] : null;
                    $saleId = isset($companyInfo["sale_id"]) ? $companyInfo["sale_id"] : null;
                    $companyEmail = isset($companyInfo['email']) ? $companyInfo['email'] : "";
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
                        is_null($companyLng)
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
                    $saleCount = Sale::where('sale_id', $saleId)->count();
                    if ($saleCount <= 0) {
                        throw new \Exception(ErrorCode::errorNotExistSale());
                    }
                    $companyCount = Company::where([['address', $companyAddress], ['name', $companyName]])->count();
                    if ($companyCount > 0) {
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
                        'email' => $companyEmail,
                        'phone1' => $companyPhone1,
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

                    $adminInfo['gender'] = 2;
                    $adminInfo['address'] = '';
                    $user = UserMethod::insertUserInfo($company->id, $adminInfo, true, true, true);
                    Admin::create(["user_id" => $user->id]);

                    CompanyApprovalRecording::create([
                        'company_id' => $company->id,
                        'order_id' => "free_" . str_random(8),
                        'pay_id' => "free_" . str_random(8),
                        'approval_state' => CompanyApprovalRecording::APPROVAL_STATE_PASS
                    ]);

                    SaleCompany::create(["sale_id" => $saleId, "company_id" => $company->id]);
                    $this->dispatch(new SendEmailAdminPasswordJob($adminInfo['email'], $adminInfo['password'], $user->lang));
                    $this->dispatch(new C2CMatchJob($company->id));
                    return ['token' => $user->web_token];
                });
            return ErrorCode::success($result);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function setCompanyCcy(Request $request, $ccy)
    {
        $company_id = $request->user->company_id;
        if (strtolower($ccy) != "usd" &&
            strtolower($ccy) != "eur" &&
            strtolower($ccy) != "gbp" &&
            strtolower($ccy) != "aud" &&
            strtolower($ccy) != "dkk" &&
            strtolower($ccy) != "cad" &&
            strtolower($ccy) != "hkd" &&
            strtolower($ccy) != "jpy" &&
            strtolower($ccy) != "nzd" &&
            strtolower($ccy) != "nok" &&
            strtolower($ccy) != "sgd" &&
            strtolower($ccy) != "sek" &&
            strtolower($ccy) != "chf"
            ) {
            return ErrorCode::errorParam("ccy");
        }
        $company = Company::where("id", $company_id)->where("ccy", "")->first();
        if (empty($company)) {
            return ErrorCode::errorAlreadyExist("this company ccy has been set");
        } else {
            Company::where("id", $company_id)->where("ccy", "")->update(["ccy" => strtolower($ccy)]);;
        }
        return ErrorCode::success("success");
    }

}
