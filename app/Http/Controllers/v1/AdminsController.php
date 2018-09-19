<?php

namespace App\Http\Controllers\v1;

use App\Constants;
use App\ErrorCode;
use App\Method\UrlSpell;
use App\Method\UserMethod;
use App\Model\Admin;
use App\Model\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

class AdminsController extends Controller
{

    public function admins()
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
        $admins =
            Admin::leftjoin('users', 'admins.user_id', '=', 'users.id')
                ->leftjoin('companies', 'users.company_id', "=", "companies.id")
                ->select('admins.id as admin_id', 'users.first_name as first_name',
                    'users.last_name as last_name', 'admins.user_id as user_id',
                    'companies.id as company_id', 'companies.name as companies_name')
                ->skip($skip)
                ->take($per_page)
                ->get();
        $result = array();
        $result['total'] = Admin::leftjoin('users', 'admins.user_id', '=', 'users.id')
            ->count();
        if (empty($admins)) {
            return ErrorCode::successEmptyResult('there is no admins in DB');
        } else {
            $result['customers'] = $admins;
            return ErrorCode::success($result);
        }
    }

    public function createAdmin($company_id)
    {
        $param = Input::get('param', null);
        $token = Input::get('token', null);
        if (is_null($token) || empty($token)) {
            return ErrorCode::errorMissingParam();
        }
        if (is_null($param) || empty($param)) {
            return ErrorCode::errorMissingParam();
        }
        try {
            $result = DB::transaction(function () use ($company_id, $param, $token) {
                $user = UserMethod::insertUserInfo($company_id, $param, true, false, true);
                $admin = Admin::create(["user_id" => $user->id]);
                return $this->getAdminDetailById($admin->id, $token);
            });
            return ErrorCode::success($result);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function companyUpdateAdminInfo(Request $request)
    {
        $admin_id = $request->user->admin->id;
        $param = Input::get('param', null);
        $token = Input::get('token', null);
        if (is_null($token) || empty($token)) {
            return ErrorCode::errorMissingParam();
        }
        if (is_null($param)) {
            return ErrorCode::errorMissingParam();
        }
        try {
            $result = DB::transaction(function () use ($admin_id, $param, $token) {
                $admin = Admin::where('admins.id', $admin_id)->first();
                if (empty($admin)) {
                    throw new \Exception(ErrorCode::errorNotExist('admin'));
                }
                $param = json_decode($param, true);
                $param['id'] = $admin->user_id;
                $user = UserMethod::updateUserInfo($param, true);
                return $this->getAdminDetailById($admin->id, $token);
            });
            return ErrorCode::success($result,false);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function updateAdminInfo($admin_id)
    {
        $param = Input::get('param', null);
        $token = Input::get('token', null);
        if (is_null($token) || empty($token)) {
            return ErrorCode::errorMissingParam();
        }
        if (empty($param)) {
            ErrorCode::errorMissingParam();
        }

        try {
            $result = DB::transaction(function () use ($admin_id, $param, $token) {
                $admin = Admin::where('admins.id', $admin_id)->first();
                if (empty($admin)) {
                    throw new \Exception(ErrorCode::errorNotExist('admin'));
                }
                $param = json_decode($param, true);
                $param['id'] = $admin->user_id;
                $user = UserMethod::updateUserInfo($param, true);
                return $this->getAdminDetailById($admin->id, $token);
            });
            return ErrorCode::success($result,false);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function addAdminRole($user_id)
    {
        $token = Input::get('token', null);
        if (is_null($token) || empty($token)) {
            return ErrorCode::errorMissingParam();
        }
        $user = User::where('users.id', $user_id)->first();
        if (empty($user)) {
            return ErrorCode::errorNotExist('user');
        }

        $admin = Admin::where('user_id', $user_id)->first();
        if (!empty($admin)) {
            return ErrorCode::errorAlreadyExist('admin');
        }
        try {
            $admin = Admin::create(['user_id' => $user_id]);
            return ErrorCode::success($this->getAdminDetailById($admin->id, $token));
        } catch (\Exception $ex) {
            return ErrorCode::errorDB();
        }
    }


    public function deleteAdminRole($admin_id)
    {

        $result = Admin::where('id', $admin_id);
        if (empty($result)) {
            return ErrorCode::errorNotExist('admin');
        }
        if ($result) {
            return ErrorCode::success('remove admin');
        } else {
            return ErrorCode::errorDB();
        }
    }

    public function getAdmin($admin_id)
    {
        $token = Input::get('token', null);
        if (is_null($token) || empty($token)) {
            return ErrorCode::errorMissingParam();
        }
        $result = $this->getAdminDetailById($admin_id, $token);
        if (empty($result)) {
            return ErrorCode::errorNotExist('admin not exist ');
        } else {
            return ErrorCode::success($result);
        }
    }


    private function getAdminDetailById($admin_id, $token)
    {
        $result = Admin::where('admins.id', $admin_id)
            ->leftjoin('users', 'admins.user_id', '=', 'users.id')
            ->leftjoin('companies', 'users.company_id', "=", "companies.id")
            ->select('admins.id as admin_id', 'users.first_name as first_name',
                'users.last_name as last_name', 'users.mobile', 'users.email',
                'users.address','users.lat','users.lng',
                'users.avatar_url', 'users.gender', 'admins.user_id as user_id',
                DB::raw('unix_timestamp(users.updated_at) as updated_at'),
                'companies.id as company_id', 'companies.name as companies_name')
            ->first();
        $result->avatar_url = UrlSpell::getUrlSpell()
            ->spellingAvatarUrl($result->updated_at, $result->avatar_url, $token, $admin_id, UrlSpell::adminType);
        return $result;
    }

    public function updateAdminDeviceToken(Request $request, $token)
    {
        $admin = $request->user->admin;
        Admin::where("web_push_token",$token)->update(["web_push_token"=>""]);
        Admin::where("user_id",$admin->user_id)->update(["web_push_token"=>$token]);
        return ErrorCode::success("success");
    }
}
