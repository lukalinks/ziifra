<?php

namespace App\Enums;

enum AppLocale: string
{
    case English = 'en';
    case Albanian = 'sq';
    case German = 'de';
    case Serbian = 'sr';

    public function label(): string
    {
        return match ($this) {
            self::English => __('locales.english'),
            self::Albanian => __('locales.albanian'),
            self::German => __('locales.german'),
            self::Serbian => __('locales.serbian'),
        };
    }

    public function nativeLabel(): string
    {
        return match ($this) {
            self::English => 'English',
            self::Albanian => 'Shqip',
            self::German => 'Deutsch',
            self::Serbian => 'Srpski',
        };
    }

    /**
     * @return list<self>
     */
    public static function configurable(): array
    {
        return [self::English, self::Albanian, self::German, self::Serbian];
    }
}
