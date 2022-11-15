<?php

namespace App\DataFixtures;

use Faker;
use Faker\Factory;
use App\Entity\Note;
use App\Entity\User;
use Faker\Generator;
use App\Entity\School;
use App\Entity\Address;
use App\Entity\Student;
use App\Entity\Director;
use App\Entity\Professor;
use App\Entity\StudentClass;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{

     /**
     * Faker Generator
     * 
     * @var Generator
     */
    private Generator $faker;

        /**
     * Classe Hashant le password
     * 
     * @var UserPasswordHasherInterface
     */
    private $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher){
        $this->faker = Factory::create('fr_FR');
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $addresses = [];
        for ($i = 0; $i < 10000; $i++) {
            $address = new Address();
            $address->setStreet($this->faker->streetAddress)
            ->setCity($this->faker->city)
            ->setPostalcode($this->faker->postcode)
            ->setCountry($this->faker->country)
            ->setStatus($this->faker->boolean);

            $manager->persist($address);
            array_push($addresses, $address);
        }

        $director = new Director();
        $director->setAddress($this->faker->randomElement($addresses))
        ->setEmail($this->faker->email)
        ->setName($this->faker->name)
        ->setNumber(1)
        ->setStatus(true);

        $professors = [];
        for ($i = 0; $i < 100; $i++) {
            $professor = new Professor();
            $professor->setAddress($this->faker->randomElement($addresses))
            ->setName($this->faker->name)
            ->setSubject($this->faker->randomElement(['Maths', 'Francais', 'Sport', 'Anglais', 'Histoire']))
            ->setStatus(true);

            $manager->persist($professor);
            array_push($professors, $professor);
        }

        $school = new School();
        $school->setName($this->faker->firstName())
        ->setEmail($this->faker->email())
        ->setDirector($director)
        ->setAddress($this->faker->randomElement($addresses))
        ->setStatus(true);

        $studentsClasses = [];
        for ($i = 0; $i < 10; $i++) {
            $studentClass = new StudentClass();
            $studentClass->setGraduation($this->faker->randomElement(['6', '5', '4', '3', '2']))
            ->setNumber($this->faker->randomDigit)
            ->setSchool($school)
            ->setProfessor(($this->faker->randomElement($professors)))
            ->setStatus($this->faker->boolean);

            $manager->persist($studentClass);
            array_push($studentsClasses, $studentClass);
        }

        $students = array();
        for ($i = 0; $i < 10; $i++) {
            $student = new Student();
            $student->setName($this->faker->firstName())
            ->setEmail($this->faker->email())
            ->setAge(10)
            ->setGender($this->faker->randomElement(['Homme', 'Femme']))
            ->setStatus(true)
            ->setAddress($this->faker->randomElement($addresses))
            ->setStudentClass($this->faker->randomElement($studentsClasses))
            ->setHandicap(false);

            array_push($students, $student);
        }

        $notes = [];
        for ($i = 0; $i < 20; $i++) {
            $note = new Note();
            $note->setValue($this->faker->randomFloat(2, 0, 20))
            ->setSubject($this->faker->randomElement(['Maths', 'Francais', 'Sport', 'Anglais', 'Histoire']))
            ->setStudent($this->faker->randomElement($students))
            ->setStatus($this->faker->boolean);

            array_push($notes, $note);
        }

        $school->addStudentClass($this->faker->randomElement($studentsClasses));

        foreach($students as $student){
            $manager->persist($student);
        }

        foreach($notes as $note){
            $manager->persist($note);
        }

        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setUserName($this->faker->userName)
            ->setRoles(["ROLE_USER"])
            ->setPassword($this->userPasswordHasher->hashPassword($user, "password"));
            
            $manager->persist($user);
        }

        $admin = new User();
        $admin->setUserName("admin")
        ->setRoles(["ROLE_ADMIN"])
        ->setPassword($this->userPasswordHasher->hashPassword($admin, "password"));
        $manager->persist($admin);

        $manager->persist($director);
        $manager->persist($school);
        $manager->flush();
    }
}
