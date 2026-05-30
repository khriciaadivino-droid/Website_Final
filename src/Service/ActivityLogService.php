<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\Orders;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class ActivityLogService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PushNotificationService $pushNotificationService,
        private UserRepository $userRepository,
        private ActivityLiveRevisionService $activityLiveRevisionService,
    ) {}

    /**
     * Log any activity
     * 
     * @param User $user The user performing the action
     * @param string $action The action being performed (LOGIN, LOGOUT, CREATE, UPDATE, DELETE)
     * @param string|null $targetData Description of the affected data
     */
    public function log(User $user, string $action, ?string $targetData = null): void
    {
        $userId = $user->getId();
        if ($userId === null) {
            return;
        }

        $this->persistLog(
            $userId,
            $user->getFullName() ?: $user->getEmail(),
            $this->resolvePrimaryRole($user),
            $action,
            $targetData
        );
    }

    /**
     * Log user login
     */
    public function logLogin(User $user): void
    {
        $this->log($user, 'LOGIN', null);
    }

    /**
     * Log user logout
     */
    public function logLogout(User $user): void
    {
        $this->log($user, 'LOGOUT', null);
    }

    /**
     * Log record creation
     */
    public function logCreate(User $user, string $entityType, string $entityName, int $entityId): void
    {
        $targetData = sprintf('%s: %s (ID: %d)', $entityType, $entityName, $entityId);
        $this->log($user, 'CREATE', $targetData);
    }

    /**
     * Log record update
     */
    public function logUpdate(User $user, string $entityType, string $entityName, int $entityId): void
    {
        $targetData = sprintf('%s: %s (ID: %d)', $entityType, $entityName, $entityId);
        $this->log($user, 'UPDATE', $targetData);
    }

    /**
     * Log record deletion
     */
    public function logDelete(User $user, string $entityType, string $entityName, int $entityId): void
    {
        $targetData = sprintf('%s: %s (ID: %d)', $entityType, $entityName, $entityId);
        $this->log($user, 'DELETE', $targetData);
    }

    /**
     * Log user creation (admin action)
     */
    public function logUserCreate(User $admin, User $newUser): void
    {
        $targetData = sprintf('User: %s (ID: %d)', $newUser->getEmail(), $newUser->getId());
        $this->log($admin, 'CREATE', $targetData);
    }

    /**
     * Log user deletion (admin action)
     */
    public function logUserDelete(User $admin, string $deletedUserEmail, int $deletedUserId): void
    {
        $targetData = sprintf('User: %s (ID: %d)', $deletedUserEmail, $deletedUserId);
        $this->log($admin, 'DELETE', $targetData);
    }

    /**
     * Log user update (admin action)
     */
    public function logUserUpdate(User $admin, User $updatedUser): void
    {
        $targetData = sprintf('User: %s (ID: %d)', $updatedUser->getEmail(), $updatedUser->getId());
        $this->log($admin, 'UPDATE', $targetData);
    }

    public function logOrderStatusNotification(User $actor, Orders $order, string $previousStatus, string $newStatus): void
    {
        if (strcasecmp($previousStatus, $newStatus) === 0) {
            return;
        }

        $normalizedStatus = strtolower(trim($newStatus));
        if (!in_array($normalizedStatus, ['processing', 'completed', 'cancelled'], true)) {
            return;
        }

        $customerEmail = trim((string) ($order->getCustomerEmail() ?? ''));
        if ($customerEmail === '') {
            return;
        }

        $customer = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $customerEmail,
        ]);
        if (!$customer instanceof User || $customer->getId() === null) {
            return;
        }

        $orderLabel = $order->getOrderNumber() ?: ('Order #' . $order->getId());
        $targetData = sprintf('%s is now %s.', $orderLabel, $newStatus);

        $this->persistLog(
            $customer->getId(),
            $actor->getFullName() ?: $actor->getEmail(),
            $this->resolvePrimaryRole($actor),
            'ORDER_STATUS',
            $targetData,
            $actor->getId()
        );

        $this->pushNotificationService->sendToUser(
            $customer,
            'Order update',
            $targetData,
            [
                'action' => 'ORDER_STATUS',
                'order_number' => (string) ($order->getOrderNumber() ?: $order->getId()),
                'status' => $normalizedStatus,
            ]
        );
    }

    private function resolvePrimaryRole(User $user): string
    {
        $roles = $user->getRoles();
        if (in_array('ROLE_ADMIN', $roles, true)) {
            return 'ROLE_ADMIN';
        }

        if (in_array('ROLE_STAFF', $roles, true)) {
            return 'ROLE_STAFF';
        }

        return 'ROLE_USER';
    }

    private function persistLog(
        int $userId,
        string $username,
        string $role,
        string $action,
        ?string $targetData,
        ?int $actorUserId = null,
    ): void {
        $log = new ActivityLog();
        $log->setUserId($userId);
        $log->setUsername($username);
        $log->setRole($role);
        $log->setAction($action);
        $log->setTargetData($targetData);

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        $this->activityLiveRevisionService->bump();

        $this->dispatchPushNotifications(
            $userId,
            $username,
            $role,
            $action,
            $targetData,
            $actorUserId ?? $userId,
        );
    }

    private function dispatchPushNotifications(
        int $logUserId,
        string $username,
        string $role,
        string $action,
        ?string $targetData,
        int $actorUserId,
    ): void {
        if (!$this->pushNotificationService->isConfigured()) {
            return;
        }

        $title = $this->formatPushTitle($action);
        $body = $this->formatPushBody($username, $action, $targetData);
        $data = [
            'action' => $action,
            'role' => $role,
            'log_user_id' => (string) $logUserId,
        ];

        $adminRecipients = $this->userRepository->findWithPushTokenByRoles(
            ['ROLE_ADMIN', 'ROLE_STAFF'],
            $actorUserId,
        );
        $this->pushNotificationService->sendToUsers($adminRecipients, $title, $body, $data);

        if ($action === 'ORDER_STATUS' || $logUserId === $actorUserId) {
            return;
        }

        $logUser = $this->entityManager->getRepository(User::class)->find($logUserId);
        if ($logUser instanceof User) {
            $this->pushNotificationService->sendToUser($logUser, $title, $body, $data);
        }
    }

    private function formatPushTitle(string $action): string
    {
        return match ($action) {
            'LOGIN' => 'New sign-in',
            'LOGOUT' => 'Signed out',
            'ORDER_STATUS' => 'Order status',
            'CREATE' => 'New activity',
            'UPDATE' => 'Update',
            'DELETE' => 'Deletion',
            default => 'PawStuff activity',
        };
    }

    private function formatPushBody(string $username, string $action, ?string $targetData): string
    {
        return match ($action) {
            'LOGIN' => sprintf('%s signed in', $username),
            'LOGOUT' => sprintf('%s signed out', $username),
            'ORDER_STATUS' => $targetData
                ? trim($targetData) . ' Updated by ' . $username . '.'
                : sprintf('Order updated by %s', $username),
            default => $targetData
                ? sprintf('%s %s', $username, $targetData)
                : sprintf('%s performed %s', $username, $action),
        };
    }
}
