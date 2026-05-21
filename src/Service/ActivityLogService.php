<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\Orders;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ActivityLogService
{
    public function __construct(
        private EntityManagerInterface $entityManager
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
            $targetData
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

    private function persistLog(int $userId, string $username, string $role, string $action, ?string $targetData): void
    {
        $log = new ActivityLog();
        $log->setUserId($userId);
        $log->setUsername($username);
        $log->setRole($role);
        $log->setAction($action);
        $log->setTargetData($targetData);

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
}
