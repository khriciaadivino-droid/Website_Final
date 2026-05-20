<?php

namespace App\EventListener;

use App\Entity\User;
use App\Service\ActivityLogService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[AsEventListener(event: LoginSuccessEvent::class)]
class LoginSuccessListener
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {}

    public function __invoke(LoginSuccessEvent $event): void
    {
        // API logins are logged directly in JWTAuthenticationSuccessHandler
        // Only log here for web (form_login) via the 'main' firewall
        if ($event->getFirewallName() !== 'main') {
            return;
        }

        $user = $event->getUser();

        if ($user instanceof User) {
            $this->activityLogService->logLogin($user);
        }
    }
}
