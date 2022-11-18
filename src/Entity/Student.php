<?php

namespace App\Entity;

use App\Entity\Note;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\StudentRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: StudentRepository::class)]
class Student
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getAllStudents", "getStudent"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getAllStudents", "getStudent"])]
    private ?string $name = null;

    #[ORM\Column]
    #[Groups(["getAllStudents", "getStudent"])]
    private ?int $age = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getAllStudents", "getStudent"])]
    #[Assert\Email(message: "The email '{{ value }}' is not a valid email")]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getAllStudents", "getStudent"])]
    private ?string $gender = null;

    #[ORM\Column]
    #[Groups(["getAllStudents", "getStudent"])]
    private ?bool $handicap = null;

    #[ORM\Column]
    #[Groups(["status"])]
    private ?bool $status = null;

    #[ORM\ManyToOne(inversedBy: 'students')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(["getStudent"])]
    private ?StudentClass $studentClass = null;

    #[ORM\OneToMany(mappedBy: 'student', targetEntity: Note::class)]
    #[Groups(["getStudent"])]
    private Collection $notes;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["getStudent"])]
    private ?Address $address = null;

    public function __construct()
    {
        $this->notes = new ArrayCollection();
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

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(int $age): self
    {
        $this->age = $age;

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

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(string $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    public function isHandicap(): ?bool
    {
        return $this->handicap;
    }

    public function setHandicap(bool $handicap): self
    {
        $this->handicap = $handicap;

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

    public function setStudentClass(?StudentClass $studentClass): self
    {
        $this->studentClass = $studentClass;

        return $this;
    }

    /**
     * @return Collection<int, Note>
     */
    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function addNote(Note $note): self
    {
        if (!$this->notes->contains($note)) {
            $this->notes->add($note);
            $note->setStudent($this);
        }

        return $this;
    }

    public function removeNote(Note $note): self
    {
        if ($this->notes->removeElement($note)) {
            // set the owning side to null (unless already changed)
            if ($note->getStudent() === $this) {
                $note->setStudent(null);
            }
        }

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
}
