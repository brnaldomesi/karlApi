<?php

namespace App\Http\Middleware;

class HttpMgrMiddleware extends BaseMgrMiddleware
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
