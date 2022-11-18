<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ProfessorRepository;
use JMS\Serializer\Annotation\Groups;


#[ORM\Entity(repositoryClass: ProfessorRepository::class)]
class Professor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getAllProfessors",  "getProfessor"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getAllProfessors", "getProfessor"])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getAllProfessors", "getProfessor"])]
    private ?string $subject = null;

    #[ORM\Column]
    #[Groups(["status"])]
    private ?bool $status = null;

    #[ORM\OneToOne(mappedBy: 'professor', cascade: ['persist', 'remove'])]
    #[Groups(["getAllProfessors", "getProfessor"])]
    private ?StudentClass $studentClass = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["getAllProfessors", "getProfessor"])]
    private ?Address $address = null;

    #[ORM\OneToOne(inversedBy: 'professor', cascade: ['persist', 'remove'])]
    private ?User $user = null;


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

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

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

    public function getStudentClass(): ?StudentClass
    {
        return $this->studentClass;
    }

    public function setStudentClass(StudentClass $studentClass): self
    {
        // set the owning side of the relation if necessary
        if ($studentClass->getProfessor() !== $this) {
            $studentClass->setProfessor($this);
        }

        $this->studentClass = $studentClass;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
