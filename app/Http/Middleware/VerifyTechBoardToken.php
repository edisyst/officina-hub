<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyTechBoardToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('techboard.token');

        if (empty($expected) || ! hash_equals($expected, (string) $request->route('token'))) {
            abort(404);
        }

        return $next($request);
    }
}
