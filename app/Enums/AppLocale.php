<?php

namespace App\Enums;

enum AppLocale: string
{
    case English = 'en';
    case Albanian = 'sq';
    case German = 'de';
    case Serbian = 'sr';
    case French = 'fr';
    case Croatian = 'hr';

    public function label(): string
    {
        return match ($this) {
            self::English => __('locales.english'),
            self::Albanian => __('locales.albanian'),
            self::German => __('locales.german'),
            self::Serbian => __('locales.serbian'),
            self::French => __('locales.french'),
            self::Croatian => __('locales.croatian'),
        };
    }

    public function nativeLabel(): string
    {
        return match ($this) {
            self::English => 'English',
            self::Albanian => 'Shqip',
            self::German => 'Deutsch',
            self::Serbian => 'Srpski',
            self::French => 'Français',
            self::Croatian => 'Hrvatski',
        };
    }

    /**
     * @return list<self>
     */
    public static function configurable(): array
    {
        return [
            self::English,
            self::Albanian,
            self::German,
            self::Serbian,
            self::French,
            self::Croatian,
        ];
    }
}
