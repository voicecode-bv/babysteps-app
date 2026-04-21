<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class IntroController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Onboarding/Intro');
    }
}
