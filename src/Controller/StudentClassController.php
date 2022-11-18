<?php

namespace App\Controller;

use App\Entity\Student;
use App\Entity\StudentClass;
use OpenApi\Attributes as OA;
use App\Repository\SchoolRepository;
use App\Repository\StudentRepository;
use App\Repository\ProfessorRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;
use Nelmio\ApiDocBundle\Annotation\Model;
use App\Repository\StudentClassRepository;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[Route('api/studentClass')]
class StudentClassController extends AbstractController
{
    /**
     * Récupérer toutes les classes
     */
    #[Route('/', name: 'app_student_class_index', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: "Retourne le tableau avec toutes les classes",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: StudentClass::class, groups: ['getAllStudentClass', "status"]))
        )
    )]
    public function index(
        StudentClassRepository $repository,
        SerializerInterface $serializer,
        TagAwareCacheInterface $cache): JsonResponse
    {
        $idCache = "getAllStudentClass";
        $studentClassSerialize = $cache->get($idCache, function(ItemInterface $item) use ($repository, $serializer) {
            $item->tag("allStudentClassCache");
            $studentClass = $repository->getAllStudentClass();
            $context = SerializationContext::create()->setGroups(['getAllStudentClass']);
            return $serializer->serialize($studentClass, 'json', $context);
        });

        return new JsonResponse($studentClassSerialize, Response::HTTP_OK, [], true);
    }

    /**
    *   Créer une classe
    */
    #[Route('/new', name: 'app_student_class_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits pour cette action')]
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
        ValidatorInterface $validator,
        TagAwareCacheInterface $cache): JsonResponse
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

        $cache->invalidateTags(["allStudentsCache", "allStudentClassCache"]);
        return new JsonResponse([
            'code' => Response::HTTP_CREATED,
            'message' => "The student class has been created"
        ], Response::HTTP_CREATED, []);
    }





    /**
     * Ajouter un utlisateur à une classe
     */
    #[Route('/{id_class}/students/{id_student}', name: 'app_student_class_add_user', methods: ['POST'])]
    #[ParamConverter('student', options: ['id' => 'id_student'])]
    #[ParamConverter('studentClass', options: ['id' => 'id_class'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits pour cette action')]
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
        EntityManagerInterface $entityManager,
        TagAwareCacheInterface $cache): JsonResponse
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
        $context = SerializationContext::create()->setGroups(['getStudent', 'status']);
        $studentsSerialize = $serializer->serialize($newStudent, 'json', $context);

        $cache->invalidateTags(["allStudentsCache", "allStudentClassCache", "allProfessorsCache"]);
        return new JsonResponse($studentsSerialize, Response::HTTP_OK, [], true);
    }




    /**
    *   Supprimer une classe (status = false)
    */
    #[Route('/{id_class}', name: 'app_student_class_delete_status', methods: ['DELETE'])]
    #[ParamConverter('studentClass', options: ['id' => 'id_class'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits pour cette action')]
    #[OA\Response(
        response: 200,
        description: "La classe a été supprimée",
    )]
    public function deleteStatus(
        StudentClass $studentClass,
        EntityManagerInterface $entityManager,
        TagAwareCacheInterface $cache): JsonResponse
    {
        $studentClass->setStatus(false);
        $entityManager->persist($studentClass);
        $entityManager->flush();

        $cache->invalidateTags(["allStudentsCache", "allStudentClassCache", "allProfessorsCache"]);
        return new JsonResponse([
            'code' => Response::HTTP_OK,
            'message' => "The student class has been deleted"
        ], Response::HTTP_OK, []);
    }
}