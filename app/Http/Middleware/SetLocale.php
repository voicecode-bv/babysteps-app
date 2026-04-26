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
        $supported = ['en', 'nl'];

        if ($request->user()?->locale && in_array($request->user()->locale, $supported, true)) {
            app()->setLocale($request->user()->locale);
        } elseif (in_array($request->getPreferredLanguage($supported), $supported, true)) {
            app()->setLocale($request->getPreferredLanguage($supported));
        } elseif (session('locale') && in_array(session('locale'), $supported, true)) {
            app()->setLocale(session('locale'));
        }

        return $next($request);
    }
}
