<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboarded
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()
            && Auth::user()?->onboarded_at === null
            && Auth::user()?->notifications_prompted_at === null
        ) {
            return redirect()->route('onboarding.intro');
        }

        return $next($request);
    }
}
