<?php

namespace AlbrachtSystems\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EmailGeverifieerd
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()?->email_verified_at) {
            return response()->json([
                'message' => 'Je e-mailadres is nog niet bevestigd.',
                'code'    => 'email_niet_geverifieerd',
            ], 403);
        }

        return $next($request);
    }
}
