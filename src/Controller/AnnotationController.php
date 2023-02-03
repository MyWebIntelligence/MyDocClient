<?php

namespace App\Controller;

use App\Entity\Annotation;
use App\Entity\Document;
use App\Entity\User;
use App\Form\AnnotationType;
use App\Repository\AnnotationRepository;
use App\Repository\ProjectRepository;
use App\Repository\TagRepository;
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
     * @Route("/annotation/update/{id}", name="update_annotation")
     */
    public function update(
        Annotation $annotation,
        Request $request,
        TagRepository $tagRepository,
        EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $document = $annotation->getDocument();

        if ($document && $project = $document->getProject()) {
            if ($user->isProjectOwner($project) || $user === $annotation->getCreatedBy()) {
                $tag = $tagRepository->findOneBy(['id' => $request->request->get('tag')]);

                $annotation->setComment($request->request->get('comment'));
                $annotation->setTag($tag);

                $entityManager->persist($annotation);
                $entityManager->flush();

                return new JsonResponse(['error' => false, 'message' => '']);
            }
        }


        return new JsonResponse(['errror' => true, 'message' => '']);
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

    /**
     * @Route("/annotations/refresh/{id}", name="refresh_annotations")
     */
    public function refreshTabPanel(Document $document, TagRepository $tagRepository, AnnotationService $annotationService): Response
    {
        $annotations = $document->getAnnotations();

        return $this->render('annotation/index.html.twig', [
            'project' => $document->getProject(),
            'document' => $document,
            'tagTree' => $tagRepository->getProjectTags($document->getProject(), true),
            'annotationsByTag' => $annotationService->getTagIndexed($annotations),
            'authors' => $annotationService->getAuthors($annotations),
        ]);
    }

    /**
     * @Route("/annotations/edit-form/{id}", name="edit_annotation_form")
     */
    public function editForm(
        Annotation $annotation,
        Request $request,
        TagRepository $tagRepository,
        EntityManagerInterface $entityManager): Response
    {
        $tagTree = $tagRepository->getProjectTags($annotation->getDocument()->getProject(), true);
        $form = $this->createForm(AnnotationType::class, $annotation, ['tag_tree' => $tagTree]);
        $form->handleRequest($request);

        return $this->render('annotation/form.html.twig', [
            'formAction' => '/annotation/update/' . $annotation->getId(),
            'formId' => 'editAnnotation',
            'form' => $form->createView(),
            'tagTree' => $tagTree,
        ]);
    }
}