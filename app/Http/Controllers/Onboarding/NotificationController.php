<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function __invoke(): Response
    {
        Auth::user()?->forceFill(['notifications_prompted_at' => now()])->save();

        return Inertia::render('Onboarding/Notifications');
    }
}
