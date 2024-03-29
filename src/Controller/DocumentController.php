<?php

namespace App\Controller;

use App\Entity\Annotation;
use App\Entity\Document;
use App\Entity\DocumentLink;
use App\Entity\Project;
use App\Entity\User;
use App\Form\DocumentType;
use App\Form\ImportDocumentType;
use App\Repository\DocumentRepository;
use App\Repository\TagRepository;
use App\Service\AnnotationService;
use App\Service\DocumentService;
use App\Service\TextProcessor;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * @IsGranted("IS_AUTHENTICATED")
 */
class DocumentController extends AbstractController
{

    public const RESTRICT_ACCESS_MESSAGE = "Vous n'êtes pas autorisé à agir sur ce document.";
    public const INVALID_YAML_MSG = "Les données ne semblent pas au format YAML, le document n'a pas été enregistré.";

    /**
     * @Route(
     *     "/document/{id}",
     *     name="user_document",
     *     requirements={"id": "\d+"})
     * @throws Exception
     */
    public function index(
        Document           $document,
        Request            $request,
        DocumentService    $documentService,
        DocumentRepository $documentRepository,
        AnnotationService  $annotationService,
        TextProcessor      $textProcessor,
        TagRepository      $tagRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->canReadProject($document->getProject())) {
            $this->addFlash("danger", self::RESTRICT_ACCESS_MESSAGE);
            return $this->redirectToRoute('home');
        }

        $form = $this->createForm(DocumentType::class, $document);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->saveForm($document, $documentService);
        }

        return $this->render('document/index.html.twig', [
            'document' => $document,
            'prev' => $documentRepository->getSiblingDocument($document, $request, -1),
            'next' => $documentRepository->getSiblingDocument($document, $request, 1),
            'annotationsByTag' => $annotationService->getTagIndexed($document->getAnnotations()),
            'annotationAuthors' => $annotationService->getAuthors($document->getAnnotations()),
            'documents' => $documentService->getDocumentsPaginated($document->getProject(), $request, $document),
            'form' => $form->createView(),
            'projectRole' => $user->getProjectRole($document->getProject()),
            'canEdit' => $user->canEditProject($document->getProject()),
            'lexicon' => $textProcessor->countWords($document->getWords()),
            'links' => $documentService->getLinks($document),
            'tagTree' => $tagRepository->getProjectTags($document->getProject(), true),
            'search' => $request->query->get('q'),
        ]);
    }

    /**
     * @Route(
     *     "/project/{id}/new-document",
     *     name="user_document_new",
     *     requirements={"id": "\d+"})
     */
    public function new(
        Project         $project,
        Request         $request,
        DocumentService $documentService,
        TextProcessor   $textProcessor,
        TagRepository   $tagRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->canEditProject($project)) {
            $this->addFlash("danger", ProjectController::RESTRICT_ACCESS_MESSAGE);
            return $this->redirectToRoute('home');
        }

        $document = new Document();
        $document->setProject($project);
        $project->addDocument($document);

        $form = $this->createForm(DocumentType::class, $document);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->saveForm($document, $documentService);
        }

        return $this->render('document/index.html.twig', [
            'document' => $document,
            'documents' => $documentService->getDocumentsPaginated($document->getProject(), $request),
            'form' => $form->createView(),
            'projectRole' => $user->getProjectRole($document->getProject()),
            'canEdit' => $user->canEditProject($project),
            'lexicon' => $textProcessor->countWords($document->getWords()),
            'tagTree' => $tagRepository->getProjectTags($document->getProject(), true),
            'search' => $request->query->get('q'),
        ]);
    }

    private function saveForm(Document $document, DocumentService $documentService): RedirectResponse
    {
        try {
            $documentService->save($document);
            $this->addFlash('success', 'Le document a été sauvé');
        } catch (ParseException $exception) {
            $this->addFlash('danger', self::INVALID_YAML_MSG);
        }

        return $this->redirectToRoute('user_document', ['id' => $document->getId()]);
    }

    /**
     * @Route(
     *     "/document/meta/{id?}",
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
        } elseif (!$user->canEditProject($document->getProject())) {
            return $this->json(false, 403);
        }

        $metas = $document->getMetadataDict();

        array_walk($metas, static function (&$value, $meta) use ($document) {
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
     * @Route("/project/{id}/import",
     *     name="user_import_documents",
     *     requirements={"id": "\d+"})
     */
    public function importDocuments(
        Project         $project,
        Request         $request,
        DocumentService $documentService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->canEditProject($project)) {
            $this->addFlash("danger", ProjectController::RESTRICT_ACCESS_MESSAGE);
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

            if (!empty($errors)) {
                foreach ($errors as $file => $error) {
                    $this->addFlash('danger', sprintf('%s : %s', $file, $error));
                }
            } else {
                return $this->redirectToRoute('user_view_project', ['id' => $project->getId()]);
            }

            return $this->redirectToRoute('user_import_documents', ['id' => $project->getId()]);
        }

        return $this->render('project/import.html.twig', [
            'project' => $project,
            'form' => $importForm->createView(),
            'succeeded' => $succeeded ?? [],
            'errors' => $errors ?? [],
        ]);
    }

    /**
     * @Route("/project/{id}/delete-documents",
     *     name="user_delete_documents",
     *     requirements={"id": "\d+"})
     */
    public function deleteDocuments(
        Project                $project,
        Request                $request,
        DocumentRepository     $documentRepository,
        EntityManagerInterface $entityManager): RedirectResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->canEditProject($project)) {
            $this->addFlash("danger", ProjectController::RESTRICT_ACCESS_MESSAGE);
            return $this->redirectToRoute('home');
        }

        $idArray = $request->request->get('delete_documents', []);

        foreach ($documentRepository->findBy(['id' => $idArray]) as $document) {
            $entityManager->remove($document);
        }

        $entityManager->flush();
        $this->addFlash('info', sprintf("%s document(s) supprimé(s)", count($idArray)));

        return $this->redirectToRoute('user_view_project', ['id' => $project->getId()]);
    }

    /**
     * @Route("/delete-document/{id}", name="user_delete_document")
     */
    public function deleteDocument(
        Document               $document,
        EntityManagerInterface $entityManager): RedirectResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $project = $document->getProject();

        if ($project && $user->canEditProject($project)) {
            $entityManager->remove($document);
            $entityManager->flush();
            $this->addFlash('info', sprintf("Le document %s a été supprimé", $document->getTitle()));

            return $this->redirectToRoute('user_view_project', ['id' => $project->getId()]);
        }

        $this->addFlash('danger', sprintf("Vous n'avez pas la permission de supprimer le document %s", $document->getTitle()));
        return $this->redirectToRoute('user_projects');
    }

    /**
     * @Route("/tag-document/{id}", name="user_tag_document")
     */
    public function tagDocument(
        Document               $document,
        Request                $request,
        TagRepository          $tagRepository,
        EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->canEditProject($document->getProject())) {
            return new JsonResponse([
                'message' => "L'édition de ce projet n'est pas permise",
                'error' => true,
            ]);
        }

        if ($tag = $tagRepository->find($request->request->get('tag'))) {
            $annotation = new Annotation();
            $annotation->setContent($request->request->get('selection'));
            $annotation->setComment($request->request->get('comment'));
            $annotation->setDocument($document);
            $annotation->setTag($tag);
            $entityManager->persist($annotation);
            $entityManager->flush();

            return new JsonResponse([
                'message' => "L'annotation a été sauvegardée",
                'error' => false,
            ]);
        }

        return new JsonResponse([
            'message' => "Erreur, le tag n'a pas été trouvé",
            'error' => true,
        ]);
    }

    /**
     * @Route("/link-documents/{source}/{target}",
     *     name="user_link_documents",
     *     requirements={"source":"\d+","target":"\d+"})
     */
    public function linkDocuments(
        Request                $request,
        EntityManagerInterface $entityManager,
        DocumentRepository     $documentRepository,
                               $source,
                               $target): JsonResponse
    {
        $sourceDocument = $documentRepository->find($source);
        $targetDocument = $documentRepository->find($target);

        if ($sourceDocument && $targetDocument) {
            $link = new DocumentLink();
            $link->setSource($sourceDocument);
            $link->setTarget($targetDocument);
            $link->setContent($request->request->get('selection'));
            $entityManager->persist($link);
            $entityManager->flush();
        }

        return $this->json(true);
    }

    /**
     * @Route("/async-search/{id}", name="user_documents_async_search")
     */
    public function asyncSearch(
        Document           $document,
        Request            $request,
        DocumentRepository $documentRepository,
        PaginatorInterface $paginator): Response
    {
        $queryBuilder = $documentRepository->getSearchDocumentsQueryBuilder(
            $document->getProject(),
            $request->query->get('q')
        );

        $documents = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            25,
            $request->query->get('q') ? [] : ['defaultSortFieldName' => 'd.id', 'defaultSortDirection' => 'asc']
        );

        return new Response(
            $this->renderView('document/_partials/documents.html.twig', [
                'source' => $document,
                'documents' => $documents,
            ])
        );
    }

    /**
     * @Route("/async-links/{id}", name="user_project_async_links")
     */
    public function asyncLinks(Document $document, DocumentService $documentService): Response
    {
        return $this->render('document/_partials/links.html.twig', [
            'links' => $documentService->getLinks($document),
        ]);
    }

}
