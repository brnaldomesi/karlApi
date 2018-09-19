<?php

namespace App\Http\Middleware;


/**
 * 只用于认证用户,是否登录,不区分角色,对应权限为的ALL接口
 * Class LoginMiddleware
 * @package App\Http\Middleware
 */
class HttpLoginMiddleware extends BaseLoginMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    function getToken($request)
    {
        return $request->get("token",null);

    }

}
