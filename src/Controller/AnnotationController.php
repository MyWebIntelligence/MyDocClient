<?php

namespace App\Controller;

use App\Entity\Annotation;
use App\Entity\User;
use App\Repository\AnnotationRepository;
use App\Repository\ProjectRepository;
use App\Service\AnnotationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AnnotationController extends AbstractController
{

    /**
     * @Route("/annotations/filter", name="async_filter_annotations")
     */
    public function asyncFilter(
        Request $request,
        ProjectRepository $projectRepository,
        AnnotationRepository $annotationRepository,
        AnnotationService $annotationService): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $project = $projectRepository->find($request->query->get('project'));

        if ($project && $user->canReadProject($project)) {
            $filteredAnnotations = $annotationRepository->getFiltered($request);

            $html = $this->renderView('annotation/annotations.html.twig', [
                'annotationsByTag' => $annotationService->getTagIndexed($filteredAnnotations),
            ]);

            return new Response($html);
        }

        return new Response('Contenu inaccessible', Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @Route("/annotation/delete/{id}", name="delete_annotation")
     */
    public function delete(Annotation $annotation, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $document = $annotation->getDocument();

        if ($document && $project = $document->getProject()) {
            if ($user->isProjectOwner($project) || $user === $annotation->getCreatedBy()) {
                $entityManager->remove($annotation);
                $entityManager->flush();

                return new JsonResponse(true);
            }
        }


        return new JsonResponse(false);
    }
}