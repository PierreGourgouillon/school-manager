<?php

namespace App\Controller;

use App\Entity\School;
use App\Form\SchoolType;
use App\Repository\SchoolRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
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
    public function new(Request $request, SchoolRepository $schoolRepository): Response
    {
        $school = new School();
        $form = $this->createForm(SchoolType::class, $school);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $schoolRepository->save($school, true);

            return $this->redirectToRoute('app_school_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('school/new.html.twig', [
            'school' => $school,
            'form' => $form,
        ]);
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

    #[Route('/{id}/edit', name: 'app_school_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, School $school, SchoolRepository $schoolRepository): Response
    {
        $form = $this->createForm(SchoolType::class, $school);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $schoolRepository->save($school, true);

            return $this->redirectToRoute('app_school_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('school/edit.html.twig', [
            'school' => $school,
            'form' => $form,
        ]);
    }

    #[Route('/{id_school}', name: 'app_school_delete', methods: ['DELETE'])]
    #[ParamConverter('school', options: ['id' => 'id_school'])]
    public function delete(School $school, EntityManagerInterface $entityManager): JsonResponse
    {
        $school->setStatus(false);
        $entityManager->persist($school);

        $entityManager->flush();

        return new JsonResponse([
            'code' => Response::HTTP_OK,
            'message' => "The school is delete"
        ], Response::HTTP_OK, []);
    }
}
