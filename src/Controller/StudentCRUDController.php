<?php

namespace App\Controller;

use App\Entity\Student;
use App\Form\StudentType;
use App\Repository\StudentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

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

    #[Route('/create', name: 'app_student_crud_new', methods: ['GET', 'POST'])]
    public function new(Request $request, StudentRepository $repository): JsonResponse
    {

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

    #[Route('/{id}/delete', name: 'app_student_crud_delete', methods: ['POST'])]
    public function delete(Request $request, Student $student, StudentRepository $repository): JsonResponse
    {
        
    }
}
