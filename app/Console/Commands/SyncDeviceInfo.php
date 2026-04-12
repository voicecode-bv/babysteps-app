<?php

namespace App\Console\Commands;

use App\Jobs\SyncDeviceInfo as SyncDeviceInfoJob;
use Illuminate\Console\Command;

class SyncDeviceInfo extends Command
{
    protected $signature = 'sync:device-info';

    protected $description = 'Dispatch a job to sync device and app package info to the API';

    public function handle(): int
    {
        SyncDeviceInfoJob::dispatch();

        $this->info('Device info sync job dispatched.');

        return self::SUCCESS;
    }
}
