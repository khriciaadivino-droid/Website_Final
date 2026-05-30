<?php

namespace App\Service;

class OrderLiveRevisionService
{
    public function __construct(
        private readonly LiveRevisionService $liveRevisionService,
    ) {}

    public function bump(): int
    {
        return $this->liveRevisionService->bump(LiveRevisionService::ORDERS);
    }

    public function current(): int
    {
        return $this->liveRevisionService->current(LiveRevisionService::ORDERS);
    }
}
