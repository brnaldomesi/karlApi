<?php

namespace App\Http\Controllers\v1;

use App\ErrorCode;
use App\Jobs\SendEmailResetAdminPasswordJob;
use App\Jobs\SendEmailResetAsstPasswordJob;
use App\Jobs\SendEmailResetPassengerPasswordJob;
use App\Jobs\SendEmailResetDriverPasswordJob;
use App\Jobs\SendEmailResetSalePasswordJob;
use App\Method\MethodAlgorithm;
use App\Method\UrlSpell;
use App\Model\Admin;
use App\Model\Asst;
use App\Model\Customer;
use App\Model\Driver;
use App\Jobs\SendEmailResetPasswordJob;
use App\Model\Sale;
use App\Model\Superadmin;
use App\Model\User;
use Faker\Provider\Uuid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

class UsersController extends Controller
{

    public function changePassword(Request $request)
    {
        $token = Input::get('token', null);
        $oldPwd = Input::get('old_pwd', null);
        $newPwd = Input::get('new_pwd', null);
        $user = User::where([
            ['password', md5($oldPwd)],
            ['token', $token]
        ])->first();
        if (empty($user)) {
            $user = User::where([
                ['password', md5($oldPwd)],
                ['web_token', $token]
            ])->first();
        }

        if (empty($user)) {
            return ErrorCode::errorChangePassword();
        }


        if (preg_match("/\w{6,16}$/", $newPwd)) {
            if (md5($newPwd) == $user->password) {
                return ErrorCode::errorPasswordNotChanged();
            }
            $user->password = md5($newPwd);
        } else {
            return ErrorCode::errorParam('new password');
        }
        $user->save();
        $user->avatar_url = UrlSpell::getUrlSpell()
            ->spellingAvatarUrl($user->updated_at, $user->avatar_url, $user->token, '', UrlSpell::mine);

        return ErrorCode::success($user);
    }

    public function updateUsersDeviceToken(Request $request, $device_token)
    {
        $token = $request->user->token;
        $result = User::where('token', $token)->update(['device_token' => $device_token]);
        if ($result) {
            return ErrorCode::success('success');
        } else {
            return ErrorCode::errorDB();
        }
    }


    public function findCustomerPassword($company_id)
    {
        $email = Input::get('email', null);
        if (!MethodAlgorithm::emailRegex($email)) {
            return ErrorCode::errorParam('email');
        }
        $user = Customer::leftJoin('users', 'customers.user_id', '=', 'users.id')
            ->where([['users.company_id', $company_id], ['users.email', $email]])
            ->frist();
        if (empty($user)) {
            return ErrorCode::errorNotExist('email');
        }
        $this->dispatch(new SendEmailResetPasswordJob($user));
        return ErrorCode::success('success');
    }

    public function findAdminPassword()
    {
        $email = Input::get('email', null);
        if (!MethodAlgorithm::emailRegex($email)) {
            return ErrorCode::errorParam('email');
        }
        $user = Admin::leftJoin('users', 'admins.user_id', '=', 'users.id')
            ->where(['users.email', $email])
            ->frist();
        if (empty($user)) {
            return ErrorCode::errorNotExist('email');
        }
        $this->dispatch(new SendEmailResetPasswordJob($user));
        return ErrorCode::success('success');
    }

    public function findDriverPassword()
    {
        $email = Input::get('email', null);
        if (!MethodAlgorithm::emailRegex($email)) {
            return ErrorCode::errorParam('email');
        }
        $user = Driver::leftJoin('users', 'drivers.user_id', '=', 'users.id')
            ->where(['users.email', $email])
            ->frist();
        if (empty($user)) {
            return ErrorCode::errorNotExist('email');
        }
        $this->dispatch(new SendEmailResetPasswordJob($user));
        return ErrorCode::success('success');
    }

    public function resetCustomerPassword($company_id)
    {
        $email = Input::get('email', null);
        $code = Input::get('code', null);
        $password = Input::get('password', null);
        if (is_null($email) || is_null($code) || is_null($password)) {
            return ErrorCode::errorMissingParam();
        }
        if (!MethodAlgorithm::emailRegex($email)) {
            return ErrorCode::errorParam('email');
        }
        if (empty($code)) {
            return ErrorCode::errorParam('code');
        }
        $userId = Customer::leftjoin('users', 'customers.user_id', '=', 'users.id')
            ->where([['users.company_id', $company_id], ['users.email', $email], ['users.code', $code]])
            ->select('users.id')->frist();
        if (empty($userId)) {
            return ErrorCode::errorParam('code');
        }

        $user = User::where('id', $userId->id)->frist();
        if (!preg_match("/\w{6,16}$/", $password)) {
            throw new \Exception(ErrorCode::errorParam("Invalid password"));
        } else {
            $password = md5($password);
        }
        $user->password = $password;
        $user->save();
        $this->dispatch(new SendEmailResetPasswordJob($email));
        return ErrorCode::success('success');
    }

    public function resetDriverPassword()
    {
        $email = Input::get('email', null);
        $code = Input::get('code', null);
        $password = Input::get('password', null);
        if (is_null($email) || is_null($code) || is_null($password)) {
            return ErrorCode::errorMissingParam();
        }
        if (!MethodAlgorithm::emailRegex($email)) {
            return ErrorCode::errorParam('email');
        }
        if (empty($code)) {
            return ErrorCode::errorParam('code');
        }
        $userId = Driver::leftjoin('users', 'driver.user_id', '=', 'users.id')
            ->where([['users.email', $email], ['users.code', $code]])
            ->select('users.id')->frist();
        if (empty($userId)) {
            return ErrorCode::errorParam('code');
        }

        $user = User::where('id', $userId->id)->frist();
        if (!preg_match("/\w{6,16}$/", $password)) {
            throw new \Exception(ErrorCode::errorParam("Invalid password"));
        } else {
            $password = md5($password);
        }
        $user->password = $password;
        $user->save();
        return ErrorCode::success('success');
    }

    public function resetAdminPassword()
    {
        $email = Input::get('email', null);
        $code = Input::get('code', null);
        $password = Input::get('password', null);
        if (is_null($email) || is_null($code) || is_null($password)) {
            return ErrorCode::errorMissingParam();
        }
        if (!MethodAlgorithm::emailRegex($email)) {
            return ErrorCode::errorParam('email');
        }
        if (empty($code)) {
            return ErrorCode::errorParam('code');
        }
        $userId = Admin::leftjoin('users', 'admins.user_id', '=', 'users.id')
            ->where([['users.email', $email], ['users.code', $code]])
            ->select('users.id')->frist();
        if (empty($userId)) {
            return ErrorCode::errorParam('code');
        }

        $user = User::where('id', $userId->id)->frist();
        if (!preg_match("/\w{6,16}$/", $password)) {
            throw new \Exception(ErrorCode::errorParam("Invalid password"));
        } else {
            $password = md5($password);
        }
        $user->password = $password;
        $user->save();
        return ErrorCode::success('success');
    }

    public function getCustomerTemplatePassword($company_id)
    {
        $email = Input::get('email', null);
        if (!MethodAlgorithm::emailRegex($email)) {
            return ErrorCode::errorParam('email');
        }
        return DB::transaction(function () use ($company_id, $email) {
            $customer = Customer::leftjoin("users", "customers.user_id", "=", "users.id")
                ->where('users.email', $email)
                ->where('users.company_id', $company_id)
                ->first();
            if (empty($customer)) {
                return ErrorCode::errorNotExist('email');
            }
            $password = MethodAlgorithm::getRandomPassword();
            User::where("id", $customer->user_id)->update(["password" => md5($password)]);
            $this->dispatch(new SendEmailResetPassengerPasswordJob($email, $password, $company_id));
            return ErrorCode::success('success');
        });
    }

    public function getAdminTemplatePassword()
    {
        $email = Input::get('email', null);
        if (!MethodAlgorithm::emailRegex($email)) {
            return ErrorCode::errorParam('email');
        }
        $admin = Admin::leftjoin("users", "users.id", "=", "admins.user_id")
            ->where('users.email', $email)
            ->first();
        if (!empty($admin)) {
            $password = MethodAlgorithm::getRandomPassword();
            User::where("id", $admin->user_id)->update(["password" => md5($password)]);
            $this->dispatch(new SendEmailResetAdminPasswordJob($email, $password, $admin->lang));
            return ErrorCode::success('success');
        }
        $sale = Sale::leftjoin("users", "users.id", "=", "sales.user_id")
            ->where('users.email', $email)
            ->where("users.company_id", 0)
            ->first();
        if (!empty($sale)) {
            $password = MethodAlgorithm::getRandomPassword();
            User::where("id", $sale->user_id)->update(["password" => md5($password)]);
            $this->dispatch(new SendEmailResetSalePasswordJob($email, $password));
            return ErrorCode::success('success');
        }
        $asst = Asst::leftjoin("users", "users.id", "=", "assts.user_id")
            ->where('users.email', $email)
            ->where("users.company_id", 0)
            ->first();
        if (!empty($asst)) {
            $password = MethodAlgorithm::getRandomPassword();
            User::where("id", $asst->user_id)->update(["password" => md5($password)]);
            $this->dispatch(new SendEmailResetAsstPasswordJob($email, $password));
            return ErrorCode::success('success');
        }
        return ErrorCode::errorNotExist('email');
    }

    public function getSalesTemplatePassword()
    {
        $email = Input::get('email', null);
        if (!MethodAlgorithm::emailRegex($email)) {
            return ErrorCode::errorParam('email');
        }
        $sale = Sale::leftjoin("users", "users.id", "=", "admins.user_id")
            ->where('users.email', $email)
            ->where('users.company_id', 0)
            ->first();
        if (empty($sale)) {
            return ErrorCode::errorNotExist('email');
        }
        $password = MethodAlgorithm::getRandomPassword();
        User::where("id", $sale->user_id)->update(["password" => md5($password)]);
        $this->dispatch(new SendEmailResetSalePasswordJob($email, $password));
        return ErrorCode::success('success');
    }

    public function getDriverTemplatePassword()
    {
        $email = Input::get('email', null);
        if (!MethodAlgorithm::emailRegex($email)) {
            return ErrorCode::errorParam('email');
        }

        try {
            return DB::transaction(function () use ($email) {

                $driver = Driver::leftjoin("users", "drivers.user_id", "=", "users.id")
                    ->where('users.email', $email)
                    ->select('users.id as user_id', 'users.company_id as company_id')
                    ->first();

                if (empty($driver)) {
                    return ErrorCode::errorNotExist('email');
                }

                $password = MethodAlgorithm::getRandomPassword();

                User::where('id', $driver->user_id)->update(['password' => md5($password)]);

                $this->dispatch(new SendEmailResetDriverPasswordJob($email, $password, $driver->company_id));

                return ErrorCode::success('success');
            });
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }
}