<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\SchoolRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SchoolRepository::class)]
class School
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getAllSchools", "getSchool"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getAllSchools", "getSchool"])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getAllSchools", "getSchool"])]
    private ?string $email = null;

    #[ORM\Column]
    #[Groups(["status"])]
    private ?bool $status = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["getAllSchools", "getSchool"])]
    private ?Address $address = null;

    #[ORM\ManyToOne(inversedBy: 'schools')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["getAllSchools", "getSchool"])]
    private ?Director $director = null;

    #[ORM\OneToMany(mappedBy: 'school', targetEntity: StudentClass::class)]
    #[Groups(["getAllSchools", "getSchool"])]
    private Collection $studentClasses;

    public function __construct()
    {
        $this->studentClasses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

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

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function setAddress(Address $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getDirector(): ?Director
    {
        return $this->director;
    }

    public function setDirector(?Director $director): self
    {
        $this->director = $director;

        return $this;
    }

    /**
     * @return Collection<int, StudentClass>
     */
    public function getStudentClasses(): Collection
    {
        return $this->studentClasses;
    }

    public function addStudentClass(StudentClass $studentClass): self
    {
        if (!$this->studentClasses->contains($studentClass)) {
            $this->studentClasses->add($studentClass);
            $studentClass->setSchool($this);
        }

        return $this;
    }

    public function removeStudentClass(StudentClass $studentClass): self
    {
        if ($this->studentClasses->removeElement($studentClass)) {
            // set the owning side to null (unless already changed)
            if ($studentClass->getSchool() === $this) {
                $studentClass->setSchool(null);
            }
        }

        return $this;
    }
}
