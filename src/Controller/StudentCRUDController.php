<?php

namespace App\Controller;

use App\Entity\Student;
use App\Repository\StudentClassRepository;
use App\Repository\StudentRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/students')]
class StudentCRUDController extends AbstractController
{
    #[Route('/', name: 'app_student_crud_index', methods: ['GET'])]
    public function index(StudentRepository $repository, SerializerInterface $serializer): JsonResponse
    {
        $students = $repository->findAll();
        $studentsSerialize = $serializer->serialize($students, 'json', ['groups' => ['getAllStudents', "status"]]);

        return new JsonResponse($studentsSerialize, Response::HTTP_OK, [], true);
    }

    #[Route('/create', name: 'app_student_crud_new', methods: ['POST'])]
    public function new(Request $request, StudentClassRepository $studentClassRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $bodyResponse = $request->toArray();
        $newStudent = $serializer->deserialize(
            $request->getContent(),
            Student::class,
            'json'
        );
        $studentClass = $studentClassRepository->find(['id' => $bodyResponse['studentClass']]);

        $errors = $validator->validate($newStudent);
        if ($errors->count() > 0) {
            return new JsonResponse([
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => $errors[0]->getMessage()
            ], Response::HTTP_NOT_FOUND, []);
            
            
            return new JsonResponse($serializer->serialize($errors, "json"), Response::HTTP_BAD_REQUEST, [], true);
        }

        if (!$studentClass) {
            return new JsonResponse([
                'code' => Response::HTTP_NOT_FOUND,
                'message' => "The student class doesn't exist"
            ], Response::HTTP_NOT_FOUND, []);
        }

        $student = $serializer->deserialize($request->getContent(), Student::class, 'json', []);
        $student->setStudentClass($studentClass);
        
        $entityManager->persist($student);
        $entityManager->flush();

        return new JsonResponse([
            'code' => Response::HTTP_CREATED,
            'message' => "The student has been created"
        ], Response::HTTP_CREATED, []);
    }

    #[Route('/{id}', name: 'app_student_crud_show', methods: ['GET'])]
    public function show(Student $student, SerializerInterface $serializer): JsonResponse
    {
       $studentsSerialize = $serializer->serialize($student, 'json', ['groups' => ['getStudent', "status"]]);

        return new JsonResponse($studentsSerialize, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}/edit', name: 'app_student_crud_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Student $student, StudentRepository $repository): JsonResponse
    {

    }

    #[Route('/{id_student}/delete', name: 'app_student_crud_delete', methods: ['DELETE'])]
    #[ParamConverter('student', options: ['id' => 'id_student'])]
    public function delete(Student $student, EntityManagerInterface $entityManager): JsonResponse
    {
        $student->setStatus(false);
        $entityManager->persist($student);
        $entityManager->flush();

        
        return new JsonResponse([
            'code' => Response::HTTP_OK,
            'message' => "The entity is delete"
        ], Response::HTTP_OK, []);
    }
}
