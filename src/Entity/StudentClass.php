<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\StudentClassRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: StudentClassRepository::class)]
class StudentClass
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getAllSchools", "getStudent"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getStudent"])]
    private ?string $graduation = null;

    #[ORM\Column]
    #[Groups(["getStudent"])]
    private ?int $number = null;

    #[ORM\Column]
    #[Groups(["status"])]
    private ?bool $status = null;

    #[ORM\ManyToOne(inversedBy: 'studentClasses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?School $school = null;

    #[ORM\OneToOne(inversedBy: 'studentClass', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Professor $professor = null;

    #[ORM\OneToMany(mappedBy: 'studentClass', targetEntity: Student::class)]
    private Collection $students;

    public function __construct()
    {
        $this->students = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGraduation(): ?string
    {
        return $this->graduation;
    }

    public function setGraduation(string $graduation): self
    {
        $this->graduation = $graduation;

        return $this;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(int $number): self
    {
        $this->number = $number;

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

    public function getSchool(): ?School
    {
        return $this->school;
    }

    public function setSchool(?School $school): self
    {
        $this->school = $school;

        return $this;
    }

    public function getProfessor(): ?Professor
    {
        return $this->professor;
    }

    public function setProfessor(Professor $professor): self
    {
        $this->professor = $professor;

        return $this;
    }

    /**
     * @return Collection<int, Student>
     */
    public function getStudents(): Collection
    {
        return $this->students;
    }

    public function addStudent(Student $student): self
    {
        if (!$this->students->contains($student)) {
            $this->students->add($student);
            $student->setStudentClass($this);
        }

        return $this;
    }

    public function removeStudent(Student $student): self
    {
        if ($this->students->removeElement($student)) {
            // set the owning side to null (unless already changed)
            if ($student->getStudentClass() === $this) {
                $student->setStudentClass(null);
            }
        }

        return $this;
    }
}
