<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class BasicAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!$request->hasHeader('nickname') || !$request->hasHeader('password')) {
            return abort(400, 'Nickname and Password required in header to access this route.');
        }

        $user = User::firstWhere('nickname', $request->header('nickname'));

        if ($user == null || !Hash::check($request->header('password'), $user->password)) {
            return abort(401, 'Nickname and/or Password incorrect.');
        }
        
        return $next($request);
    }
}
