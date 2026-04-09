<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->locale) {
            app()->setLocale($request->user()->locale);
        } elseif (session('locale')) {
            app()->setLocale(session('locale'));
        }

        return $next($request);
    }
}
