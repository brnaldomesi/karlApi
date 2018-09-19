<?php
/**
 * Created by PhpStorm.
 * User: lqh
 * Date: 2017/3/28
 * Time: 下午3:45
 */

namespace App\Method;


use App\ErrorCode;
use App\Model\Admin;
use App\Model\Driver;
use App\Model\Sale;
use App\Model\Superadmin;
use App\Model\User;
use Faker\Provider\Uuid;

class UserMethod
{
    public static function insertUserInfo($company_id, $param, $is_admin = false, $needToken = false, $isDriver = false)
    {
//        $username =             isset($param['username'])           ?$param['username']             :null;
        $password = isset($param['password']) ? $param['password'] : null;
        $first_name = isset($param['first_name']) ? $param['first_name'] : null;
        $last_name = isset($param['last_name']) ? $param['last_name'] : null;
        $mobile = isset($param['mobile']) ? $param['mobile'] : null;
        $email = isset($param['email']) ? $param['email'] : null;
        $address = isset($param['address']) ? $param['address'] : null;
        $lang = isset($param['lang']) ? $param['lang'] : null;
        $lat = isset($param['lat']) ? $param['lat'] : 0.00;
        $lng = isset($param['lng']) ? $param['lng'] : 0.00;
        $gender = isset($param['gender']) ? $param['gender'] : null;
        if (//is_null($username) ||
            is_null($password) ||
            is_null($company_id) ||
            is_null($first_name) ||
            is_null($last_name) ||
            is_null($mobile) ||
            is_null($email) ||
            is_null($gender)
        ) {
            throw new \Exception(ErrorCode::errorMissingParam(' in user'));
        } else {
        }
        if (!is_numeric($company_id) || $company_id <= 0) {
            throw new \Exception(ErrorCode::errorParam('company id'));
        }

//        if (empty($username)) {
//            throw new \Exception( ErrorCode::errorParam("param username is empty"));
//        } else {
//        }

        if (empty($first_name)) {
            throw new \Exception(ErrorCode::errorParam("param first_name is empty"));
        } else {
        }

        if (empty($last_name)) {
            throw new \Exception(ErrorCode::errorParam("param last_name is empty"));
        } else {
        }
        if (empty($gender) || !is_numeric($gender) || ($gender != 1 && $gender != 2)) {
            throw new \Exception(ErrorCode::errorParam("param last_name is empty"));
        } else {
        }

        if(empty($lang)){
            $lang = "en";
        }

        //验证密码格式是否正确
        if (!preg_match("/\w{6,16}$/", $password)) {
            throw new \Exception(ErrorCode::errorParam("Invalid password"));
        } else {
            $password = md5($password);
        }
        if (!MethodAlgorithm::mobileRegex($mobile)) {
            throw new \Exception(ErrorCode::errorParam("Invalid mobile"));
        }
        //验证邮箱格式是否正确
        if (!MethodAlgorithm::emailRegex($email)) {
            throw new \Exception(ErrorCode::errorParam("Invalid email"));
        } else {
        }
        if(!empty($address)){
            if(is_null($lat)||!is_numeric($lat)||
                $lat<-90 || $lat>90){
                throw new \Exception(ErrorCode::errorParam("lat"));
            }
            if(is_null($lng)||!is_numeric($lng)||
                $lng<-180 || $lng>180){
                throw new \Exception(ErrorCode::errorParam("lng"));
            }
        }else{
            $address="";
            $lat=0;
            $lng=0;
        }

        $username = Uuid::uuid();
        //如果是admin判断email、mobileusername是否和其他公司admin及superadmin重复
        if ($isDriver || $is_admin) {
            $user = Admin::leftjoin('users', 'admins.user_id', '=', 'users.id')
                ->where('users.username', $username)
                ->first();
            if (!empty($user)) {
                throw new \Exception(ErrorCode::errorRegisteredUsername());
            }
            //再判断admin email
            $user = Admin::leftjoin('users', 'admins.user_id', '=', 'users.id')
                ->where('users.email', $email)
                ->first();
            if (!empty($user)) {
                throw new \Exception(ErrorCode::errorRegisteredEmail());
            }
            //再判断admin mobile
            $user = Admin::leftjoin('users', 'admins.user_id', '=', 'users.id')
                ->where('users.mobile', $mobile)
                ->first();
            if (!empty($user)) {
                throw new \Exception(ErrorCode::errorRegisteredMobile());
            }
            //如果是admin在判断是否和sale重复
            if($is_admin){
                //判断sale email
                $user = Sale::leftjoin('users', 'sales.user_id', '=', 'users.id')
                    ->where('users.email', $email)
                    ->first();
                if (!empty($user)) {
                    throw new \Exception(ErrorCode::errorRegisteredEmail());
                }
                //再判断sale mobile
                $user = Sale::leftjoin('users', 'sales.user_id', '=', 'users.id')
                    ->where('users.mobile', $mobile)
                    ->first();
                if (!empty($user)) {
                    throw new \Exception(ErrorCode::errorRegisteredMobile());
                }
            }


            //再判断super admin mobile

            $user = Superadmin::leftjoin('users', 'superadmins.user_id', '=', 'users.id')
                ->where('users.username', $username)
                ->first();
            if (!empty($user)) {
                throw new \Exception(ErrorCode::errorRegisteredUsername());
            }
            //再判断super admin email
            $user = Superadmin::leftjoin('users', 'superadmins.user_id', '=', 'users.id')
                ->where('users.email', $email)
                ->first();
            if (!empty($user)) {
                throw new \Exception(ErrorCode::errorRegisteredEmail());
            }
            //再判断super admin mobile
            $user = Superadmin::leftjoin('users', 'superadmins.user_id', '=', 'users.id')
                ->where('users.mobile', $mobile)
                ->first();
            if (!empty($user)) {
                throw new \Exception(ErrorCode::errorRegisteredMobile());
            }

            //在全平台判断是否与其他公司的driver、admin的email、mobile、username是否重复
            $driver = Driver::leftjoin('users', 'users.id', '=', 'drivers.user_id')
                ->where('users.email', $email)->first();
            if (!empty($driver)) {
                throw new \Exception(ErrorCode::errorRegisteredEmail());
            }
            $driver = Driver::leftjoin('users', 'users.id', '=', 'drivers.user_id')
                ->where('users.username', $username)->first();
            if (!empty($driver)) {
                throw new \Exception(ErrorCode::errorRegisteredUsername());
            }
            $driver = Driver::leftjoin('users', 'users.id', '=', 'drivers.user_id')
                ->where('users.mobile', $mobile)->first();
            if (!empty($driver)) {
                throw new \Exception(ErrorCode::errorRegisteredMobile());
            }
        }
        $user = User::where('username', $username)->where('company_id', $company_id)->first();
        if (!empty($user)) {
            throw new \Exception(ErrorCode::errorRegisteredUsername());
        }
        $user = User::where('email', $email)->where('company_id', $company_id)->first();
        if (!empty($user)) {
            throw new \Exception(ErrorCode::errorRegisteredEmail());
        }
        $user = User::where('mobile', $mobile)->where('company_id', $company_id)->first();
        if (!empty($user)) {
            throw new \Exception(ErrorCode::errorRegisteredMobile());
        }
        $user = User::create([
            "first_name" => $first_name,
            "last_name" => $last_name, "company_id" => $company_id,
            "username" => $username,
            "mobile" => $mobile, "email" => $email,
            "password" => $password,
            "lang" => $lang,
            "gender" => $gender,
            "address" => $address,
            "lat" => $lat,
            "lng" => $lng,
        ]);
        if ($is_admin && $needToken) {
            $user->web_token = Uuid::uuid();
            $user->token_invalid_time = MethodAlgorithm::formatTimestampToDate(time() + 7200);
            $user->save();
        }
//        $customer = Customer::create([
//            'user_id' => $user->id
//        ]);
//        $user->customer_id = $customer->id;
        return $user;
    }

    public static function updateUserInfo($param, $isAdmin = false, $isDriver = false,$isSale=false)
    {
        $id = isset($param['id']) ? $param['id'] : null;
        $password = isset($param['pwd']) ? $param['pwd'] : null;
        $first_name = isset($param['first_name']) ? $param['first_name'] : null;
        $last_name = isset($param['last_name']) ? $param['last_name'] : null;
        $mobile = isset($param['mobile']) ? $param['mobile'] : null;
        $email = isset($param['email']) ? $param['email'] : null;
        $address = isset($param['address']) ? $param['address'] : null;
        $lat = isset($param['lat']) ? $param['lat'] : null;
        $lng = isset($param['lng']) ? $param['lng'] : null;
        $gender = isset($param['gender']) ? $param['gender'] : null;
        $lang = isset($param['lang']) ? $param['lang'] : null;
        $timemode = isset($param['timemode']) ? $param['timemode'] : 0;
        if (
        is_null($id)
        ) {
            throw new \Exception(ErrorCode::errorMissingParam());
        } else {
        }

        if (
            is_null($password) &&
            is_null($first_name) &&
            is_null($last_name) &&
            is_null($mobile) &&
            is_null($email) &&
            is_null($address) &&
            is_null($lang) &&
            is_null($gender)
        ) {
            throw new \Exception(ErrorCode::errorMissingParam());
        }

        $user = User::where('id', $id)->first();

        $user->timemode = $timemode;
        $emailNotSame =  false;
        if (empty($user)) {
            throw new \Exception(ErrorCode::errorNotExist('user'));
        }

        if (!is_null($first_name)) {
            if (empty($first_name)) {
                throw new \Exception(ErrorCode::errorParam('first name'));
            } else {
                $user->first_name = $first_name;
            }
        }

        if (!is_null($last_name) && !empty($last_name)) {
            if (empty($last_name)) {
                throw new \Exception(ErrorCode::errorParam('last name'));
            } else {
                $user->last_name = $last_name;
            }
        }
        if (!is_null($gender)) {
            if (!empty($gender) && is_numeric($gender) && ($gender == 1 || $gender == 2)) {
                $user->gender = $gender;
            } else {
                throw new \Exception(ErrorCode::errorParam($gender));
            }
        }

        if (!is_null($password)) {
            if (preg_match("/\S{6,16}$/", $password)) {
                $user->password = md5($password);
            } else {
                throw new \Exception(ErrorCode::errorParam('password'));
            }
        }


        if (!is_null($mobile)) {
//            echo "mobile is ".$mobile;
            if (!MethodAlgorithm::mobileRegex($mobile)) {
                throw new \Exception(ErrorCode::errorParam('mobile'));
            } else {
                if($isAdmin || $isDriver){
                    $driverCount = Driver::leftjoin('users', 'users.id', '=', 'drivers.user_id')
                        ->where('users.mobile', $mobile)
                        ->where('users.id', '!=', $user->id)->count();
                    if ($driverCount > 0) {
                        throw new \Exception(ErrorCode::errorMobileUserData());
                    }

                    $adminCount = Admin::leftjoin('users', 'users.id', '=', 'admins.user_id')
                        ->where('users.mobile', $mobile)
                        ->where('users.id', '!=', $user->id)->count();
                    if ($adminCount > 0) {
                        throw new \Exception(ErrorCode::errorMobileUserData());
                    }

                    if($isAdmin){
                        $saleCount = Sale::leftjoin('users', 'users.id', '=', 'sales.user_id')
                            ->where('users.mobile', $mobile)
                            ->where('users.id', '!=', $user->id)->count();
                        if ($saleCount > 0) {
                            throw new \Exception(ErrorCode::errorMobileUserData());
                        }
                    }
                }else{
                   if($isSale){
                       $adminCount = Admin::leftjoin('users', 'users.id', '=', 'admins.user_id')
                           ->where('users.mobile', $mobile)
                           ->where('users.id', '!=', $user->id)->count();
                       if ($adminCount > 0) {
                           throw new \Exception(ErrorCode::errorMobileUserData());
                       }
                   }
                }


                $userCount = User::where('mobile', $mobile)->where('company_id', $user->company_id)
                    ->where('id', '!=', $user->id)->count();
                if ($userCount > 0) {
                    throw new \Exception(ErrorCode::errorMobileUserData());
                }
                $user->mobile = $mobile;
            }
        }
        if (!is_null($email)) {
            if (!MethodAlgorithm::emailRegex($email)) {
                throw new \Exception(ErrorCode::errorParam('email'));
            } else {
                if ($isAdmin || $isDriver) {
                    $adminCount = Admin::leftjoin('users', 'users.id', '=', 'admins.user_id')
                        ->where('users.email', $email)
                        ->where('users.id', '!=', $user->id)->count();
                    if ($adminCount > 0) {
                        throw new \Exception(ErrorCode::errorEmailUserData());
                    }
                    if($isAdmin){
                        $saleCount = Sale::leftjoin('users', 'users.id', '=', 'sales.user_id')
                            ->where('users.email', $email)
                            ->where('users.id', '!=', $user->id)->count();
                        if ($saleCount > 0) {
                            throw new \Exception(ErrorCode::errorEmailUserData());
                        }
                    }
                    $driverCount = Driver::leftjoin('users', 'users.id', '=', 'drivers.user_id')
                        ->where('users.email', $email)
                        ->where('users.id', '!=', $user->id)->count();
                    if ($driverCount > 0) {
                        throw new \Exception(ErrorCode::errorEmailUserData());
                    }
                }else{
                    if($isSale){
                        $adminCount = Admin::leftjoin('users', 'users.id', '=', 'admins.user_id')
                            ->where('users.email', $email)
                            ->where('users.id', '!=', $user->id)->count();
                        if ($adminCount > 0) {
                            throw new \Exception(ErrorCode::errorEmailUserData());
                        }

                    }

                    $emailNotSame = $user->email != $email;
                }


                $userCount = User::where('email', $email)->where('company_id', $user->company_id)
                    ->where('id', '!=', $user->id)->count();
                if ($userCount > 0) {
                    throw new \Exception(ErrorCode::errorEmailUserData());
                }
                $user->email = $email;
            }
        }

        if (!is_null($address)) {
            if(empty($address)){
                $user->address = $address;
                $user->lat = 0.00;
                $user->lng = 0.00;
            }else{
                if(is_null($lat)||!is_numeric($lat)||
                    $lat<-90 || $lat>90){
                    throw new \Exception(ErrorCode::errorParam("lat"));
                }
                if(is_null($lng)||!is_numeric($lng)||
                    $lng<-180 || $lng>180){
                    throw new \Exception(ErrorCode::errorParam("lng"));
                }
                $user->lng=$lng;
                $user->lat=$lat;
                $user->address = $address;

            }
        }
        if(!is_null($lang)){
            if(empty($lang)){
                throw new \Exception(ErrorCode::errorParam('email'));
            }else{
                $user->lang=$lang;
            }
        }
        $user->save();
        $user->emailChanged = $emailNotSame;
        return $user;
    }

}