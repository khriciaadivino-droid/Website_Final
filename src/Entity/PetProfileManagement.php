<?php

namespace App\Entity;

use App\Repository\PetProfileManagementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PetProfileManagementRepository::class)]
class PetProfileManagement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: PetOwners::class, inversedBy: 'petProfiles')]
    #[ORM\JoinColumn(nullable: true)]
    private ?PetOwners $owner = null;

    #[ORM\Column(length: 255)]
    private ?string $Species = null;

    #[ORM\Column(length: 255)]
    private ?string $Breed = null;

    #[ORM\Column]
    private ?float $Age = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateofbirth = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isPetOfTheMonth = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSpecies(): ?string
    {
        return $this->Species;
    }

    public function setSpecies(string $Species): static
    {
        $this->Species = $Species;

        return $this;
    }

    public function getBreed(): ?string
    {
        return $this->Breed;
    }

    public function setBreed(string $Breed): static
    {
        $this->Breed = $Breed;

        return $this;
    }

    public function getAge(): ?float
    {
        return $this->Age;
    }

    public function setAge(float $Age): static
    {
        $this->Age = $Age;

        return $this;
    }

    public function getDateofbirth(): ?\DateTimeInterface
    {
        return $this->dateofbirth;
    }

    public function setDateofbirth(?\DateTimeInterface $dateofbirth): static
    {
        $this->dateofbirth = $dateofbirth;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getOwner(): ?PetOwners
    {
        return $this->owner;
    }

    public function setOwner(?PetOwners $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function isPetOfTheMonth(): bool
    {
        return $this->isPetOfTheMonth;
    }

    public function setIsPetOfTheMonth(bool $isPetOfTheMonth): static
    {
        $this->isPetOfTheMonth = $isPetOfTheMonth;

        return $this;
    }
}
