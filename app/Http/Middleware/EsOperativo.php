<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EsOperativo
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || $request->user()->tipo !== 'operativo') {
            return redirect()->route('portal.dashboard');
        }

        return $next($request);
    }
}
