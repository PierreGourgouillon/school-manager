<?php

namespace App\Controller;

use App\Entity\Student;
use App\Entity\StudentClass;
use OpenApi\Attributes as OA;
use App\Repository\StudentRepository;
use JMS\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
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

#[Route('api/students')]
class StudentCRUDController extends AbstractController
{
    /**
     * Récupérer tous les étudiants
     */
    #[Route('/', name: 'app_student_crud_index', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: "Retourne le tableau avec tous les étudiants",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Student::class, groups: ['getAllStudents', "status"]))
        )
    )]
    public function index(
        StudentRepository $repository,
        SerializerInterface $serializer,
        TagAwareCacheInterface $cache): JsonResponse
    {
        $idCache = "getAllStudents";
        $studentsSerialize = $cache->get($idCache, function(ItemInterface $item) use ($repository, $serializer) {
            $item->tag("allStudentsCache");
            $students = $repository->getAllStudents();
            $context = SerializationContext::create()->setGroups(['getAllStudents']);
            return $serializer->serialize($students, 'json', $context);
        });

        return new JsonResponse($studentsSerialize, Response::HTTP_OK, [], true);
    }

    /**
     * Récupère tous les étudiants d'une classe
     */
    #[Route('/{class_id}/filter', name: 'app_student_crud_filter', methods: ['GET'])]
    #[ParamConverter('studentClass', options: ['id' => 'class_id'])]
    #[OA\Response(
        response: 200,
        description: "Retourne les étudiants d'une même classe",
        content: new Model(type: Student::class, groups: ['getStudent', "status"])
    )]
    public function Filter(
        StudentClass $studentClass,
        StudentRepository $studentRepository,
        SerializerInterface $serializer): JsonResponse
    {
        $newList = $studentRepository->findBy(['studentClass' => $studentClass->getId()]);
        $context = SerializationContext::create()->setGroups(['getStudent', "status"]);
        $studentsSerialize = $serializer->serialize($newList, 'json', $context);

        return new JsonResponse($studentsSerialize, Response::HTTP_OK, [], true);
    }


    /**
     * Créer un étudiant
     */
    #[Route('/create', name: 'app_student_crud_new', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits pour cette action')]
    #[OA\Response(
        response: 201,
        description: "L'étudiant a bien été créé"
    )]
    public function new(
        Request $request,
        StudentClassRepository $studentClassRepository,
        SerializerInterface $serializer,
        TagAwareCacheInterface $cache,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator): Response
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

        $student = $serializer->deserialize($request->getContent(), Student::class, 'json');
        $student->setStudentClass($studentClass);
        
        $entityManager->persist($student);
        $entityManager->flush();

        $cache->invalidateTags(["allStudentsCache", "allStudentClassCache"]);

        return new JsonResponse([
            'code' => Response::HTTP_CREATED,
            'message' => "The student has been created"
        ], Response::HTTP_CREATED, []);
    }





    /**
     * Récupérer un étudiant
     */
    #[Route('/{id_student}', name: 'app_student_crud_show', methods: ['GET'])]
    #[ParamConverter('student', options: ['id' => 'id_student'])]
    #[OA\Response(
        response: 200,
        description: "Retourne l'étudiant",
        content: new Model(type: Student::class, groups: ['getStudent', "status"])
    )]
    public function show(
        Student $student,
        SerializerInterface $serializer): JsonResponse
    {
        if (!$student->isStatus()) {
            return new JsonResponse([
                'code' => Response::HTTP_NOT_FOUND,
                'message' => "The student doesn't exist"
            ], Response::HTTP_NOT_FOUND, []);
        }

        $context = SerializationContext::create()->setGroups('getStudent');
        $studentsSerialize = $serializer->serialize($student, 'json', $context);

        return new JsonResponse($studentsSerialize, Response::HTTP_OK, [], true);
    }

    /**
     * Modifier un étudiant
     */
    #[Route('/{id_student}/edit', name: 'app_student_crud_edit', methods: ['POST'])]
    #[ParamConverter('student', options: ['id' => 'id_student'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Response(
        response: 200,
        description: "Retourne l'étudiant modifié",
        content: new Model(type: Student::class, groups: ['getStudent', "status"])
    )]
    public function edit(
        Request $request,
        Student $student,
        StudentRepository $repository,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        TagAwareCacheInterface $cache): JsonResponse
    {
        if (!$student->isStatus()) {
            return new JsonResponse([
                'code' => Response::HTTP_NOT_FOUND,
                'message' => "The student doesn't exist"
            ], Response::HTTP_NOT_FOUND, []);
        }

        $bodyResponse = $request->toArray();
        if (array_key_exists('name', $bodyResponse)) {
            $student->setName($bodyResponse['name']);
        }

        if (array_key_exists('email', $bodyResponse)) {
            $student->setEmail($bodyResponse['email']);
        }

        if (array_key_exists('age', $bodyResponse)) {
            $student->setAge($bodyResponse['age']);
        }

        if (array_key_exists('gender', $bodyResponse)) {
            $student->setGender($bodyResponse['gender']);
        }

        if (array_key_exists('handicap', $bodyResponse)) {
            $student->setHandicap($bodyResponse['handicap']);
        }

        if (array_key_exists('address', $bodyResponse)) {
            $student->setAddress($bodyResponse['address']);
        }

        $entityManager->persist($student);
        $entityManager->flush();

        $newStudent = $repository->findOneBy(['id' => $student->getId()]);
        $context = SerializationContext::create()->setGroups(['getStudent', 'status']);
        $studentsSerialize = $serializer->serialize($newStudent, 'json', $context);
        
        $cache->invalidateTags(["allStudentsCache", "allStudentClassCache"]);

        return new JsonResponse($studentsSerialize, Response::HTTP_OK, [], true);
    }

    /**
     * Supprimer un étudiant (status = false)
     */
    #[Route('/{id_student}', name: 'app_student_crud_delete', methods: ['DELETE'])]
    #[ParamConverter('student', options: ['id' => 'id_student'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits pour cette action')]
    #[OA\Response(
        response: 200,
        description: "Supprime l'étudiant"
    )]
    public function deleteStatus(Student $student, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $student->setStatus(false);
        $entityManager->persist($student);

        $entityManager->flush();

        $cache->invalidateTags(["allStudentsCache", "allStudentClassCache"]);
        return new JsonResponse([
            'code' => Response::HTTP_OK,
            'message' => "The entity is delete"
        ], Response::HTTP_OK, []);
    }

    /**
     * Supprimer un étudiant définitivement
    */
    #[Route('/{id_student}/delete', name: 'app_student_crud_delete_definitely', methods: ['DELETE'])]
    #[ParamConverter('student', options: ['id' => 'id_student'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits pour cette action')]
    #[OA\Response(
        response: 200,
        description: "Supprime l'étudiant"
    )]
    public function delete(
        Student $student,
        EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $entityManager->remove($student);
        $entityManager->flush();

        $cache->invalidateTags(["allStudentsCache", "allStudentClassCache"]);
        return new JsonResponse([
            'code' => Response::HTTP_OK,
            'message' => "The entity is delete"
        ], Response::HTTP_OK, []);
    }
}
