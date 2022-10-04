<?php

namespace App\DataFixtures;

use App\Entity\Student;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker;

class AppFixtures extends Fixture
{

     /**
     * Faker Generator
     * 
     * @var Generator
     */
    private Generator $faker;


    public function __construct(){
        $this->faker = Factory::create('fr_FR');
    }

    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);

        $school = new School();
        $school->setName($this->faker->firstName());
        $school->setEmail($this->faker->email());
        $school->setPhone($this->faker->phone());

        

        $students = array();
        for ($i = 0; $i < 10; $i++) {
            $students[$i] = new Student();
            $students[$i]->setName($this->faker->firstName());
            $students[$i]->setEmail($this->faker->email());
            $students[$i]->setAge($this->faker->age());
            $students[$i]->setGender($this->faker->gender());
            $students[$i]->setPhone($this->faker->phoneNumber());
            $students[$i]->setStatus(true);
            $students[$i]->setHandicap(false);
           
        }
        $manager->flush();
    }
}
