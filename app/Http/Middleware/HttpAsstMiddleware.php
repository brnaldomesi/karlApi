<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/2/29
 * Time: 18:14
 */

namespace App\Http\Middleware;

use App\ErrorCode;
use App\Method\MethodAlgorithm;
use App\Model\Asst;
use App\Model\Driver;
use App\Model\Sale;
use App\Model\User;
use Closure;

class HttpAsstMiddleware
{
    public function handle($request, Closure $next)
    {

        $gout = MethodAlgorithm::checkFullGout();
        if($gout){
            return ErrorCode::errorFullGout();
        }
        $token = $request->get('token',null);
        if(empty($token)){
            return ErrorCode::missingToken();
        }
        $user = User::where('web_token',$token)->first();

        if(empty($user)){
            return ErrorCode::errorTokenExpired();
        }
        $asst = Asst::where('user_id',$user->id)->first();
        if(empty($asst)){
            return ErrorCode::hasNoPermission();
        }
        $user->asst = $asst;
        $request->user= $user;
        return $next($request);
    }
}