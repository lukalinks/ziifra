<?php

namespace App\Enums;

enum OAuthProvider: string
{
    case Google = 'google';

    public function label(): string
    {
        return 'Google';
    }

    public function isConfigured(): bool
    {
        return filled(config('services.google.client_id'));
    }

    /**
     * @return list<self>
     */
    public static function configured(): array
    {
        return self::Google->isConfigured() ? [self::Google] : [];
    }
}
