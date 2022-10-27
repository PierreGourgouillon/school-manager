<?php

namespace App\Controller;

use App\Entity\Student;
use App\Entity\StudentClass;
use App\Repository\StudentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

#[Route('/studentClass')]
class StudentClassController extends AbstractController
{
    #[Route('/{id_class}/students/{id_student}', name: 'app_student_class_add_user', methods: ['POST'])]
    #[ParamConverter('student', options: ['id' => 'id_student'])]
    #[ParamConverter('studentClass', options: ['id' => 'id_class'])]
    public function addUser(Student $student, StudentRepository $studentRepository, StudentClass $studentClass, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse {

        if (!$studentClass->isStatus()) {
            return new JsonResponse([
                'status' => 'error',
                'message' => "StudentClass doesn't exist"
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$student->isStatus()) {
            return new JsonResponse([
                'status' => 'error',
                'message' => "Student doesn't exist"
            ], Response::HTTP_BAD_REQUEST);
        }

        $student->setStudentClass($studentClass);
        $entityManager->persist($student);
        $entityManager->flush();

        $newStudent = $studentRepository->findOneBy(['id' => $student->getId()]);
        $studentsSerialize = $serializer->serialize($newStudent, 'json', ['groups' => ['getStudent', "status"]]);

        return new JsonResponse($studentsSerialize, Response::HTTP_OK, [], true);
    }
}