<?php

namespace App\Controller;

use App\Entity\School;
use App\Entity\Student;
use App\Entity\StudentClass;
use App\Repository\ProfessorRepository;
use App\Repository\SchoolRepository;
use App\Repository\StudentRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

#[Route('api/studentClass')]
class StudentClassController extends AbstractController
{
    #[Route('/studentClass/new', name: 'app_student_class_create', methods: ['POST'])]
    public function create(Request $request, SerializerInterface $serializer, SchoolRepository $schoolRepository, ProfessorRepository $professorRepository, EntityManagerInterface $entityManager, ValidatorInterface $validator): JsonResponse {
        $bodyResponse = $request->toArray();
        $newStudentClass = $serializer->deserialize(
            $request->getContent(),
            StudentClass::class,
            'json'
        );

        $school = $schoolRepository->find(['id' => $bodyResponse['school_id']]);

        if (!$school) {
            return new JsonResponse([
                'code' => Response::HTTP_NOT_FOUND,
                'message' => "The school_id value doesn't exist"
            ], Response::HTTP_NOT_FOUND, []);
        }

        $newStudentClass->setSchool($school);

        $professor = $professorRepository->find(['id' => $bodyResponse['professor_id']]);

        if (!$professor) {
            return new JsonResponse([
                'code' => Response::HTTP_NOT_FOUND,
                'message' => "The professor_id value doesn't exist"
            ], Response::HTTP_NOT_FOUND, []);
        }

        $newStudentClass->setProfessor($professor);

        $errors = $validator->validate($newStudentClass);
        if ($errors->count() > 0) {
            return new JsonResponse([
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => $errors[0]->getMessage()
            ], Response::HTTP_NOT_FOUND, []);
        }

        $newStudentClass->setStatus(true);
        $entityManager->persist($newStudentClass);
        $entityManager->flush();

        return new JsonResponse([
            'code' => Response::HTTP_CREATED,
            'message' => "The student class has been created"
        ], Response::HTTP_CREATED, []);
    }

    #[Route('/studentClass/{id_class}/students/{id_student}', name: 'app_student_class_add_user', methods: ['POST'])]
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

    #[Route('/studentClass/{id_class}', name: 'app_student_class_delete_status', methods: ['DELETE'])]
    #[ParamConverter('studentClass', options: ['id' => 'id_class'])]
    public function deleteStatus(StudentClass $studentClass, EntityManagerInterface $entityManager): JsonResponse {
        $studentClass->setStatus(false);
        $entityManager->persist($studentClass);
        $entityManager->flush();

        return new JsonResponse([
            'code' => Response::HTTP_OK,
            'message' => "The student class has been deleted"
        ], Response::HTTP_OK, []);
    }
}