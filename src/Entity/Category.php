<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, Productss>
     */
    #[ORM\OneToMany(targetEntity: Productss::class, mappedBy: 'category')]
    private Collection $productsses;
    

    public function __construct()
    {
        $this->productsses = new ArrayCollection();
    }

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

    /**
     * @return Collection<int, Productss>
     */
    public function getProductsses(): Collection
    {
        return $this->productsses;
    }
    // In src/Entity/Category.php
public function __toString(): string
{
    return $this->name ?? '';
}

    public function addProductss(Productss $productss): static
    {
        if (!$this->productsses->contains($productss)) {
            $this->productsses->add($productss);
            $productss->setCategory($this);
        }

        return $this;
    }

    public function removeProductss(Productss $productss): static
    {
        if ($this->productsses->removeElement($productss)) {
            // set the owning side to null (unless already changed)
            if ($productss->getCategory() === $this) {
                $productss->setCategory(null);
            }
        }

        return $this;
    }
}
