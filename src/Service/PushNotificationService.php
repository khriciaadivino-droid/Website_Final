<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PushNotificationService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%env(default::FCM_SERVER_KEY)%')]
        private readonly ?string $serverKey = null,
    ) {}

    public function isConfigured(): bool
    {
        return $this->serverKey !== null && trim($this->serverKey) !== '';
    }

    public function sendToUser(User $user, string $title, string $body, array $data = []): bool
    {
        $token = trim((string) $user->getPushToken());

        if ($token === '') {
            return false;
        }

        return $this->sendToToken($token, $title, $body, $data);
    }

    /**
     * @param iterable<User> $users
     */
    public function sendToUsers(iterable $users, string $title, string $body, array $data = []): int
    {
        $sent = 0;

        foreach ($users as $user) {
            if ($this->sendToUser($user, $title, $body, $data)) {
                ++$sent;
            }
        }

        return $sent;
    }

    public function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        if ($this->serverKey === null || trim($this->serverKey) === '' || trim($token) === '') {
            return false;
        }

        $payload = [
            'to' => $token,
            'priority' => 'high',
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $this->normalizeData($data),
        ];

        try {
            $response = $this->httpClient->request('POST', 'https://fcm.googleapis.com/fcm/send', [
                'headers' => [
                    'Authorization' => 'key=' . $this->serverKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
        } catch (ExceptionInterface) {
            return false;
        }
    }

    private function normalizeData(array $data): array
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            $normalized[(string) $key] = is_scalar($value) || $value === null ? (string) $value : json_encode($value, JSON_THROW_ON_ERROR);
        }

        return $normalized;
    }
}
