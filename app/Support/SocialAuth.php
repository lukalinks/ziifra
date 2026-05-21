<?php

namespace App\Support;

use App\Enums\OAuthProvider;
use Illuminate\Support\Facades\Route;

class SocialAuth
{
    public static function redirectUri(OAuthProvider $provider): string
    {
        return url('/auth/'.$provider->value.'/callback');
    }

    public static function hasAnyProvider(): bool
    {
        return OAuthProvider::configured() !== [];
    }
}
