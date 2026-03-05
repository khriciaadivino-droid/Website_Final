<?php

namespace App\Entity;

use App\Repository\PetOwnersRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PetOwnersRepository::class)]
class PetOwners
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToMany(targetEntity: PetProfileManagement::class, mappedBy: 'owner', cascade: ['persist', 'remove'])]
    private Collection $petProfiles;

    #[ORM\Column(length: 100)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    private ?string $lastName = null;

    #[ORM\Column(length: 100)]
    private ?string $email = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $registrationDate = null;

    public function __construct()
    {
        $this->petProfiles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getRegistrationDate(): ?\DateTimeInterface
    {
        return $this->registrationDate;
    }

    public function setRegistrationDate(\DateTimeInterface $registrationDate): static
    {
        $this->registrationDate = $registrationDate;

        return $this;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    /**
     * @return Collection<int, PetProfileManagement>
     */
    public function getPetProfiles(): Collection
    {
        return $this->petProfiles;
    }

    public function addPetProfile(PetProfileManagement $petProfile): static
    {
        if (!$this->petProfiles->contains($petProfile)) {
            $this->petProfiles->add($petProfile);
            $petProfile->setOwner($this);
        }

        return $this;
    }

    public function removePetProfile(PetProfileManagement $petProfile): static
    {
        if ($this->petProfiles->removeElement($petProfile)) {
            // set the owning side to null (unless already changed)
            if ($petProfile->getOwner() === $this) {
                $petProfile->setOwner(null);
            }
        }

        return $this;
    }
}
