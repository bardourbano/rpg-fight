<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class BasicAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Closure | Response
    {
        if (!$request->hasHeader('nickname') || !$request->hasHeader('password')) {
            return response('Nickname and Password required in header to access this route.', 400);
        }

        $user = User::firstWhere('nickname', $request->header('nickname'));

        // @phpstan-ignore-next-line
        if ($user == null || !Hash::check($request->header('password'), $user->password)) {
            return response('Nickname and/or Password incorrect.', 401);
        }
        
        return $next($request);
    }
}
