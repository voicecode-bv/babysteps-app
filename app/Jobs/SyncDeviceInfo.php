<?php

namespace App\Jobs;

use App\Services\ApiClient;
use Codingwithrk\PackageInfo\Facades\PackageInfo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncDeviceInfo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function handle(ApiClient $apiClient): void
    {
        if (! $apiClient->hasToken()) {
            return;
        }

        $info = PackageInfo::getInfo();

        if ($info === null) {
            return;
        }

        try {
            $response = $apiClient->post('/device-info', [
                'app_name' => $info->appName,
                'package_name' => $info->packageName,
                'version' => $info->version,
                'build_number' => $info->buildNumber,
                'installer_store' => $info->installerStore,
            ]);

            if (! $response->successful()) {
                Log::warning('Failed to sync device info', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                $this->release($this->backoff);
            }
        } catch (ConnectionException $e) {
            Log::warning('Could not connect to API for device info sync', [
                'message' => $e->getMessage(),
            ]);

            $this->release($this->backoff);
        }
    }
}
