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
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;

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
    public function index(StudentRepository $repository, SerializerInterface $serializer): JsonResponse
    {
        $students = $repository->getAllStudents();
        $studentsSerialize = $serializer->serialize($students, 'json', ['groups' => ['getAllStudents', "status"]]);

        return new JsonResponse($studentsSerialize, Response::HTTP_OK, [], true);
    }





    /**
     * Créer un étudiant
     */
    #[Route('/create', name: 'app_student_crud_new', methods: ['POST'])]
    #[OA\Response(
        response: 201,
        description: "L'étudiant a bien été créé"
    )]
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





    /**
     * Récupérer un étudiant
     */
    #[Route('/{id}', name: 'app_student_crud_show', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: "Retourne l'étudiant",
        content: new Model(type: Student::class, groups: ['getStudent', "status"])
    )]
    public function show(Student $student, SerializerInterface $serializer): JsonResponse
    {
        if (!$student->isStatus()) {
            return new JsonResponse([
                'code' => Response::HTTP_NOT_FOUND,
                'message' => "The student doesn't exist"
            ], Response::HTTP_NOT_FOUND, []);
        }

       $studentsSerialize = $serializer->serialize($student, 'json', ['groups' => ['getStudent', "status"]]);

        return new JsonResponse($studentsSerialize, Response::HTTP_OK, [], true);
    }





    /**
     * Modifier un étudiant
     */
    #[Route('/{id}/edit', name: 'app_student_crud_edit', methods: ['POST'])]
    #[OA\Response(
        response: 200,
        description: "Retourne l'étudiant modifié",
        content: new Model(type: Student::class, groups: ['getStudent', "status"])
    )]
    public function edit(Request $request, Student $student, StudentRepository $repository, EntityManagerInterface $entityManager,  SerializerInterface $serializer): JsonResponse
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
        $studentsSerialize = $serializer->serialize($newStudent, 'json', ['groups' => ['getStudent', "status"]]);

       return new JsonResponse($studentsSerialize, Response::HTTP_OK, [], true);
    }





    /**
     * Supprimer un étudiant (status = false)
     */
    #[Route('/{id_student}', name: 'app_student_crud_delete', methods: ['DELETE'])]
    #[ParamConverter('student', options: ['id' => 'id_student'])]
    #[OA\Response(
        response: 200,
        description: "Supprime l'étudiant"
    )]
    public function deleteStatus(Student $student, EntityManagerInterface $entityManager): JsonResponse
    {
        $student->setStatus(false);
        $entityManager->persist($student);

        $entityManager->flush();

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
    #[OA\Response(
        response: 200,
        description: "Supprime l'étudiant"
    )]
    public function delete(Student $student, EntityManagerInterface $entityManager): JsonResponse
    {

        $entityManager->remove($student);
        $entityManager->flush();

        return new JsonResponse([
            'code' => Response::HTTP_OK,
            'message' => "The entity is delete"
        ], Response::HTTP_OK, []);
    }
}
