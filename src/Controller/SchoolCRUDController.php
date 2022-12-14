<?php

namespace App\Controller;

use App\Entity\School;
use App\Entity\StudentClass;
use OpenApi\Attributes as OA;
use App\Repository\SchoolRepository;
use App\Repository\AddressRepository;
use App\Repository\DirectorRepository;
use JMS\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

#[Route('api/schools')]
class SchoolCRUDController extends AbstractController
{
    /**
     * Récupérer toutes les écoles
     */
    #[Route('/', name: 'app_school_index', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: "Retourne un tableau des écoles",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: School::class, groups: ['getAllSchools', "status"]))
        )
    )]
    public function index(
        SchoolRepository $repository,
        SerializerInterface $serializer,
        TagAwareCacheInterface $cache): JsonResponse
    {
        $idCache = "getAllSchools";
        $schoolSerialize = $cache->get($idCache, function(ItemInterface $item) use ($repository, $serializer) {
            $item->tag("allSchoolsCache");
            $school = $repository->getAllSchools();
            $context = SerializationContext::create()->setGroups(['getAllSchools']);
            return $serializer->serialize($school, 'json', $context);
        });

        return new JsonResponse($schoolSerialize, Response::HTTP_OK, [], true);
    }





    /**
     * Créer une école
     */

    #[Route('/new', name: 'app_school_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits pour cette action')]
    #[OA\Response(
        response: 200,
        description: "Retourne 200"
    )]
    public function new(
        Request $request,
        AddressRepository $addressRepository,
        DirectorRepository $directorRepository,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
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
        $cache->invalidateTags(["allStudentsCache", "allStudentClassCache", "allProfessorsCache"]);
        return new JsonResponse([
            'code' => Response::HTTP_CREATED,
            'message' => "The school has been created"
        ], Response::HTTP_CREATED, []);
    }





    /**
     * Récupérer une école
     */
    #[Route('/{id}', name: 'app_school_show', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: "Retourne l'école",
        content: new Model(type: School::class, groups: ['getSchool', "status"])
    )]
    public function show(
        School $school,
        SerializerInterface $serializer): JsonResponse
    {
        if (!$school->isStatus()) {
            return new JsonResponse([
                'code' => Response::HTTP_NOT_FOUND,
                'message' => "The school doesn't exist"
            ], Response::HTTP_NOT_FOUND, []);
        }

        $context = SerializationContext::create()->setGroups(['getSchool', "status"]);
        $schoolSerialize = $serializer->serialize($school, 'json', $context);

        return new JsonResponse($schoolSerialize, Response::HTTP_OK, [], true);
    }




    /**
     * Ajouter une classe à une école
     */
    #[Route('/{id_school}/studentClass/{id_class}', name: 'app_school_add_studentClass', methods: ['POST'])]
    #[ParamConverter('school', options: ['id' => 'id_school'])]
    #[ParamConverter('studentClass', options: ['id' => 'id_class'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits pour cette action')]
    #[OA\Response(
        response: 200,
        description: "Retourne l'école",
        content: new Model(type: School::class, groups: ['getSchool', "status"])
    )]
    public function addStudentClass(
        School $school,
        SchoolRepository $schoolRepository,
        StudentClass $studentClass,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager): JsonResponse
    {
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
        $context = SerializationContext::create()->setGroups(['getSchool', "status"]);
        $schoolSerialize = $serializer->serialize($newSchool, 'json', $context);

        return new JsonResponse($schoolSerialize, Response::HTTP_OK, [], true);
    }




    /**
     * Supprimer une classe à une école
     */
    #[Route('/{id_school}/studentClass/{id_class}', name: 'app_school_delete_studentClass', methods: ['DELETE'])]
    #[ParamConverter('school', options: ['id' => 'id_school'])]
    #[ParamConverter('studentClass', options: ['id' => 'id_class'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits pour cette action')]
    #[OA\Response(
        response: 200,
        description: "L'école a été supprimée",
    )]
    public function deleteStudentClass(
        School $school,
        StudentClass $studentClass,
        EntityManagerInterface $entityManager,  TagAwareCacheInterface $cache): JsonResponse
    {
        if (!$school->isStatus()) {
            return new JsonResponse([
                'code' => Response::HTTP_NOT_FOUND,
                'message' => "The school doesn't exist"
            ], Response::HTTP_NOT_FOUND, []);
        }

        $studentClass->setStatus(false);
        $entityManager->persist($studentClass);
        $entityManager->flush();
        $cache->invalidateTags(["allStudentsCache", "allStudentClassCache", "allProfessorsCache"]);
        return new JsonResponse([
            'code' => Response::HTTP_CREATED,
            'message' => "The class has been delete"
        ], Response::HTTP_ACCEPTED, []);
    }





    /**
     * Supprimer une école (status = false)
     */
    #[Route('/{id_school}', name: 'app_school_delete', methods: ['DELETE'])]
    #[ParamConverter('school', options: ['id' => 'id_school'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits pour cette action')]
    #[OA\Response(
        response: 200,
        description: "Retourne 200"
    )]
    public function deleteStatus(
        School $school,
        EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $school->setStatus(false);
        $entityManager->persist($school);
        $entityManager->flush();
        $cache->invalidateTags(["allStudentsCache", "allStudentClassCache", "allProfessorsCache"]);
        return new JsonResponse([
            'code' => Response::HTTP_OK,
            'message' => "The school is delete"
        ], Response::HTTP_OK, []);
    }





    /**
     * Supprimer une école définitivement
     */
    #[Route('/{id_school}/delete', name: 'app_school_delete_definitely', methods: ['DELETE'])]
    #[ParamConverter('school', options: ['id' => 'id_school'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits pour cette action')]
    #[OA\Response(
        response: 200,
        description: "Retourne 200"
    )]
    public function delete(School $school, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $entityManager->remove($school);
        $entityManager->flush();
        $cache->invalidateTags(["allStudentsCache", "allStudentClassCache", "allProfessorsCache"]);
        return new JsonResponse([
            'code' => Response::HTTP_OK,
            'message' => "The school is delete"
        ], Response::HTTP_OK, []);
    }
}
