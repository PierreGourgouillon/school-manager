<?php

namespace App\Controller;

use App\Entity\Note;
use App\Entity\Student;
use App\Repository\StudentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;

#[Route('api/notes')]
class NoteController extends AbstractController
{
    /**
     * Ajouter une note à un étudiant
     */
    #[Route('/{id_note}/students/{id_student}', name: 'app_student_add_note', methods: ['POST'])]
    #[ParamConverter('student', options: ['id' => 'id_student'])]
    #[ParamConverter('note', options: ['id' => 'id_note'])]
    #[OA\Response(
        response: 200,
        description: "Retourne l'utilisateur avec la note ajoutée",
        content: new Model(type: Student::class, groups: ['getStudent', "status"])
    )]
    public function addNoteToUser(Student $student, StudentRepository $repository, Note $note, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$student || $student->isStatus() === false) {
            return new JsonResponse([
                'code' => Response::HTTP_NOT_FOUND,
                'message' => "The student doesn't exist"
            ], Response::HTTP_NOT_FOUND, []);
        }

        if (!$note || $note->isStatus() === false) {
            return new JsonResponse([
                'code' => Response::HTTP_NOT_FOUND,
                'message' => "The note doesn't exist"
            ], Response::HTTP_NOT_FOUND, []);
        }

        $student->addNote($note);
        $entityManager->persist($student);
        $entityManager->flush();

        $newStudent = $repository->findOneBy(['id' => $student->getId()]);
        $studentsSerialize = $serializer->serialize($newStudent, 'json', ['groups' => ['getStudent', "status"]]);

        return new JsonResponse($studentsSerialize, Response::HTTP_OK, [], true);
    }

    // #[Route('/{id}', name: 'app_note_delete', methods: ['POST'])]
    // public function delete(Request $request, Note $note, NoteRepository $noteRepository): Response
    // {
    //     if ($this->isCsrfTokenValid('delete'.$note->getId(), $request->request->get('_token'))) {
    //         $noteRepository->remove($note, true);
    //     }

    //     return $this->redirectToRoute('app_note_index', [], Response::HTTP_SEE_OTHER);
    // }
}