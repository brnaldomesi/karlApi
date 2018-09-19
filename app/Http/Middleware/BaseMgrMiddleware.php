<?php

namespace App\Http\Middleware;

use App\Constants;
use App\ErrorCode;
use App\Method\MethodAlgorithm;
use App\Model\Superadmin;
use App\Model\User;
use Closure;

abstract class BaseMgrMiddleware
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
            ->whereRaw('unix_timestamp(token_invalid_time) > '.time())
            ->first();

        if(empty($user)){
           return ErrorCode::errorTokenExpired();
        }
        $sa = Superadmin::where('user_id',$user->id)->first();
        if(empty($sa)){
            return ErrorCode::hasNoPermission();
        }
        $user->token_invalid_time = date('Y-m-d H:i:s',time()+Constants::DAY_SECONDS);
        $user->save();
        $user->token = $user->web_token;
        $user->superadmin = $sa;
        $request->user= $user;
        return $next($request);
    }


    /**
     * @param $request
     * @return mixed
     */
    abstract function getToken($request);
}
