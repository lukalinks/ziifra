<?php

namespace App\Support;

class HttpSslOptions
{
    /**
     * @return array{verify: bool|string}
     */
    public function toArray(): array
    {
        if (! config('http.verify_ssl', true)) {
            return ['verify' => false];
        }

        $bundle = config('http.ca_bundle');

        if (is_string($bundle) && $bundle !== '' && is_file($bundle)) {
            return ['verify' => $bundle];
        }

        return ['verify' => true];
    }
}
