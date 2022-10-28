<?php

namespace App\Controller;

use App\Entity\School;
use App\Form\SchoolType;
use App\Entity\StudentClass;
use App\Repository\SchoolRepository;
use App\Repository\AddressRepository;
use App\Repository\DirectorRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\StudentClassRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

#[Route('/schools')]
class SchoolCRUDController extends AbstractController
{
    #[Route('/', name: 'app_school_index', methods: ['GET'])]
    public function index(SchoolRepository $schoolRepository, SerializerInterface $serializer): JsonResponse
    {
        $schools = $schoolRepository->findAllValidSchools();
        $schoolSerialize = $serializer->serialize($schools, 'json', ['groups' => ['getAllSchools', 'status']]);

        return new JsonResponse($schoolSerialize, Response::HTTP_OK, [], true);
    }

    #[Route('/new', name: 'app_school_new', methods: ['GET', 'POST'])]
    public function new(Request $request, AddressRepository $addressRepository, DirectorRepository $directorRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager, ValidatorInterface $validator): JsonResponse
    {
        $bodyResponse = $request->toArray();
        $newSchool = $serializer->deserialize(
            $request->getContent(),
            School::class,
            'json'
        );
        $address = $addressRepository->find(['id' => $bodyResponse['address_id']]);

        if (!$address) {
            return new JsonResponse([
                'code' => Response::HTTP_NOT_FOUND,
                'message' => "The address_id value doesn't exist"
            ], Response::HTTP_NOT_FOUND, []);
        }

        $newSchool->setAddress($address);

        $director = $directorRepository->find(['id' => $bodyResponse['director_id']]);

        if (!$director) {
            return new JsonResponse([
                'code' => Response::HTTP_NOT_FOUND,
                'message' => "The director_id value doesn't exist"
            ], Response::HTTP_NOT_FOUND, []);
        }

        $newSchool->setDirector($director);

        $errors = $validator->validate($newSchool);
        if ($errors->count() > 0) {
            return new JsonResponse([
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => $errors[0]->getMessage()
            ], Response::HTTP_NOT_FOUND, []);
        }
        
        $newSchool->setStatus(true);
        $entityManager->persist($newSchool);
        $entityManager->flush();

        return new JsonResponse([
            'code' => Response::HTTP_CREATED,
            'message' => "The school has been created"
        ], Response::HTTP_CREATED, []);
    }

    #[Route('/{id}', name: 'app_school_show', methods: ['GET'])]
    public function show(School $school, SerializerInterface $serializer): JsonResponse
    {
        if (!$school->isStatus()) {
            return new JsonResponse([
                'code' => Response::HTTP_NOT_FOUND,
                'message' => "The school doesn't exist"
            ], Response::HTTP_NOT_FOUND, []);
        }
        $schoolSerialize = $serializer->serialize($school, 'json', ['groups' => ['getSchool', "status"]]);

        return new JsonResponse($schoolSerialize, Response::HTTP_OK, [], true);
    }

    #[Route('/{id_school}/studentClass/{id_class}', name: 'app_school_add_studentClass', methods: ['POST'])]
    #[ParamConverter('school', options: ['id' => 'id_school'])]
    #[ParamConverter('studentClass', options: ['id' => 'id_class'])]
    public function addStudentClass(School $school, SchoolRepository $schoolRepository, StudentClass $studentClass, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse {
        if (!$school->isStatus()) {
            return new JsonResponse([
                'code' => Response::HTTP_NOT_FOUND,
                'message' => "The school doesn't exist"
            ], Response::HTTP_NOT_FOUND, []);
        }

        if (!$studentClass->isStatus()) {
            return new JsonResponse([
                'code' => Response::HTTP_NOT_FOUND,
                'message' => "The studentClass doesn't exist"
            ], Response::HTTP_NOT_FOUND, []);
        }

        $school->addStudentClass($studentClass);
        $entityManager->persist($school);
        $entityManager->flush();

        $newSchool = $schoolRepository->findOneBy(['id' => $school->getId()]);
        $studentsSerialize = $serializer->serialize($newSchool, 'json', ['groups' => ['getSchool', "status"]]);

        return new JsonResponse($studentsSerialize, Response::HTTP_OK, [], true);
    }

    #[Route('/{id_school}/studentClass/{id_class}', name: 'app_school_add_studentClass', methods: ['DELETE'])]
    #[ParamConverter('school', options: ['id' => 'id_school'])]
    #[ParamConverter('studentClass', options: ['id' => 'id_class'])]
    public function deleteStudentClass(School $school, StudentClass $studentClass, EntityManagerInterface $entityManager): JsonResponse {
        if (!$school->isStatus()) {
            return new JsonResponse([
                'code' => Response::HTTP_NOT_FOUND,
                'message' => "The school doesn't exist"
            ], Response::HTTP_NOT_FOUND, []);
        }

        $studentClass->setStatus(false);
        $entityManager->persist($studentClass);
        $entityManager->flush();

        return new JsonResponse([
            'code' => Response::HTTP_CREATED,
            'message' => "The class has been delete"
        ], Response::HTTP_ACCEPTED, []);
    }

    #[Route('/{id_school}', name: 'app_school_delete', methods: ['DELETE'])]
    #[ParamConverter('school', options: ['id' => 'id_school'])]
    public function deleteStatus(School $school, EntityManagerInterface $entityManager): JsonResponse
    {
        $school->setStatus(false);
        $entityManager->persist($school);
        $entityManager->flush();

        return new JsonResponse([
            'code' => Response::HTTP_OK,
            'message' => "The school is delete"
        ], Response::HTTP_OK, []);
    }

    #[Route('/{id_school}/delete', name: 'app_school_delete', methods: ['DELETE'])]
    #[ParamConverter('school', options: ['id' => 'id_school'])]
    public function delete(School $school, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($school);
        $entityManager->flush();

        return new JsonResponse([
            'code' => Response::HTTP_OK,
            'message' => "The school is delete"
        ], Response::HTTP_OK, []);
    }
}
