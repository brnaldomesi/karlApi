<?php

namespace App\Http\Controllers\v1;

use App\Constants;
use App\ErrorCode;
use App\Jobs\AddCustomerToGroup;
use App\Jobs\UpdateCustomerGroupInfo;
use App\Jobs\CustomerRemoveAllCreditCardJob;
use App\Method\PaymentAlgorithm;
use App\Method\UrlSpell;
use App\Method\MethodAlgorithm;
use App\Method\UserMethod;
use App\Model\Admin;
use App\Model\Booking;
use App\Model\Company;
use App\Model\CompanyPayMethod;
use App\Model\CreditCard;
use App\Model\Customer;
use App\Model\Driver;
use App\Model\Order;
use App\Model\StripeCustomer;
use App\Model\Superadmin;
use App\Model\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

class CustomersController extends Controller
{
    public function register($company_id)
    {
//        $username = Input::get('username', null);
        $password = Input::get('password', null);
        $first_name = Input::get('first_name', null);
        $last_name = Input::get('last_name', null);
        $mobile = Input::get('mobile', null);
        $email = Input::get('email', null);
        $lang = Input::get('lang', null);
        $gender = Input::get('gender', 2);
        $timeMode = Input::get('timemode', 0);
        if (//is_null($username) ||
            is_null($password) ||
            is_null($first_name) ||
            is_null($last_name) ||
            is_null($mobile) ||
            is_null($email)
        ) {
            return ErrorCode::errorMissingParam();
        } else {
        }


//        if (empty($username)) {
//            return ErrorCode::errorParam("param username is empty");
//        } else {
//        }

        if (empty($first_name)) {
            return ErrorCode::errorParam("param first_name is empty");
        } else {
        }

        if (empty($last_name)) {
            return ErrorCode::errorParam("param last_name is empty");
        } else {
        }

        //验证密码格式是否正确 TODO
        if (!preg_match("/\S{6,16}$/", $password)) {
            return ErrorCode::errorParam("Invalid password");
        } else {
            $password = md5($password);
        }
        if ($gender != 1 && $gender != 2) {
            return ErrorCode::errorParam('gender');
        } else {
        }

        //验证手机格式是否正确 TODO 格式
        if (empty($mobile)) {
            return ErrorCode::errorParam("Invalid mobile");
        }
        //验证邮箱格式是否正确
        if (!MethodAlgorithm::emailRegex($email)) {
            return ErrorCode::errorParam("Invalid email");
        } else {
        }

        if(empty($lang)){
            $lang="en";
        }

//        $user = User::where('username', $username)->where('company_id', $company_id)->first();
//        if (!empty($user)) {
//            return ErrorCode::errorRegisteredUsername();
//        }
        $user = User::where('email', $email)->where('company_id', $company_id)->first();
        if (!empty($user)) {
            return ErrorCode::errorRegisteredEmail();
        }
        $user = User::where('mobile', $mobile)->where('company_id', $company_id)->first();
        if (!empty($user)) {
            return ErrorCode::errorRegisteredMobile();
        }
        //注册用户
        $user = User::create(
            array("first_name" => $first_name,
                "last_name" => $last_name,
                "company_id" => $company_id,
                "username" => md5($email),
                "mobile" => $mobile,
                "email" => $email,
                "gender" => $gender,
                "lang" => $lang,
                "password" => $password,
                "timemode" => $timeMode));
        $result = $user->save();
        if (!$result) {
            return ErrorCode::errorRegisteredFailed();
        }
        //注册成为customer
        $customer = Customer::create(array("user_id" => $user->id));
        $user->customer = $customer;
        $this->dispatch(new AddCustomerToGroup($email,$company_id));
        return ErrorCode::success($user,false);
    }


    public function customerCount()
    {
        return ErrorCode::success(Customer::all()->count());
    }

    public function companyCustomerCount(Request $request)
    {
        $company_id = $request->user->company_id;
        $result = Customer::leftjoin('users', 'customers.user_id', '=', 'users.id')
            ->where('users.company_id', $company_id)
            ->count();
        return ErrorCode::success($result);
    }

    public function customers()
    {
        $page = Input::get('page', Constants::PAGE_DEFAULT);
        $per_page = Input::get('per_page', Constants::PER_PAGE_DEFAULT);
        if ($per_page > 100 || $per_page < 1 || !is_numeric($per_page)) {
            $per_page = Constants::PER_PAGE_DEFAULT;
        }
        if ($page < 1 || !is_numeric($page)) {
            $page = Constants::PAGE_DEFAULT;
        }
        $skip = $per_page * ($page - 1);
        $result = array();
        $result['total'] = Customer::leftjoin('users', 'customers.user_id', '=', 'users.id')
            ->count();
        $customers = Customer::leftjoin('users', 'customers.user_id', '=', 'users.id')
            ->leftjoin('companies', 'users.company_id', "=", "companies.id")
            ->select('customers.id as customer_id', 'users.first_name as first_name',
                'users.last_name as last_name', 'users.mobile as mobile',
                'users.email as email', 'customers.user_id as user_id',
                'companies.id as company_id', 'companies.name as companies_name')
            ->skip($skip)
            ->take($per_page)
            ->get();

        if (empty($customers)) {
            return ErrorCode::successEmptyResult('there is no customer in DB');
        } else {
            $result['customers'] = $customers;
            return ErrorCode::success($result,true);
        }
    }

    public function companyCustomers(Request $request)
    {
        $page = Input::get('page', Constants::PAGE_DEFAULT);
        $per_page = Input::get('per_page', Constants::PER_PAGE_DEFAULT);
        $search = Input::get('search', null);
        if ($per_page > 100 || $per_page < 1 || !is_numeric($per_page)) {
            $per_page = Constants::PER_PAGE_DEFAULT;
        }
        if ($page < 1 || !is_numeric($page)) {
            $page = Constants::PAGE_DEFAULT;
        }
        $skip = $per_page * ($page - 1);
        $company_id = $request->user->company_id;
        $token = $request->user->token;

        $result = array();
        $customers = Customer::leftjoin('users', 'customers.user_id', '=', 'users.id')
            ->where('users.company_id', $company_id)
            ->where(function ($query) use ($search) {
                if (!empty($search)) {
                    $query->where('users.first_name', 'like', "%" . $search . "%")
                        ->orWhere('users.last_name', 'like', "%" . $search . "%")
                        ->orWhere('users.mobile', 'like', "%" . $search . "%")
                        ->orWhere('users.email', 'like', "%" . $search . "%");
                }
            })
            ->leftjoin('companies', 'users.company_id', "=", "companies.id")
            ->select(
                'customers.id as customer_id', 'users.first_name as first_name',
                'users.last_name as last_name', 'users.mobile as mobile',
                'users.email as email', 'customers.user_id as user_id',
                'users.address', 'users.lat', 'users.lng',
                DB::raw(UrlSpell::getUrlSpell()->getSpellAvatarInDB('users.updated_at', 'users.avatar_url',
                        'customers.id', $token, UrlSpell::companyCustomerType) . " as customer_avatar", ""),
                'companies.id as company_id',
                'companies.name as companies_name')
            ->skip($skip)
            ->take($per_page)
            ->get();
        $activePay = CompanyPayMethod::where('company_id', $company_id)
            ->where('active', CompanyPayMethod::ACTIVE)
            ->first();
        foreach ($customers as $customer) {
            $cards=[];
            if(!empty($activePay)){
                $cards = CreditCard::where('owner_id', $customer->customer_id)
                    ->where("type", CreditCard::TYPE_CUSTOMER)
                    ->where('pay_method_id', $activePay->id)
                    ->get();
            }
            $customer->cards = $cards;
        }

        $result['total'] = Customer::leftjoin('users', 'customers.user_id', '=', 'users.id')
            ->where('users.company_id', $company_id)
            ->where(function ($query) use ($search) {
                if (!empty($search)) {
                    $query->where('users.first_name', 'like', "%" . $search . "%")
                        ->orWhere('users.last_name', 'like', "%" . $search . "%")
                        ->orWhere('users.mobile', 'like', "%" . $search . "%")
                        ->orWhere('users.email', 'like', "%" . $search . "%");
                }
            })->count();

        if (empty($customers)) {
            return ErrorCode::successEmptyResult('there is no customer in DB');
        } else {
            $result['customers'] = $customers;
            return ErrorCode::success($result,false);
        }
    }

    public function customer($customer_id)
    {
        $token = Input::get('token', null);
        if (is_null($token) || empty($token)) {
            return ErrorCode::errorMissingParam();
        }
        $customer = Customer::where('customers.id', $customer_id)
            ->leftjoin('users', 'customers.user_id', '=', 'users.id')
            ->leftjoin('companies', 'users.company_id', "=", "companies.id")
            ->select('customers.user_id as user_id', 'customers.id as customer_id',
                'users.first_name',
                'users.last_name',
                'users.email',
                'users.mobile',
                'users.address', 'users.lat', 'users.lng',
                'users.avatar_url',
                'users.gender',
                DB::raw('unix_timestamp(users.updated_at) as updated_at'),
                'companies.id as company_id', 'companies.name as companies_name')
            ->first();
        if (empty($customer)) {
            return ErrorCode::errorNotExist('customer');
        } else {
            $customer->avatar_url = UrlSpell::getUrlSpell()
                ->spellingAvatarUrl($customer->updated_at, $customer->avatar_url,
                    $token, $customer_id, UrlSpell::customerType);

            return ErrorCode::success($customer,false);
        }
    }


    public function companyCustomer(Request $request, $customer_id)
    {
        $company_id = $request->user->company_id;
        $token = $request->user->token;
        try {
            return ErrorCode::success($this->getCustomerInformation($customer_id, $company_id, $token, UrlSpell::companyCustomerType),false);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }


    public function addCustomer()
    {
        $company_id = Input::get('company_id', null);
        $param = Input::get('param', null);
        $token = Input::get('token', null);

        if (is_null($company_id) || is_null($param) || is_null($token)) {
            return ErrorCode::errorMissingParam();
        }
        try {
            $param = json_decode($param, true);
        } catch (\Exception $ex) {
            return ErrorCode::errorParam('param not json');
        }
        try {
            $customer = $this->insertCustomer($company_id, $param, $token, UrlSpell::customerType);
            return ErrorCode::success($customer);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function companyAddCustomer(Request $request)
    {
        $company_id = $request->user->company_id;
        $param = Input::get('param', null);
        $token = Input::get('token', null);
        if (is_null($param) || is_null($token)) {
            return ErrorCode::errorMissingParam();
        }
        try {
            $param = json_decode($param, true);
        } catch (\Exception $ex) {
            return ErrorCode::errorParam('param not json');
        }

        if (!is_array($param)) {
            return ErrorCode::errorParam('param');
        }

        $pwd = MethodAlgorithm::getRandomPassword();
        $param['password'] = $pwd;

        try {
            $customer = $this->insertCustomer($company_id, $param, $token, UrlSpell::companyCustomerType);
            return ErrorCode::success($customer);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    private function insertCustomer($company_id, $param, $token, $type)
    {
        try {
            $result = DB::transaction(function () use ($company_id, $param, $token, $type) {
                $user = UserMethod::insertUserInfo($company_id, $param);
                $customer = Customer::create([
                    'user_id' => $user->id
                ]);
                $payInfo  = CompanyPayMethod::leftjoin("companies","companies.id","=","company_pay_methods.company_id")
                    ->leftjoin("card_zip_code_setting","card_zip_code_setting.country_code","=","companies.country")
                    ->where("company_pay_methods.company_id", $company_id)
                    ->where("company_pay_methods.active", CompanyPayMethod::ACTIVE)
                    ->where("company_pay_methods.pay_type", CompanyPayMethod::PAY_TYPE_STRIPE)
                    ->select(
                        "company_pay_methods.id",
                        DB::raw("ifnull(card_zip_code_setting.proving,1) as proving"),
                        "company_pay_methods.secret"
                    )
                    ->first();
                if (!empty($payInfo)&&isset($param['sc_id'])&&!empty($param['sc_id'])) {
                    try{
                        StripeCustomer::create(['customer_id'=>"customer_".$customer->id,"stripe_customer_id"=>$param['sc_id']]);
                        PaymentAlgorithm::getPayment()->syncPayMethodCustomerCreditCard($payInfo,$customer->id,$param['sc_id']);
                    }catch(\Exception $ex){
                    }
                }
                return $this->getCustomerInformation($customer->id, $company_id, $token, $type);
            });
            $this->dispatch(new AddCustomerToGroup($result->email,$company_id));
            return $result;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    public function customerUpdateInfo(Request $request)
    {
        $customer_id = $request->user->customer->id;
        $param = Input::get('param', null);
        $token = Input::get('token', null);
        if (is_null($param) || is_null($token)) {
            return ErrorCode::errorMissingParam();
        }
        try {
            $param = json_decode($param, true);
            if (isset($param['password'])) {
                array_pull($param, 'password');
            }
            $param = json_encode($param);
            $result = $this->updateCustomerInfo($customer_id, $param, $token, UrlSpell::mine);
            return ErrorCode::success($result,false);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }


    public function companyUpdateCustomer(Request $request, $customer_id)
    {
        $company_id = $request->user->company_id;
        $customer = Customer::where('customers.id', $customer_id)
            ->leftjoin('users', 'customers.user_id', '=', 'users.id')
            ->select('users.company_id')
            ->first();
        if ($company_id != $customer->company_id) {
            throw new \Exception(ErrorCode::errorAdminUnauthorizedOperation());
        }
        $param = Input::get('param', null);
        $token = Input::get('token', null);
        if (is_null($param) || is_null($token)) {
            return ErrorCode::errorMissingParam();
        }
        try {
            $result = $this->updateCustomerInfo($customer_id, $param, $token, UrlSpell::companyCustomerType);
            return ErrorCode::success($result,false);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function updateCustomer($customer_id)
    {
        $param = Input::get('param', null);
        $token = Input::get('token', null);
        if (is_null($param) || is_null($token)) {
            return ErrorCode::errorMissingParam();
        }
        try {
            $result = $this->updateCustomerInfo($customer_id, $param, $token, UrlSpell::customerType);
            return ErrorCode::success($result,false);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    private function updateCustomerInfo($customer_id, $param, $token, $type)
    {
        $result = DB::transaction(function () use ($customer_id, $param, $token, $type) {
            $customer = Customer::where('customers.id', $customer_id)->first();
            if (empty($customer)) {
                throw new \Exception(ErrorCode::errorNotExist('customer'));
            }
            $param = json_decode($param, true);
            $param['id'] = $customer->user_id;
            $user = UserMethod::updateUserInfo($param);
            $payInfo = CompanyPayMethod::leftjoin("companies","companies.id","=","company_pay_methods.company_id")
                ->leftjoin("card_zip_code_setting","card_zip_code_setting.country_code","=","companies.country")
                ->where("company_pay_methods.company_id", $user->company_id)
                ->where("company_pay_methods.active", CompanyPayMethod::ACTIVE)
                ->where("company_pay_methods.pay_type", CompanyPayMethod::PAY_TYPE_STRIPE)
                ->select(
                    "company_pay_methods.id",
                    DB::raw("ifnull(card_zip_code_setting.proving,1) as proving"),
                    "company_pay_methods.secret"
                )
                ->first();
            if($user->emailChanged){
                $customer->save();
                $this->dispatch(new UpdateCustomerGroupInfo($user->email,$user->company_id));
            }
            if(isset($param['sc_id'])&&!empty($param['sc_id'])&&!empty($payInfo)){
                $stripeCustomer = StripeCustomer::where("customer_id","customer_".$customer_id)
                    ->first();
                if(empty($stripeCustomer)||$param['sc_id'] != $stripeCustomer->stripe_customer_id){
                    CreditCard::where('owner_id',$customer_id)
                        ->where('type',CreditCard::TYPE_CUSTOMER)
                        ->where('pay_method_id',$payInfo->id)
                        ->delete();
                    if(empty($stripeCustomer)){
                        StripeCustomer::create(
                            [
                                "customer_id"=>"customer_".$customer_id,
                                "stripe_customer_id"=>$param['sc_id']
                            ]);
                    }else{
                        StripeCustomer::where("customer_id","customer_".$customer_id)
                        ->update(
                            [
                                "stripe_customer_id"=>$param['sc_id']
                            ]);
                    }
                    PaymentAlgorithm::getPayment()->syncPayMethodCustomerCreditCard($payInfo,$customer_id,$param['sc_id']);
                }
            }
            $customer = $this->getCustomerInformation($customer_id, $user->company_id, $token, $type);
            return $customer;
        });
        return $result;
    }

    private function getCustomerInformation($customer_id, $company_id, $token, $type)
    {
        $customer = Customer::where('customers.id', $customer_id)
            ->leftjoin('users', 'customers.user_id', '=', 'users.id')
            ->leftjoin('companies', 'users.company_id', "=", "companies.id")
            ->leftjoin('customer_group_members',"customer_group_members.customer_id","=","customers.id")
            ->leftjoin('customer_groups',"customer_group_members.group_id","=","customer_groups.id")
            ->leftjoin('customer_group_binders',"customer_group_binders.id","=","customer_groups.bind_id")
            ->where('users.company_id', $company_id)
            ->select('customers.user_id as user_id', 'customers.id as customer_id',
                "customer_groups.name as group_name",
                "customer_group_binders.type as outer_type",
                "customer_group_binders.sort as group_sort",
                'users.first_name',
                'users.last_name',
                'users.email',
                'users.mobile',
                'users.address', 'users.lat', 'users.lng',
                'users.avatar_url',
                'users.gender',
                'users.lang',
                'users.timemode',
                DB::raw('unix_timestamp(users.updated_at) as updated_at'),
                'companies.id as company_id', 'companies.name as companies_name')
            ->first();
        if (empty($customer)) {
            throw new \Exception(ErrorCode::errorNotExist('customer'));
        } else {
            $stripeId = StripeCustomer::where("customer_id","customer_".$customer_id)
                ->count();
            $customer->bind_stripe = $stripeId>0 ? 1:0;
            $customer->avatar_url = UrlSpell::getUrlSpell()
                ->spellingAvatarUrl($customer->updated_at, $customer->avatar_url, $token, $customer_id, $type);

            return $customer;
        }
    }


//    public function deleteCustomer($customer_id)
//    {
//
//        $driver = Customer::where('customers.id', $customer_id)
//            ->leftjoin('users', 'customers.user_id', '=', 'users.id')
//            ->first();
//
//        if (empty($driver)) {
//            return ErrorCode::errorNotExist('customer');
//        }
//        try {
//            $this->removeCustomer($customer_id);
//            return ErrorCode::success('success');
//        } catch (\Exception $ex) {
//            return $ex->getMessage();
//        }
//    }


    public function companyDeleteCustomer(Request $request, $customer_id)
    {
        $company_id = $request->user->company_id;
        $customer = Customer::where('customers.id', $customer_id)
            ->leftjoin('users', 'customers.user_id', '=', 'users.id')
            ->first();

        if (empty($customer)) {
            return ErrorCode::errorNotExist('customer');
        }
        if ($company_id != $customer->company_id) {
            return ErrorCode::errorAdminUnauthorizedOperation();
        }

        try {
            $this->removeCustomer($customer_id, $company_id);
            return ErrorCode::success('success');
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    private function removeCustomer($customer_id, $company_id)
    {
        /**
         * 移除customer需要考虑与之相关的表同步移除,
         * 目前只有booking表有关,以后需要考虑到日程表
         * 1.禁止移除
         * TODO
         */
        DB::transaction(function () use ($customer_id, $company_id) {
            $booking = Booking::where('bookings.customer_id', $customer_id)
                ->whereRaw('bookings.appointed_at >= now()')
                ->leftjoin("orders", "bookings.id", "=", "orders.booking_id")
                ->whereIn("orders.order_state", [Order::ORDER_STATE_BOOKING, Order::ORDER_STATE_RUN, Order::ORDER_STATE_DONE, Order::ORDER_STATE_SETTLE_ERROR, Order::ORDER_STATE_WAIT_DETERMINE])
                ->where("orders.trip_state", "<", Order::TRIP_STATE_SETTLE_DONE)
                ->count();
            if ($booking > 0) {
                throw new \Exception(ErrorCode::errorCustomerDelete());
            }
            $customer = Customer::where('customers.id', $customer_id)->first();
            $driver = Driver::where("user_id", $customer->user_id)->count();
            $admin = Admin::where("user_id", $customer->user_id)->count();
            $superAdmin = Superadmin::where("user_id", $customer->user_id)->count();
            if ($driver == 0 && $admin == 0 && $superAdmin == 0) {
                User::where('id', $customer->user_id)->where("company_id", $company_id)->delete();
            }
            $customer->delete();
            $this->dispatch(new CustomerRemoveAllCreditCardJob($company_id, $customer_id));
        });
    }


    public function updateCustomersDeviceToken(Request $request, $device_token)
    {
//        $token = $request->user->token;
        $user_id = $request->user->id;
        $result = Customer::where('user_id', $user_id)->update(['device_token' => $device_token]);
        if ($result) {
            return ErrorCode::success('success');
        } else {
            return ErrorCode::errorDB();
        }
    }


    public function checkPaymentExistCustomerId(Request $request,$scId)
    {
        $companyId = $request->user->company_id;
        $payInfo = CompanyPayMethod::where('company_id', $companyId)
            ->where("active", CompanyPayMethod::ACTIVE)
            ->where("pay_type", CompanyPayMethod::PAY_TYPE_STRIPE)
            ->first();
        if (empty($payInfo)) {
            return ErrorCode::errorPayMethodStripe();
        }
        $customer = PaymentAlgorithm::getPayment()->getPayMethodExistCustomer($payInfo,$scId);
        if(is_null($customer)){
            return ErrorCode::errorGetExistCustomer();
        }
        return ErrorCode::success($customer);
    }


    public function getCustomerInfoDetail(Request $request)
    {
        $user = $request->user;
        $company_name = Company::where('id', $user->company_id)
            ->select('companies.name',
                'companies.phone1',
                'companies.phone2',
                'companies.email',
                'companies.country'
            )
            ->first();
        $user->company_name = $company_name->name;
        $user->company_phone1 = $company_name->phone1;
        $user->company_phone2 = $company_name->phone2;
        $user->company_email = $company_name->email;
        $user->company_country = $company_name->country;

        $user->avatar_url = UrlSpell::getUrlSpell()
            ->spellingAvatarUrl($user->updated_at, $user->avatar_url, $user->token, '', UrlSpell::mine);

        unset($user->web_token);
        return ErrorCode::success($user);
    }
}
