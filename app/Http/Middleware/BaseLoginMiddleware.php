<?php
/**
 * Created by PhpStorm.
 * User: lqh
 * Date: 2017/6/9
 * Time: 下午1:03
 */

namespace app\Http\Middleware;


use App\Constants;
use App\ErrorCode;
use App\Method\MethodAlgorithm;
use App\Model\Admin;
use App\Model\ProxyAdmin;
use App\Model\User;
use Closure;

abstract class BaseLoginMiddleware
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

        if(($request->segment(2) == "customers"||$request->segment(2) == "admins")
            && (is_numeric($request->segment(3)))
            && $request->segment(4) == "avatar"
        ){

        }else{
            $token = $this->getToken($request);
            if(empty($token)){
                return ErrorCode::missingToken();
            }
            $user = User::where('token',$token)->first();

            if(empty($user)){
                $user = User::where('web_token',$token)
                    ->whereRaw('unix_timestamp(token_invalid_time) > '.time())
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
                    $user->token_invalid_time = date('Y-m-d H:i:s',time()+Constants::HALF_HOUR);
                    $user->save();
                }
            }
            $request->user= $user;
        }

        return $next($request);
    }

    /**
     * @param $request
     * @return mixed
     */
    abstract function getToken($request);
}