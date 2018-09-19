<?php

namespace App\Http\Middleware;

use App\Model\User;
use Closure;

/**
 * 只用于认证用户,是否登录,不区分角色,对应权限为的ALL接口
 * Class LoginMiddleware
 * @package App\Http\Middleware
 */
class TestMiddleware extends BaseMiddleware
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
        if($_SERVER['APP_PUB']){
            return $next($request);
        }
    }
}
