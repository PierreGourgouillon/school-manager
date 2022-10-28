<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\AddressRepository;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AddressRepository::class)]
class Address
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getStudent", "getAllSchools"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getStudent"])]
    private ?string $street = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getStudent"])]
    private ?string $city = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getStudent"])]
    private ?string $postalcode = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getStudent"])]
    private ?string $country = null;

    #[ORM\Column]
    #[Groups(["status"])]
    private ?bool $status = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(string $street): self
    {
        $this->street = $street;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getPostalcode(): ?string
    {
        return $this->postalcode;
    }

    public function setPostalcode(string $postalcode): self
    {
        $this->postalcode = $postalcode;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function isStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): self
    {
        $this->status = $status;

        return $this;
    }
}
