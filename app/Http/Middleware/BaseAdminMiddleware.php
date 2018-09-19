<?php

namespace App\Http\Middleware;

use App\ErrorCode;
use App\Method\MethodAlgorithm;
use App\Model\Admin;
use App\Constants;
use App\Model\ProxyAdmin;
use App\Model\User;
use Closure;

abstract class BaseAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $gout = MethodAlgorithm::checkFullGout();
        if($gout){
            return ErrorCode::errorFullGout();
        }

        $token = $this->getToken($request);
        if(empty($token)){
            return ErrorCode::missingToken();
        }
        $user = User::where('web_token',$token)
            ->whereRaw('token_invalid_time > now()')
            ->first();

        if(empty($user)){
            if (
            ProxyAdmin::urlNotAllow($request->decodedPath())
            ){
                return ErrorCode::errorAdminUnauthorizedOperation();
            }

            $user = ProxyAdmin::where('token',$token)
                ->whereRaw("expire_time > now()")
                ->first();
            if(empty($user)){
                return ErrorCode::errorTokenExpired();
            }
            $admin = new Admin();
            $admin->id = $user->creator_id;
            $user->admin=$admin;
            $request->user=$user;

            return $next($request);
        }else{
            $admin = Admin::where('user_id',$user->id)
                ->first();
            if(empty($admin)){
                return ErrorCode::hasNoPermission();
            }
            if(strtotime($user->token_invalid_time)<time()+Constants::DAY_SECONDS){
                $user->token_invalid_time = date('Y-m-d H:i:s',time()+Constants::DAY_SECONDS);
                $user->save();
            }
            $user->token = $user->web_token;
            $user->admin = $admin;
            $request->user= $user;
            return $next($request);
        }
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return mixed
     */
    protected abstract function getToken($request);
}
