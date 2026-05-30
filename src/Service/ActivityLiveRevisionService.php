<?php

namespace App\Service;

class ActivityLiveRevisionService
{
    public function __construct(
        private readonly LiveRevisionService $liveRevisionService,
    ) {}

    public function bump(): int
    {
        return $this->liveRevisionService->bump(LiveRevisionService::ACTIVITY);
    }

    public function current(): int
    {
        return $this->liveRevisionService->current(LiveRevisionService::ACTIVITY);
    }
}
