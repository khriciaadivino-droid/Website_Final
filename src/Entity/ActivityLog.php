<?php

namespace App\Entity;

use App\Repository\ActivityLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActivityLogRepository::class)]
#[ORM\Table(name: 'activity_log')]
#[ORM\Index(name: 'idx_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_action', columns: ['action'])]
#[ORM\Index(name: 'idx_timestamp', columns: ['timestamp'])]
class ActivityLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: false)]
    private ?int $userId = null;

    #[ORM\Column(length: 255, nullable: false)]
    private ?string $username = null;

    #[ORM\Column(length: 50, nullable: false)]
    private ?string $role = null;

    #[ORM\Column(length: 50, nullable: false)]
    private ?string $action = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $targetData = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: false)]
    private ?\DateTimeInterface $timestamp = null;

    public function __construct()
    {
        $this->timestamp = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): static
    {
        $this->userId = $userId;
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    public function getTargetData(): ?string
    {
        return $this->targetData;
    }

    public function setTargetData(?string $targetData): static
    {
        $this->targetData = $targetData;
        return $this;
    }

    public function getTimestamp(): ?\DateTimeInterface
    {
        return $this->timestamp;
    }

    public function setTimestamp(\DateTimeInterface $timestamp): static
    {
        $this->timestamp = $timestamp;
        return $this;
    }
}
