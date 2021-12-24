<?php

namespace App\Controller\User;

use App\Controller\Traits\Authorization;
use App\Entity\Document;
use App\Entity\Project;
use App\Entity\User;
use App\Form\DocumentType;
use App\Form\ImportDocumentType;
use App\Repository\DocumentRepository;
use App\Repository\TagRepository;
use App\Service\DocumentService;
use App\Service\TextProcessor;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("IS_AUTHENTICATED")
 */
class DocumentController extends AbstractController
{
    use Authorization;

    /**
     * @Route(
     *     "/user/document/{id}",
     *     name="user_document",
     *     requirements={"id": "\d+"})
     */
    public function index(
        Document $document,
        Request $request,
        DocumentService $documentService,
        TextProcessor $textProcessor,
        TagRepository $tagRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->canRead($user, $document->getProject())) {
            $this->addFlash("danger", "Vous n'êtes pas autorisé à agir sur ce document.");
            return $this->redirectToRoute('home');
        }

        $form = $this->createForm(DocumentType::class, $document);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $documentService->save($document);
            $this->addFlash('success', 'Le document a été sauvé');
            return $this->redirectToRoute('user_document', ['id' => $document->getId()]);
        }

        return $this->render('user/document/index.html.twig', [
            'document' => $document,
            'form' => $form->createView(),
            'projectRole' => $this->getRole($user, $document->getProject()),
            'canEdit' => $this->canEdit($user, $document->getProject()),
            'lexicon' => $textProcessor->countWords($document->getWords()),
            'links' => $documentService->getLinks($document, $request),
            'tagTree' => $tagRepository->getProjectTags($document->getProject(), true),
        ]);
    }

    /**
     * @Route(
     *     "/user/project/{id}/new-document",
     *     name="user_document_new",
     *     requirements={"id": "\d+"})
     */
    public function new(
        Project $project,
        Request $request,
        DocumentService $documentService,
        TextProcessor $textProcessor): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->canEdit($user, $project)) {
            $this->addFlash("danger", "Vous n'êtes pas autorisé à agir sur ce projet.");
            return $this->redirectToRoute('home');
        }

        $document = new Document();
        $document->setProject($project);
        $project->addDocument($document);

        $form = $this->createForm(DocumentType::class, $document);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $documentService->save($document);
            return $this->redirectToRoute('user_document', ['id' => $document->getId()]);
        }

        return $this->render('user/document/index.html.twig', [
            'document' => $document,
            'form' => $form->createView(),
            'projectRole' => $this->getRole($user, $document->getProject()),
            'canEdit' => $this->canEdit($user, $project),
            'lexicon' => $textProcessor->countWords($document->getWords()),
        ]);
    }

    /**
     * @Route(
     *     "/user/document/meta/{id?}",
     *     name="user_document_meta",
     *     requirements={"id": "\d+"})
     */
    public function meta(?Document $document): JsonResponse
    {
        /**
         * @var User $user
         */
        $user = $this->getUser();

        if ($document === null) {
            // New document, metas come from nothing
            $document = new Document();
            $document->setOwner($user);
        } elseif (!$this->canEdit($user, $document->getProject())) {
            return $this->json(false, 403);
        }

        $metas = $document->getMetadataDict();

        array_walk($metas, static function(&$value, $meta) use ($document) {
            $value = $document->{'get' . ucfirst($meta)}();
        });

        $updatedContent = sprintf(
            '%s%s%s',
            $document->formatMetadata(),
            PHP_EOL,
            $document->getContent(true)
        );

        return $this->json([
            'metas' => $metas,
            'formatted' => $updatedContent,
        ]);
    }

    /**
     * @Route("/user/project/{id}/import",
     *     name="user_project_import_documents",
     *     requirements={"id": "\d+"})
     */
    public function importDocuments(
        Project $project,
        Request $request,
        DocumentService $documentService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->canEdit($user, $project)) {
            $this->addFlash("danger", "Vous n'êtes pas autorisé à agir sur ce projet.");
            return $this->redirectToRoute('home');
        }

        $importForm = $this->createForm(ImportDocumentType::class);
        $importForm->handleRequest($request);

        if ($importForm->isSubmitted()) {
            [$succeeded, $errors] = $documentService->importDocuments($project, $importForm);

            $message = empty($errors)
                ? sprintf('%s document(s) importé(s)', count($succeeded))
                : sprintf('%s document(s) importés, %s erreur(s)', count($succeeded), count($errors));
            $this->addFlash('info', $message);

            if (empty($errors)) {
                return $this->redirectToRoute('user_view_project', ['id' => $project->getId()]);
            }

        }

        return $this->render('user/project/import.html.twig', [
            'project' => $project,
            'form' => $importForm->createView(),
            'succeeded' => $succeeded ?? [],
            'errors' => $errors ?? [],
        ]);
    }

    /**
     * @Route("/user/project/{id}/delete-documents",
     *     name="user_project_delete_documents",
     *     requirements={"id": "\d+"})
     */
    public function deleteDocuments(
        Project $project,
        Request $request,
        DocumentRepository $documentRepository): RedirectResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->canEdit($user, $project)) {
            $this->addFlash("danger", "Vous n'êtes pas autorisé à agir sur ce projet.");
            return $this->redirectToRoute('home');
        }

        $ids = $request->request->get('delete_documents', []);
        $qb = $documentRepository->createQueryBuilder('d')
            ->delete()
            ->where('d.id IN (:ids)')
            ->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY);
        $qb->getQuery()->execute();
        $this->addFlash('info', sprintf("%s document(s) supprimé(s)", count($ids)));

        return $this->redirectToRoute('user_view_project', ['id' => $project->getId()]);
    }

    /**
     * @Route("/user/project/{projectId}/delete-document/{documentId}",
     *     name="user_project_delete_document",
     *     requirements={
     *         "projectId": "\d+",
     *         "documentId": "\d+"
     *     })
     * @ParamConverter("project", options={"mapping": {"projectId": "id"}})
     * @ParamConverter("document", options={"mapping": {"documentId": "id"}})
     */
    public function deleteDocument(
        Project $project,
        Document $document,
        EntityManagerInterface $entityManager): RedirectResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->canEdit($user, $project)) {
            $entityManager->remove($document);
            $entityManager->flush();
            $this->addFlash('info', sprintf("Le document %s a été supprimé", $document->getTitle()));
            return $this->redirectToRoute('user_view_project', ['id' => $project->getId()]);
        }
    }
}
