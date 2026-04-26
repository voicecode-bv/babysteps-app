<?php

use Illuminate\Support\Facades\Schedule;

// Schedule::command('sync:device-info')->daily()->onAnyNetwork();
Schedule::command('telescope:prune')->daily();
