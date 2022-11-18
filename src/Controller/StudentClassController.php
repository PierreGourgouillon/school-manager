<?php

namespace App\Controller;

use App\Entity\Student;
use App\Entity\StudentClass;
use App\Repository\ProfessorRepository;
use App\Repository\SchoolRepository;
use App\Repository\StudentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;

#[Route('api/studentClass')]
class StudentClassController extends AbstractController
{
    /**
    *   Créer une classe
    */
    #[Route('/studentClass/new', name: 'app_student_class_create', methods: ['POST'])]
    #[OA\Response(
        response: 201,
        description: "La classe a été créée",
    )]
    public function create(
        Request $request,
        SerializerInterface $serializer,
        SchoolRepository $schoolRepository,
        ProfessorRepository $professorRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator): JsonResponse
    {
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





    /**
     * Ajouter un utlisateur à une classe
     */
    #[Route('/{id_class}/students/{id_student}', name: 'app_student_class_add_user', methods: ['POST'])]
    #[Route('/studentClass/{id_class}/students/{id_student}', name: 'app_student_class_add_user', methods: ['POST'])]
    #[ParamConverter('student', options: ['id' => 'id_student'])]
    #[ParamConverter('studentClass', options: ['id' => 'id_class'])]
    #[OA\Response(
        response: 200,
        description: "Retourne l'utisateur ajouté à la classe",
        content: new Model(type: Student::class, groups: ['getStudent', "status"])
    )]
    public function addUser(
        Student $student,
        StudentRepository $studentRepository,
        StudentClass $studentClass,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager): JsonResponse
    {
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
        $context = SerializationContext::create()->setGroups(['getStudent', "status"]);
        $studentsSerialize = $serializer->serialize($newStudent, 'json', $context);

        return new JsonResponse($studentsSerialize, Response::HTTP_OK, [], true);
    }




    /**
    *   Supprimer une classe (status = false)
    */
    #[Route('/studentClass/{id_class}', name: 'app_student_class_delete_status', methods: ['DELETE'])]
    #[ParamConverter('studentClass', options: ['id' => 'id_class'])]
    #[OA\Response(
        response: 200,
        description: "La classe a été supprimée",
    )]
    public function deleteStatus(
        StudentClass $studentClass,
        EntityManagerInterface $entityManager): JsonResponse
    {
        $studentClass->setStatus(false);
        $entityManager->persist($studentClass);
        $entityManager->flush();

        return new JsonResponse([
            'code' => Response::HTTP_OK,
            'message' => "The student class has been deleted"
        ], Response::HTTP_OK, []);
    }
}