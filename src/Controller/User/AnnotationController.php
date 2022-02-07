<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Repository\AnnotationRepository;
use App\Repository\ProjectRepository;
use App\Service\AnnotationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

        if ($project && $user->canRead($project)) {
            $filteredAnnotations = $annotationRepository->getFiltered($request);

            $html = $this->renderView('user/annotation/annotations.html.twig', [
                'annotationsByTag' => $annotationService->getTagIndexed($filteredAnnotations),
            ]);

            return new Response($html);
        }

        return new Response('Contenu inaccessible', Response::HTTP_UNAUTHORIZED);
    }

}