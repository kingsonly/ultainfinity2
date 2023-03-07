<?php

namespace App\Http\Middleware;

use Closure;

class UserMiddleware
{
    /**
     * ensure that user id is passed in the header .
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!$request->headers->has('user-id')) {
            // return json 
            return response()->json(['status'=>'error', 'message'=>'user-id header is required']);
        }

        return $next($request);
    }

}