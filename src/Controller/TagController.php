<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Tag;
use App\Entity\User;
use App\Repository\TagRepository;
use App\Service\TagUtil;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TagController extends AbstractController
{

    /**
     * @Route("/project/{id}/add-tag",
     *     name="user_project_add_tag",
     *     requirements={"id": "\d+"},
     *     methods={"POST"})
     */
    public function addTag(
        Project $project,
        Request $request,
        TagRepository $tagRepository,
        EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->canEditProject($project)) {
            return $this->json([
                'res' => false,
                'message' => "Vous n'êtes pas autorisé à agir sur ce projet",
            ], 401);
        }

        $tag = new Tag();
        $tag->setName($request->request->get('name'));
        $tag->setDescription($request->request->get('description'));
        $tag->setRoot($project);

        if ($parent = $tagRepository->find($request->request->get('parentId'))) {
            $tag->setParent($parent);
        }

        $entityManager->persist($tag);
        $entityManager->flush();

        return $this->json(true);
    }

    /**
     * @Route("/project/{id}/delete-tag",
     *     name="user_project_delete_tag",
     *     requirements={"id": "\d+"},
     *     methods={"POST"})
     */
    public function deleteTag(
        Request $request,
        Project $project,
        TagRepository $tagRepository,
        EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->canEditProject($project)) {
            return $this->json([
                'res' => false,
                'message' => "Vous n'êtes pas autorisé à agir sur ce projet",
            ], 401);
        }

        if ($tag = $tagRepository->find($request->request->get('id'))) {
            $entityManager->remove($tag);
            $entityManager->flush();

            return $this->json(true);
        }

        return $this->json(false);
    }

    /**
     * @Route("/project/{id}/save-tags",
     *     name="user_project_save_tags",
     *     requirements={"id": "\d+"},
     *     methods={"POST"})
     */
    public function saveTags(
        Project $project,
        Request $request,
        TagRepository $tagRepository,
        TagUtil $tagUtil,
        EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->canEditProject($project)) {
            return $this->json([
                'res' => false,
                'message' => "Vous n'êtes pas autorisé à agir sur ce projet",
            ], 401);
        }

        try {
            $updatedStructure = json_decode(
                $request->request->get('updatedTree', '[]'),
                false,
                512,
                JSON_THROW_ON_ERROR);

            $indexedCollection = $tagUtil->indexCollection($tagRepository->getProjectTags($project));
            $tagUtil->updateTree($updatedStructure, $indexedCollection);
            $entityManager->flush();

            return $this->json([
                'res' => true,
                'message' => 'Mis à jour',
            ]);
        } catch (Exception $e) {
            return $this->json([
                'res' => false,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * @Route("/project/{id}/rename-tag",
     *     name="user_project_rename_tag",
     *     requirements={"id": "\d+"},
     *     methods={"POST"})
     */
    public function renameTag(
        Project $project,
        Request $request,
        TagRepository $tagRepository,
        EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->canEditProject($project)) {
            return $this->json([
                'res' => false,
                'message' => "Vous n'êtes pas autorisé à agir sur ce projet",
            ], 401);
        }

        if (($tag = $tagRepository->find($request->request->get('id')))
            && ($tag->getRoot() === $project)) {
            $tag->setName($request->request->get('name'));
            $tag->setDescription($request->request->get('description'));
            $entityManager->persist($tag);
            $entityManager->flush();

            return $this->json([
                'res' => true,
                'message' => 'Tag sauvegardé',
            ]);
        }

        return $this->json([
            'res' => false,
            'message' => "La tag n'existe pas"
        ]);
    }
}