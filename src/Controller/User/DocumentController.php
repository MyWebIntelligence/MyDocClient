<?php

namespace App\Controller\User;

use App\Controller\Traits\Authorization;
use App\Entity\Document;
use App\Entity\Project;
use App\Entity\User;
use App\Form\DocumentType;
use App\Service\DocumentService;
use App\Service\TextProcessor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_USER")
 */
class DocumentController extends AbstractController
{
    use Authorization;

    /**
     * @Route("/user/document/{id}", name="user_document", requirements={"id": "\d+"})
     */
    public function index(Document $document, Request $request, DocumentService $documentService, TextProcessor $textProcessor): Response
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
            'canEdit' => $this->canEdit($user, $document->getProject()),
            'lexicon' => $textProcessor->countWords($document->getWords()),
            'links' => $documentService->getLinks($document, $request),
        ]);
    }

    /**
     * @Route("/user/project/{id}/new-document", name="user_document_new")
     */
    public function new(Project $project, Request $request, DocumentService $documentService, TextProcessor $textProcessor): Response
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
            $this->redirectToRoute('user_document', ['id' => $document->getId()]);
        }

        return $this->render('user/document/index.html.twig', [
            'document' => $document,
            'form' => $form->createView(),
            'canEdit' => $this->canEdit($user, $project),
            'lexicon' => $textProcessor->countWords($document->getWords()),
        ]);
    }

    /**
     * @Route("/user/document/meta/{id?}", name="user_document_meta", requirements={"id": "\d+"})
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
}
