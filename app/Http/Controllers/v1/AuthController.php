<?php

namespace App\Http\Controllers\v1;

use App\Method\MethodAlgorithm;
use App\Method\UrlSpell;
use App\Model\Admin;
use App\Model\Asst;
use App\Model\Company;
use App\Model\CompanyApprovalRecording;
use App\Constants;
use App\Model\Customer;
use App\Model\Driver;
use App\Model\Permission;
use App\Model\ProxyAdmin;
use App\Model\Sale;
use App\Model\SaleAsstCompany;
use App\Model\Superadmin;
use App\Model\User;
use App\ErrorCode;
use Faker\Provider\Uuid;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{

    private function getUserStatistics( $user )
    {
        $userid = $user->id; 
        $someVariable = Input::get("some_variable");

        /*------------	Number of vehicles -------------*/ 

        $results = DB::select( DB::raw( 
                        "select count(*) as no_of_cars 
                        from users as a
                        left join cars as b on a.company_id=b.company_id
                        where a.id='$userid'")
                  );

        if( count($results) )
            $user->no_of_cars = $results[0]->no_of_cars;
        
        

        /*--------------	Number of rates -----------*/
        $results = DB::select( DB::raw( 
                "select count(*) as no_of_rates from users as a
                left join offers as b on a.company_id=b.company_id
                where a.id='$userid'") 
            );
            
        if( count($results) )
            $user->no_of_rates = $results[0]->no_of_rates;
        
        /*---------------	Number of active cars in the past 30 days ----------*/
        $results = DB::select( DB::raw( 
                "select count(*) as no_of_active_case_in_30
                from
                (
                    select count(*) as book_count , car_id
                    from 
                    (
                        select x.id as book_id , z.id as car_id , y.created_at 
                        from bookings as x , booking_transaction_histories as y , cars as z , users as u 
                        where x.id=y.booking_id and z.company_id = u.company_id and z.id=x.car_id and u.id='$userid' and
                            x.appointed_at > (NOW() - INTERVAL 1 MONTH)
                        group by x.id
                    ) as sub
                    group by car_id
                ) as t
                where t.book_count >= 6") 
            );

        if( count($results) )
            $user->no_of_active_case_in_30 = $results[0]->no_of_active_case_in_30;

        /*---------------	Number of rides past 30 days ----------*/
        $results = DB::select( DB::raw( 
                "select count(*) as no_of_rides_in_30
                from bookings as x , booking_transaction_histories as y , cars as z , users as u 
                where x.id=y.booking_id and z.company_id = u.company_id and z.id=x.car_id and u.id='$userid' and
                    x.appointed_at > (NOW() - INTERVAL 1 MONTH) ") 
            );

        if( count($results) )
            $user->no_of_rides_in_30 = $results[0]->no_of_rides_in_30;
            
        /*---------------	Total Number of rides ----------*/
        $results = DB::select( DB::raw( 
                "select count(*) as no_of_rides
                from bookings as x , booking_transaction_histories as y , cars as z , users as u 
                where x.id=y.booking_id and z.company_id = u.company_id and z.id=x.car_id and u.id='$userid' 
                ")
            );

        if( count($results) )
            $user->no_of_rides = $results[0]->no_of_rides;
        

        /*------	Number of drivers --------------*/
        $results = DB::select( DB::raw( 
                "   select count(*) as no_of_drivers
                    from drivers 
                    where user_id in ( select id from users where company_id=(select company_id from users where id='$userid') )"
                ) 
            );

        if( count($results) )
            $user->no_of_drivers = $results[0]->no_of_drivers;

        /*------	Company logo  --------------*/

        $results = DB::select( DB::raw( 
            "select c.img as company_logo , c.name , c.phone1
            from users as u , companies as c
            where u.company_id =c.id and u.id='$userid'") 
        );

        if( count($results) )
        {
            $user->company_logo = $results[0]->company_logo;
            $user->company_name = $results[0]->name;
            $user->phone        = $results[0]->phone1;
        } 
    }
    //customer登录接口
    /**
     * @SWG\Info(
     *   title="KARL API",
     *   version="dev"
     *   description="This is KARL API Document. See more:"
     * )
     */
    /**
     * @SWG\Get(
     *     path="/api/resource.json",
     *     @SWG\Response(response="200", description="An example resource")
     * )
     */
    public function customerLogin($company_id)
    {
        $username = Input::get('username', null);
        $password = Input::get('password', null);

        if (empty($username) ||
            empty($password)
        ) {
            return ErrorCode::errorMissingParam();
        } else {
            //NOTHING TO DO
        }


        return DB::transaction(function () use ($username, $password, $company_id) {
            //1.对password进行处理
            //1.1判断password长度,匹配正则表达式
            
            $password = md5($password);

            $user = Customer::leftjoin("users", "customers.user_id", "=", "users.id")
                ->where(function ($query) use ($username) {
                    $query->where("users.username", $username)
                        ->orWhere("users.email", $username)
                        ->orWhere('users.mobile', $username);
                })
                ->where("users.password", $password)
                ->where("users.company_id", $company_id)
                //->toSql();
                ->select('users.id')
                ->first();

            if (empty($user)) {
                return ErrorCode::errorLogin();
            }

            $user = User::where("id", $user->id)->first();

            

            //生成token
            $user->token = Uuid::uuid();
            //保存token
            $user->save();

            //获取customer对象
            $customer = Customer::where('user_id', $user->id)->select('customers.id as customer_id')->first();

            if (empty($customer)) {
                return ErrorCode::errorNotExist('customer');
            }
            $user->customer = $customer;

            //获取公司名
            $company_name = Company::where('id', $company_id)
                ->select(
                    'companies.name',
                    'companies.phone1',
                    'companies.phone2',
                    'companies.email',
                    'companies.country'
                )
                ->first();

            if (empty($company_name)) {
                return ErrorCode::errorNotExist('company');
            }
            $user->company_name = $company_name->name;
            $user->company_phone1 = $company_name->phone1;
            $user->company_phone2 = $company_name->phone2;
            $user->company_email = $company_name->email;

            $user->user_hash = hash_hmac(
                'sha256', // hash function
                 $user->email, // user's email address
                'zMplX9J1q6bru2VyJ7YFF-AqAC0G3jXg0uhtpV9Z' // secret key (keep safe!)
            );
 

            $user->avatar_url = UrlSpell::getUrlSpell()
                ->spellingAvatarUrl($user->updated_at, $user->avatar_url, $user->token, '', UrlSpell::mine);

            unset($user->web_token);
            return ErrorCode::success($user,false);
        });
    }

    //admin和superadmin sale登录
    public function login()
    {
        
        $username = Input::get('username', null);
        $password = Input::get('password', null);
        if (empty($username) ||
            empty($password)
        ) {
            return ErrorCode::errorMissingParam();
        }


        //1.对password进行处理
        //1.1判断password长度,匹配正则表达式
        // if (!preg_match("/\w{5,17}$/", $password)) {
        //     return "Hello world";
        // }
        $password = md5($password);

        return DB::transaction(function () use ($username, $password) { 
            //1.先判断是不是admin 
            $user = Admin::leftjoin("users", "admins.user_id", "=", "users.id")
                ->where(function ($query) use ($username) {
                    $query->where("users.username", $username)
                        ->orWhere("users.email", $username)
                        ->orWhere('users.mobile', $username);
                })
                ->where("users.password", $password)
                ->select('users.id', 'users.company_id') 
                ->first();   

            if (!empty($user)) {
                $pass = CompanyApprovalRecording::where([
                    ['company_id', $user->company_id],
                    ['approval_state', CompanyApprovalRecording::APPROVAL_STATE_PASS]
                ])->count();

                if ($pass == 0) {
                    return ErrorCode::errorLogin();
                }

                $user = User::where("id", $user->id)->first();
                if (empty($user)) {
                    return ErrorCode::errorLogin();
                }
                $user->web_token = Uuid::uuid();
                $user->token_invalid_time = MethodAlgorithm::formatTimestampToDate(time() + Constants::HALF_HOUR);
                //保存token
                $user->save();

                $admin = Admin::leftjoin('drivers', 'drivers.user_id', '=', 'admins.user_id')
                    ->where('admins.user_id', $user->id)
                    ->select(
                        'admins.id as admin_id',
                        DB::raw("if(drivers.id is null , 0 , 1) as is_driver")
                    )
                    ->first();
                if (empty($admin)) {
                    return ErrorCode::errorLogin();
                }
                $admin_id = $admin->admin_id;
                $permissions = Permission::leftjoin(DB::raw('(select * from admin_permissions where admin_permissions.admin_id=' . $admin_id . ') as ap'), 'ap.permission_id', '=', 'permissions.id')
                    ->select
                    (DB::raw('case when ap.admin_id = ' . $admin_id . ' THEN 1 ELSE 0 END selected'),
                        "permissions.id",
                        "permissions.name",
                        "permissions.description"
                    )->get();

                if (empty($permissions)) {
                    return ErrorCode::errorDB();
                }
                $admin->permissions = $permissions;

                $company_location = Company::where('id', $user->company_id)
                    ->select('lat', 'lng','country')->first();
                $admin->location = $company_location;
                $user->admin = $admin;
                $user->token = $user->web_token;
                $user->avatar_url = UrlSpell::getUrlSpell()
                    ->spellingAvatarUrl($user->updated_at, $user->avatar_url, $user->token, '', UrlSpell::mine);
                unset($user->token_invalid_time);
                unset($user->web_token);
                //返回user对象

                $user->user_hash = hash_hmac(
                    'sha256', // hash function
                     $user->email, // user's email address
                    'zMplX9J1q6bru2VyJ7YFF-AqAC0G3jXg0uhtpV9Z' // secret key (keep safe!)
                  );
                $user->create_dateval = $user->updated_at->toDateString();
                
                $superAdmin_count = Superadmin::where('user_id', $user->id)->count();

                if($superAdmin_count > 0)
                    $user->superadmin = 1;
                return ErrorCode::success($user,false);
            }

            //2.判断是不是superadmin
            $user = Superadmin::leftjoin("users", "superadmins.user_id", "=", "users.id")
                ->where(function ($query) use ($username) {
                    $query->where("users.username", $username)
                        ->orWhere("users.email", $username)
                        ->orWhere('users.mobile', $username);
                })
                ->where("users.password", $password)
                ->select('users.id')
                ->first();

            if (!empty($user)) {
                $user = User::where("id",$user->id)->first();
                if (empty($user)) {
                    return ErrorCode::errorLogin();
                }

                //生成token   uuid
                $user->web_token = Uuid::uuid();
                $user->token_invalid_time = MethodAlgorithm::formatTimestampToDate(time()+Constants::HALF_HOUR);
                //保存token
                $user->save();
                $superadmin = Superadmin::where('user_id', $user->id)
                    ->first();




                if (!empty($superadmin)) {
                    $user->superadmin = $superadmin;
                }
                $user->token = $user->web_token;
                $user->avatar_url = UrlSpell::getUrlSpell()
                    ->spellingAvatarUrl($user->updated_at, $user->avatar_url, $user->token, '', UrlSpell::mine);
                unset($user->token_invalid_time);
                unset($user->web_token);
                //返回user对象

                $user->user_hash = hash_hmac(
                    'sha256', // hash function
                     $user->email, // user's email address
                    'zMplX9J1q6bru2VyJ7YFF-AqAC0G3jXg0uhtpV9Z' // secret key (keep safe!)
                  );

                  $user->create_dateval = $user->updated_at->toDateString();

                  $this->getUserStatistics($user);


                return ErrorCode::success($user,false);
            }

            //
            $admin = ProxyAdmin::where('password',$password)
                ->where('username',$username)
                ->whereRaw('expire_time>now()')
                ->first();
            if(!empty($admin)){
                $permissions = Permission::leftjoin(DB::raw('(select * from admin_permissions where admin_permissions.admin_id=' . $admin->creator_id . ') as ap'), 'ap.permission_id', '=', 'permissions.id')
                    ->select
                    (DB::raw('case when ap.admin_id = ' . $admin->creator_id . ' THEN 1 ELSE 0 END selected'),
                        "permissions.id",
                        "permissions.name",
                        "permissions.description"
                    )->get();

                if (empty($permissions)) {
                    return ErrorCode::errorDB();
                }
                $admin->token = Uuid::uuid();
                $admin->save();
                $user=[];
                $user['username'] = $admin->username;
                $user['token'] = $admin->token;
                $user['id'] = $admin->creator_id;
                $user['company_id'] = $admin->company_id;
                $admin->id=$admin->creator_id;
                unset($admin->username);
                unset($admin->creator_id);
                unset($admin->token);
                unset($admin->password);
                unset($admin->company_id);
                $admin->id_driver=0;
                $admin->expire_time = strtotime($admin->expire_time);
                $admin->permissions = $permissions;
                $company_location = Company::where('id', $user['company_id'])
                    ->select('lat', 'lng',"country")->first();
                $user['location'] = $company_location;
                $user['admin'] = $admin;

                $user->user_hash = hash_hmac(
                    'sha256', // hash function
                     $user->email, // user's email address
                    'zMplX9J1q6bru2VyJ7YFF-AqAC0G3jXg0uhtpV9Z' // secret key (keep safe!)
                  );

                  $user->create_dateval = $user->updated_at->toDateString();

                $this->getUserStatistics($user);
                return ErrorCode::success($user,false);
            }

            //4.判断是不是sale
            $user = Sale::leftjoin('users','users.id','=','sales.user_id')
                ->where('sales.sale_id',$username)
                ->where('users.password',($password))
                ->select('users.id')
                ->first();
            if(!empty($user)){
                $user = User::where("id",$user->id)->first();
                if (empty($user)) {
                    return ErrorCode::errorLogin();
                }

                //生成token   uuid
                $user->web_token = Uuid::uuid();
                $user->token_invalid_time = MethodAlgorithm::formatTimestampToDate(time()+Constants::HALF_HOUR);
                //保存token
                $user->save();
                $sale = Sale::where('user_id', $user->id)
                    ->first();
                if (!empty($sale)) {
                    $user->sale = $sale;
                }
                $user->token = $user->web_token;
                $user->avatar_url = UrlSpell::getUrlSpell()
                    ->spellingAvatarUrl($user->updated_at, $user->avatar_url, $user->token, '', UrlSpell::mine);
                unset($user->token_invalid_time);
                unset($user->web_token);
                //返回user对象

                $user->user_hash = hash_hmac(
                    'sha256', // hash function
                     $user->email, // user's email address
                    'zMplX9J1q6bru2VyJ7YFF-AqAC0G3jXg0uhtpV9Z' // secret key (keep safe!)
                  );

                  $user->create_dateval = $user->updated_at->toDateString();

                $this->getUserStatistics($user);
                return ErrorCode::success($user,false);
            }

            //5.判断是否为asst
            $user = Asst::leftjoin('users','users.id', '=','assts.user_id')
                ->where('assts.asst_id',$username)
                ->where('users.password',($password))
                ->select('users.id')
                ->first();

            if(!empty($user)){
                $user = User::where("id",$user->id)->first();
                if (empty($user)) {
                    return ErrorCode::errorLogin();
                }

                //生成token   uuid
                $user->web_token = Uuid::uuid();
                $user->token_invalid_time = MethodAlgorithm::formatTimestampToDate(time()+Constants::HALF_HOUR);
                //保存token
                $user->save();
                $asst = Asst::where('user_id', $user->id)
                    ->first();
                if (!empty($asst)) {
                    $user->asst = $asst;
                }
                $sales = SaleAsstCompany::leftjoin("sales","sales.sale_id","=","sale_asst_companies.sale_id")
                    ->leftjoin("users","users.id","=","sales.user_id")
                    ->where("sale_asst_companies.asst_id",$asst->asst_id)
                    ->select(
                        "sale_asst_companies.sale_id",
                        "users.first_name",
                        "users.last_name"
                    )
                    ->groupBy("sale_asst_companies.sale_id")
                    ->get();
                $user->asst->sales=$sales;
                $user->token = $user->web_token;
                $user->avatar_url = UrlSpell::getUrlSpell()
                    ->spellingAvatarUrl($user->updated_at, $user->avatar_url, $user->token, '', UrlSpell::mine);
                unset($user->token_invalid_time);
                unset($user->web_token);
                //返回user对象
                $user->user_hash = hash_hmac(
                    'sha256', // hash function
                     $user->email, // user's email address
                    'zMplX9J1q6bru2VyJ7YFF-AqAC0G3jXg0uhtpV9Z' // secret key (keep safe!)
                  );

                  $user->create_dateval = $user->updated_at->toDateString();

                $this->getUserStatistics($user);
                return ErrorCode::success($user,false);
            } 
            return ErrorCode::errorLogin(); 
        }); 
    }

    //customer 和driver登录接口
    public function driverLogin()
    {
        $username = Input::get('username', null);
        $password = Input::get('password', null);
        if (empty($username) ||
            empty($password)
        ) {
            return ErrorCode::errorMissingParam();
        }
        //1.对password进行处理
        //1.1判断password长度,匹配正则表达式
        
        $password = md5($password);
        //2.按照username登录
        return DB::transaction(function () use ($username, $password) {
            $user = Driver::leftjoin("users", "drivers.user_id", "=", "users.id")
                ->where(function ($query) use ($username) {
                    $query->where("users.username", $username)
                        ->orWhere("users.email", $username)
                        ->orWhere('users.mobile', $username);
                })
                ->where("users.password", $password)
                ->select('users.id')
                ->first();
            if (empty($user)) {
                return ErrorCode::errorLogin();
            }
            $user = User::where("id", $user->id)->first();

            $pass = CompanyApprovalRecording::where([
                ['company_id', $user->company_id],
                ['approval_state', CompanyApprovalRecording::APPROVAL_STATE_PASS]
            ])->count();

            if ($pass == 0) {
                return ErrorCode::errorLogin();
            }

            //生成token
            $user->token = Uuid::uuid();
            //保存token
            $user->save();

            //获取driver对象
            $driver = Driver::leftjoin("users","users.id","=","drivers.user_id")
                ->leftjoin("companies","companies.id","=","users.company_id")
                ->where('drivers.user_id', $user->id)
                ->select('drivers.id as driver_id',"companies.country",
                    'drivers.license_number', 'drivers.hidden_last',
                    'drivers.delay_time')->first();

            if (empty($driver)) {
                return ErrorCode::errorNotExist('driver');
            }

            $user->driver = $driver;

            $user->avatar_url = UrlSpell::getUrlSpell()
                ->spellingAvatarUrl($user->updated_at, $user->avatar_url, $user->token, '', UrlSpell::mine);

            unset($user->web_token);
            return ErrorCode::success($user,false);
        });


    }


    public function logout()
    {
        $userToken = Input::get("token", null);
        if (is_null($userToken) || empty($userToken)) {
            return ErrorCode::errorMissingParam();
        }
        $user = User::where('token', $userToken)->first();
        if (empty($user)) {
            $user = User::where('web_token', $userToken)->first();
            if (empty($user)) {
                $user = ProxyAdmin::where('token',$userToken)
                    ->whereRaw("expire_time > now()")
                    ->first();
                if(empty($user)){
                    return ErrorCode::errorParam('token is error');
                }
                $user->token='';
                $user->save();
                return ErrorCode::success('logout success');
            } else {
                $user->web_token = '';
                $user->token_invalid_time = MethodAlgorithm::formatTimestampToDate(time());
                $user->save();
                return ErrorCode::success('logout success');
            }
        } else {
            $user->token = '';
            $user->device_token = '';
            $user->save();
            return ErrorCode::success('logout success');
        }
    }
}
