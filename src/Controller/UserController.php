<?php

namespace App\Controller;

use App\Entity\User;
use JMS\Serializer\Serializer;
use App\Repository\UserRepository;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('api/user')]
class UserController extends AbstractController
{

    /**
     * Récupérer le profil de l'utilisateur connecté
     */
    #[Route('/profil', name: 'app_user_profil', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: "Retourne le type de l'utilisateur connecté",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: User::class, groups: []))
        )
    )]
    public function index(SerializerInterface $serializer, #[CurrentUser] User $user): JsonResponse
    {
        if ($user?->getDirector() != null) {
            $director = $user->getDirector();
            $context = SerializationContext::create()->setGroups('getDirector');
            $directorSerialize = $serializer->serialize($director, 'json', $context);
            return new JsonResponse($directorSerialize, Response::HTTP_OK, [], true);
        } else if ($user?->getStudent() != null) {
            $student = $user->getStudent();
            $context = SerializationContext::create()->setGroups('getStudent');
            $studentSerialize = $serializer->serialize($student, 'json', $context);
            return new JsonResponse($studentSerialize, Response::HTTP_OK, [], true);
        } else if ($user?->getProfessor() != null) {
            echo ("coucou3");
            $professor = $user->getProfessor();
            $context = SerializationContext::create()->setGroups('getProfessor');
            $professorSerialize = $serializer->serialize($professor, 'json', $context);
            return new JsonResponse($professorSerialize, Response::HTTP_OK, [], true);
        }

        return new JsonResponse("", Response::HTTP_OK, [], true);
    }
}
