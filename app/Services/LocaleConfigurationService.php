<?php

namespace App\Services;

use App\Enums\AppLocale;
use App\Models\Organization;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class LocaleConfigurationService
{
    public const SETTINGS_KEY = 'languages';

    private const CACHE_KEY = 'platform.languages';

    /**
     * @return array<string, array{label: string, native: string}>
     */
    public function definitions(): array
    {
        return config('ziifra.locale_definitions', []);
    }

    /**
     * @return list<string>
     */
    public function allCodes(): array
    {
        return array_keys($this->definitions());
    }

    /**
     * @return list<string>
     */
    public function enabledCodes(): array
    {
        $settings = $this->settings();

        $enabled = $settings['enabled'] ?? [AppLocale::English->value];

        return array_values(array_filter(
            $enabled,
            fn (string $code) => $this->isValidCode($code),
        ));
    }

    /**
     * @return array<string, string> code => native label
     */
    public function enabledOptions(): array
    {
        $options = [];

        foreach ($this->enabledCodes() as $code) {
            $definition = $this->definitions()[$code] ?? null;
            $options[$code] = $definition['native'] ?? $code;
        }

        return $options;
    }

    public function defaultCode(): string
    {
        $settings = $this->settings();
        $default = $settings['default'] ?? AppLocale::English->value;

        if (! $this->isValidCode($default) || ! $this->isEnabled($default)) {
            return $this->enabledCodes()[0] ?? AppLocale::English->value;
        }

        return $default;
    }

    public function isEnabled(string $code): bool
    {
        return in_array($code, $this->enabledCodes(), true);
    }

    public function isValidCode(string $code): bool
    {
        return array_key_exists($code, $this->definitions());
    }

    public function resolve(?string $preferred): string
    {
        if ($preferred !== null && $this->isEnabled($preferred)) {
            return $preferred;
        }

        return $this->defaultCode();
    }

    /**
     * @param  list<string>  $enabled
     */
    public function update(array $enabled, string $default, ?User $admin = null): void
    {
        $enabled = array_values(array_unique(array_filter(
            $enabled,
            fn (string $code) => $this->isValidCode($code),
        )));

        if ($enabled === []) {
            throw new InvalidArgumentException('At least one language must remain enabled.');
        }

        if (! in_array($default, $enabled, true)) {
            throw new InvalidArgumentException('Default language must be one of the enabled languages.');
        }

        PlatformSetting::query()->updateOrCreate(
            ['key' => self::SETTINGS_KEY],
            ['value' => [
                'enabled' => $enabled,
                'default' => $default,
                'updated_by' => $admin?->id,
                'updated_at' => now()->toIso8601String(),
            ]],
        );

        Cache::forget(self::CACHE_KEY);

        $this->reconcileStoredLocales($enabled, $default);
    }

    /**
     * @param  list<string>  $enabled
     */
    protected function reconcileStoredLocales(array $enabled, string $default): void
    {
        Organization::query()
            ->whereNotIn('locale', $enabled)
            ->update(['locale' => $default]);

        User::query()
            ->whereNotNull('locale')
            ->whereNotIn('locale', $enabled)
            ->update(['locale' => null]);
    }

    /**
     * @return array{enabled: list<string>, default: string}
     */
    protected function settings(): array
    {
        if (! Schema::hasTable('platform_settings')) {
            return $this->defaultSettings();
        }

        /** @var array{enabled?: list<string>, default?: string} $settings */
        $settings = Cache::rememberForever(self::CACHE_KEY, function (): array {
            $row = PlatformSetting::query()->find(self::SETTINGS_KEY);

            if ($row === null) {
                return $this->defaultSettings();
            }

            return is_array($row->value) ? $row->value : $this->defaultSettings();
        });

        return $settings;
    }

    /**
     * @return array{enabled: list<string>, default: string}
     */
    protected function defaultSettings(): array
    {
        return [
            'enabled' => array_map(
                fn (AppLocale $locale) => $locale->value,
                AppLocale::configurable(),
            ),
            'default' => AppLocale::English->value,
        ];
    }
}
