<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EsCiudadano
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || $request->user()->tipo !== 'ciudadano') {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
