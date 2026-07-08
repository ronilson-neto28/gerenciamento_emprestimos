<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminBasicAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = trim((string) env('ADMIN_BASIC_AUTH_USER', ''));
        $pass = (string) env('ADMIN_BASIC_AUTH_PASS', '');

        if ($user === '' || $pass === '') {
            return $next($request);
        }

        $providedUser = (string) $request->getUser();
        $providedPass = (string) $request->getPassword();

        if (hash_equals($user, $providedUser) && hash_equals($pass, $providedPass)) {
            return $next($request);
        }

        return response('Unauthorized', 401, ['WWW-Authenticate' => 'Basic realm="Admin"']);
    }
}

