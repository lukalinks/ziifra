<?php

namespace App\Enums;

enum SubscriptionPlan: string
{
    case Trial = 'trial';
    case Starter = 'starter';
    case Pro = 'pro';
    case Enterprise = 'enterprise';

    public function label(): string
    {
        $name = app(\App\Services\BillingConfigurationService::class)
            ->plan($this->value)['name'] ?? null;

        return is_string($name) && $name !== '' ? $name : ucfirst($this->value);
    }

    /**
     * @return list<self>
     */
    public static function selectable(): array
    {
        return [self::Starter, self::Pro, self::Enterprise];
    }
}
