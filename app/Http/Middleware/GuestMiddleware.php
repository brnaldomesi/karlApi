<?php

namespace App\Http\Middleware;

use App\ErrorCode;
use App\Method\MethodAlgorithm;
use Closure;

class GuestMiddleware
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
        return $next($request);
    }
}
