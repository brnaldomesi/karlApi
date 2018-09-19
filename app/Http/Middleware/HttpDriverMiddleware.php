<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/2/29
 * Time: 18:14
 */

namespace App\Http\Middleware;

use App\ErrorCode;
use App\Model\Driver;
use App\Model\User;
use Closure;

class HttpDriverMiddleware extends BaseDriverMiddleware
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