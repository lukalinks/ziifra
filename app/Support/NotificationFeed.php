<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class NotificationFeed
{
    /**
     * @param  Collection<int, NotificationItem>  $items
     */
    public function __construct(
        public Collection $items,
        public int $unreadCount,
        public bool $canMarkAllRead,
    ) {}
}
