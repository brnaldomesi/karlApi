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
use App\Model\Driver;
use App\Model\User;
use Closure;

abstract class BaseDriverMiddleware
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
        $user = User::where('token',$token)->first();

        if(empty($user)){
            return ErrorCode::errorTokenExpired();
        }
        $driver = Driver::where('user_id',$user->id)->first();
        if(empty($driver)){
            return ErrorCode::hasNoPermission();
        }
        $user->driver = $driver;
        $request->user= $user;
        return $next($request);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return mixed
     */
    protected abstract function getToken($request);
}