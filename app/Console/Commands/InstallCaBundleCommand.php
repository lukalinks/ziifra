<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class InstallCaBundleCommand extends Command
{
    protected $signature = 'app:install-ca-bundle';

    protected $description = 'Download the Mozilla CA bundle for PHP/cURL HTTPS on Windows';

    public function handle(): int
    {
        $directory = storage_path('certs');
        $path = config('http.ca_bundle', $directory.'/cacert.pem');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $this->info('Downloading CA bundle…');

        $response = Http::withoutVerifying()
            ->timeout(30)
            ->get('https://curl.se/ca/cacert.pem');

        if (! $response->successful()) {
            $this->error('Download failed. Fetch manually from https://curl.se/ca/cacert.pem and save to: '.$path);

            return self::FAILURE;
        }

        file_put_contents($path, $response->body());

        $this->info('Saved CA bundle to '.$path);

        return self::SUCCESS;
    }
}
