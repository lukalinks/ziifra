<?php

namespace App\Support;

use Carbon\CarbonInterface;

class NotificationItem
{
    public function __construct(
        public string $id,
        public string $title,
        public string $body,
        public ?string $url,
        public bool $read,
        public CarbonInterface $createdAt,
        public bool $ephemeral = false,
    ) {}
}
