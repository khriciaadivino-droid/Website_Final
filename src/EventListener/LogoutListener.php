<?php

namespace App\EventListener;

use App\Entity\User;
use App\Service\ActivityLogService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LogoutEvent;

#[AsEventListener(event: LogoutEvent::class)]
class LogoutListener
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {
    }

    public function __invoke(LogoutEvent $event): void
    {
        $token = $event->getToken();
        
        if ($token) {
            $user = $token->getUser();
            
            if ($user instanceof User) {
                $this->activityLogService->logLogout($user);
            }
        }
    }
}
