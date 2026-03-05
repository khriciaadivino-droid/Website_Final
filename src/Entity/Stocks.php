<?php

namespace App\Entity;

use App\Repository\StocksRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Productss;

#[ORM\Entity(repositoryClass: StocksRepository::class)]
class Stocks
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updateAt = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $stockChangeLog = null;

    #[ORM\ManyToOne(inversedBy: 'stocks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Productss $productss = null;

    // 🧩 Add optional quantity change tracking if needed
    #[ORM\Column(nullable: true)]
    private ?float $quantityChange = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreateAt(): ?\DateTimeImmutable
    {
        return $this->createAt;
    }

    public function setCreateAt(\DateTimeImmutable $createAt): static
    {
        $this->createAt = $createAt;
        return $this;
    }

    public function getUpdateAt(): ?\DateTimeImmutable
    {
        return $this->updateAt;
    }

    public function setUpdateAt(\DateTimeImmutable $updateAt): static
    {
        $this->updateAt = $updateAt;
        return $this;
    }

    public function getStockChangeLog(): ?string
    {
        return $this->stockChangeLog;
    }

    public function setStockChangeLog(string $stockChangeLog): static
    {
        $this->stockChangeLog = $stockChangeLog;
        return $this;
    }

    public function getProductss(): ?Productss
    {
        return $this->productss;
    }

    public function setProductss(?Productss $productss): static
    {
        $this->productss = $productss;
        return $this;
    }

    public function getQuantityChange(): ?float
    {
        return $this->quantityChange;
    }

    public function setQuantityChange(?float $quantityChange): static
    {
        $this->quantityChange = $quantityChange;
        return $this;
    }
}
