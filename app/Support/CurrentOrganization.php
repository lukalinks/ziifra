<?php

namespace App\Support;

use App\Models\Organization;

class CurrentOrganization
{
    protected static ?Organization $organization = null;

    public static function set(Organization $organization): void
    {
        static::$organization = $organization;
    }

    public static function get(): ?Organization
    {
        return static::$organization;
    }

    public static function id(): ?int
    {
        return static::$organization?->id;
    }

    public static function clear(): void
    {
        static::$organization = null;
    }

    public static function check(): Organization
    {
        $organization = static::get();

        if ($organization === null) {
            abort(403, 'No organization selected.');
        }

        return $organization;
    }
}
