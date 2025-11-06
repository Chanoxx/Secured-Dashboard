<?php

namespace App\Entity;

use App\Repository\ItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ItemRepository::class)]
class Item
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $Item_name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $Description = null;

    #[ORM\Column]
    private ?int $Quantity = null;

    #[ORM\Column]
    private ?int $Price = null;

    #[ORM\Column(length: 255, nullable: true)]
private ?string $image = null;

public function getImage(): ?string
{
    return $this->image;
}

public function setImage(?string $image): static
{
    $this->image = $image;

    return $this;
}


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getItemName(): ?string
    {
        return $this->Item_name;
    }

    public function setItemName(string $Item_name): static
    {
        $this->Item_name = $Item_name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->Description;
    }

    public function setDescription(string $Description): static
    {
        $this->Description = $Description;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->Quantity;
    }

    public function setQuantity(int $Quantity): static
    {
        $this->Quantity = $Quantity;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->Price;
    }

    public function setPrice(int $Price): static
    {
        $this->Price = $Price;

        return $this;
    }
}
