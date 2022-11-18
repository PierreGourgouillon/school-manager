<?php

namespace App\Controller;

use App\Entity\Professor;
use OpenApi\Attributes as OA;
use App\Repository\ProfessorRepository;
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

#[Route('api/professor')]
class ProfessorCRUDController extends AbstractController
{
  /**
     * Récupérer tous les professeurs
     */
    #[Route('/', name: 'app_professor_index', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: "Retourne un tableau avec tous les professeurs",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Professor::class, groups: ['getAllProfessors', "status"]))
        )
    )]
    public function index(
        ProfessorRepository $repository,
        SerializerInterface $serializer,
        TagAwareCacheInterface $cache): JsonResponse
    {
        $cache->invalidateTags(["allProfessorsCache"]);
        $idCache = "getAllProfessors";
        $ProfessorsSerialize = $cache->get($idCache, function(ItemInterface $item) use ($repository, $serializer) {
            $item->tag("allProfessorsCache");
            $Professors = $repository->getAllProfessors();
            $context = SerializationContext::create()->setGroups(['getAllProfessors']);

            return $serializer->serialize($Professors, 'json', $context);
        });

        return new JsonResponse($ProfessorsSerialize, Response::HTTP_OK, [], true);
    }





    /**
     * Créer un professeur
     */
    #[Route('/create', name: 'app_professor_new', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits pour cette action')]
    #[OA\Response(
        response: 201,
        description: "Le professeur a bien été créé"
    )]
    public function new(
        Request $request,
        ProfessorRepository $ProfessorClassRepository,
        SerializerInterface $serializer,
        TagAwareCacheInterface $cache,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator): Response
    {
        $bodyResponse = $request->toArray();
        $newProfessor = $serializer->deserialize(
            $request->getContent(),
            Professor::class,
            'json'
        );
        $ProfessorClass = $ProfessorClassRepository->find(['id' => $bodyResponse['ProfessorClass']]);

        $errors = $validator->validate($newProfessor);
        if ($errors->count() > 0) {
            return new JsonResponse([
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => $errors[0]->getMessage()
            ], Response::HTTP_NOT_FOUND, []);

            return new JsonResponse($serializer->serialize($errors, "json"), Response::HTTP_BAD_REQUEST, [], true);
        }

        if (!$ProfessorClass) {
            return new JsonResponse([
                'code' => Response::HTTP_NOT_FOUND,
                'message' => "The Professor class doesn't exist"
            ], Response::HTTP_NOT_FOUND, []);
        }

        $Professor = $serializer->deserialize($request->getContent(), Professor::class, 'json');
        $Professor->setProfessorClass($ProfessorClass);
        
        $entityManager->persist($Professor);
        $entityManager->flush();

        $cache->invalidateTags(["allProfessorsCache"]);

        return new JsonResponse([
            'code' => Response::HTTP_CREATED,
            'message' => "The Professor has been created"
        ], Response::HTTP_CREATED, []);
    }


    /**
     * Récupérer un professeur
     */
    #[Route('/{id_professor}', name: 'app_professor_show', methods: ['GET'])]
    #[ParamConverter('professor', options: ['id' => 'id_professor'])]
    #[OA\Response(
        response: 200,
        description: "Retourne le professeur",
        content: new Model(type: Professor::class, groups: ['getProfessor', "status"])
    )]
    public function show(
        Professor $professor,
        SerializerInterface $serializer): JsonResponse
    {
        if (!$professor->isStatus()) {
            return new JsonResponse([
                'code' => Response::HTTP_NOT_FOUND,
                'message' => "The student doesn't exist"
            ], Response::HTTP_NOT_FOUND, []);
        }

        $context = SerializationContext::create()->setGroups('getProfessor');
        $studentsSerialize = $serializer->serialize($professor, 'json', $context);

        return new JsonResponse($studentsSerialize, Response::HTTP_OK, [], true);
    }





    /**
     * Modifier un professeur
     */
    #[Route('/{id_professor}/edit', name: 'app_professor_edit', methods: ['POST'])]
    #[ParamConverter('professor', options: ['id' => 'id_professor'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits pour cette action')]
    #[OA\Response(
        response: 200,
        description: "Retourne le professeur modifié",
        content: new Model(type: Professor::class, groups: ['getProfessor', "status"])
    )]
    public function edit(
        Request $request,
        Professor $professor,
        ProfessorRepository $repository,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        TagAwareCacheInterface $cache): JsonResponse
    {
        if (!$professor->isStatus()) {
            return new JsonResponse([
                'code' => Response::HTTP_NOT_FOUND,
                'message' => "The professor doesn't exist"
            ], Response::HTTP_NOT_FOUND, []);
        }

        $bodyResponse = $request->toArray();
        if (array_key_exists('name', $bodyResponse)) {
            $professor->setName($bodyResponse['name']);
        }

        if (array_key_exists('studentClass', $bodyResponse)) {
            $professor->setStudentClass($bodyResponse['studentClass']);
        }

        if (array_key_exists('address', $bodyResponse)) {
            $professor->setAddress($bodyResponse['address']);
        }

        $entityManager->persist($professor);
        $entityManager->flush();

        $newStudent = $repository->findOneBy(['id' => $professor->getId()]);
        $context = SerializationContext::create()->setGroups(['getProfessor', 'status']);
        $studentsSerialize = $serializer->serialize($newStudent, 'json', $context);
        
        $cache->invalidateTags(["allProfessorsCache"]);

        return new JsonResponse($studentsSerialize, Response::HTTP_OK, [], true);
    }





    /**
     * Supprimer un professeur (status = false)
     */
    #[Route('/{id_professor}', name: 'app_professor_delete', methods: ['DELETE'])]
    #[ParamConverter('professor', options: ['id' => 'id_professor'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits pour cette action')]
    #[OA\Response(
        response: 200,
        description: "Supprime le professeur"
    )]
    public function deleteStatus(
        Professor $professor,
        EntityManagerInterface $entityManager): JsonResponse
    {
        $professor->setStatus(false);
        $entityManager->persist($professor);

        $entityManager->flush();

        return new JsonResponse([
            'code' => Response::HTTP_OK,
            'message' => "The entity is delete"
        ], Response::HTTP_OK, []);
    }




    /**
     * Supprimer un professor définitivement
    */
    #[Route('/{id_professor}/delete', name: 'app_professor_delete_definitely', methods: ['DELETE'])]
    #[ParamConverter('student', options: ['id' => 'id_professor'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits pour cette action')]
    #[OA\Response(
        response: 200,
        description: "Supprime le professeur"
    )]
    public function delete(
        Professor $professor,
        EntityManagerInterface $entityManager): JsonResponse
    {

        $entityManager->remove($professor);
        $entityManager->flush();

        return new JsonResponse([
            'code' => Response::HTTP_OK,
            'message' => "The entity is delete"
        ], Response::HTTP_OK, []);
    }
}

