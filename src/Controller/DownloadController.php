<?php

namespace App\Controller;

use App\Entity\Annotation;
use App\Entity\Document;
use App\Entity\Project;
use App\Entity\User;
use App\Repository\AnnotationRepository;
use App\Repository\DocumentRepository;
use App\Service\AnnotationService;
use App\Service\DocumentService;
use App\Service\ExportService;
use App\Service\Gexf;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use ZipArchive;

class DownloadController extends AbstractController
{

    private SluggerInterface $slugger;
    private ExportService $exportService;

    public function __construct(SluggerInterface $slugger, ExportService $exportService)
    {
        $this->slugger = $slugger;
        $this->exportService = $exportService;
    }

    /**
     * @Route("/telechargements/{id}", name="download")
     */
    public function index(Project $project): Response
    {
        return $this->render('download/index.html.twig', [
            'project' => $project,
        ]);
    }

    /**
     * @Route("/telecharger-document/{id}", name="download_document")
     */
    public function document(Document $document): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->canReadProject($document->getProject())) {
            $this->addFlash('danger', DocumentController::RESTRICT_ACCESS_MESSAGE);
            return $this->redirectToRoute('user_projects');
        }

        $response = new Response($document->getContent());

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            sprintf('%s-%s.md',
                $this->slugger->slug($document->getTitle()),
                $document->getId()
            )
        );

        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * @Route("/telecharger-annotations/{id}", name="download_annotations")
     */
    public function annotations(Project $project, Request $request, AnnotationRepository $annotationRepository)
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->canReadProject($project)) {
            $this->addFlash('danger', ProjectController::RESTRICT_ACCESS_MESSAGE);
            return $this->redirectToRoute('user_projects');
        }

        $filteredAnnotations = $annotationRepository->getFiltered($request);

        $exportFileName = $this->exportService->timestamp('mydoc-annotations-%s.csv');
        $exportFilePath = $this->exportService->temp($exportFileName);
        $file = fopen($exportFilePath,'wb+');

        fputcsv($file, [
            'citation',
            'commentaire',
            'id document',
            'titre',
            'id utilisateur',
            'utilisateur',
            'tags',
        ], ';');

        /** @var Annotation $annotation */
        foreach ($filteredAnnotations as $annotation) {
            $document = $annotation->getDocument();
            $tag = $annotation->getTag();
            $user = $annotation->getCreatedBy();

            if ($document && $tag && $user) {
                $username = ($user->getFirstName() || $user->getLastName())
                    ? $user->getFirstName() . " " . $user->getLastName()
                    : $user->getEmail();

                $tags = [$tag->getName()];

                foreach ($tag->getAncestors() as $ancestor) {
                    array_unshift($tags, $ancestor->getName());
                }

                fputcsv($file, [
                    $annotation->getContent(),
                    $annotation->getComment(),
                    $document->getId(),
                    $document->getTitle(),
                    $user->getId(),
                    $username,
                    implode(',', $tags),
                ], ';');
            }
        }

        fclose($file);

        return $this->exportService->serveFile($exportFilePath, 'text/csv');
    }

    /**
     * @Route("/telecharger-annotations-md/{id}", name="download_annotations_md")
     */
    public function filteredAnnotationToMarkdown(
        Project $project,
        Request $request,
        AnnotationRepository $annotationRepository,
        AnnotationService $annotationService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->canReadProject($project)) {
            if (!$request->query->get('project')) {
                $request->query->set('project', $project->getId());
            }

            $filteredAnnotations = $annotationRepository->getFiltered($request);

            $text = $this->renderView('annotation/md.html.twig', [
                'annotationsByTag' => $annotationService->getTagIndexed($filteredAnnotations),
            ]);

            $response = new Response($text);
            $disposition = $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                sprintf('annotations-%s.md', date('YmdHis'))
            );

            $response->headers->set('Content-Disposition', $disposition);

            return $response;
        }

        return new Response('Contenu inaccessible', Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @throws JsonException
     */
    private function getDocuments(Project $project, Request $request, DocumentRepository $documentRepository)
    {
        $searchData = ['q' => ''];

        if ($searchParams = $request->cookies->get('searchParams')) {
            $params = json_decode($searchParams, true, 512, JSON_THROW_ON_ERROR);
            $searchData = array_merge($searchData, $params);
        }

        $queryBuilder = $documentRepository->getSearchDocumentsQueryBuilder($project, $searchData['q']);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @Route("/telecharger-gexf/{id}", name="download_gexf")
     * @throws JsonException
     */
    public function gexf(
        Project $project,
        Request $request,
        DocumentRepository $documentRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->canReadProject($project)) {
            $this->addFlash('danger', ProjectController::RESTRICT_ACCESS_MESSAGE);
            return $this->redirectToRoute('user_projects');
        }

        $documents = $this->getDocuments($project, $request, $documentRepository);
        $gexf = new Gexf($project, $documents);
        $exportFileName = $this->exportService->timestamp('mydoc-%s.gexf');
        $exportFilePath = $this->exportService->temp($exportFileName);
        file_put_contents($exportFilePath, $gexf->toXml());

        return $this->exportService->serveFile($exportFilePath, 'application/xml');
    }

    /**
     * @Route("/telecharger-csv/{id}", name="download_csv")
     * @throws JsonException
     */
    public function csv(
        Project $project,
        Request $request,
        DocumentRepository $documentRepository,
        DocumentService $documentService,
        UrlGeneratorInterface $urlGenerator): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->canReadProject($project)) {
            $this->addFlash('danger', ProjectController::RESTRICT_ACCESS_MESSAGE);
            return $this->redirectToRoute('user_projects');
        }

        $exportBaseName = $this->exportService->timestamp('mydoc-csv-%s');
        $archiveFilePath = $this->exportService->temp($exportBaseName . '.zip');
        $archive = new ZipArchive();
        $archive->open($archiveFilePath, ZipArchive::CREATE | ZIPARCHIVE::OVERWRITE);

        $documents = $this->getDocuments($project, $request, $documentRepository);
        $headers = array_merge(
            ['Id'],
            array_values(Document::getMetadataDict()),
            ['Tags', 'Internal links', 'External links']
        );

        $csvFilename = sprintf("%s.csv", $exportBaseName);
        $csvFilepath = $this->exportService->temp($csvFilename);
        $csvFile = fopen($csvFilepath, 'wb+');

        fputcsv($csvFile, $headers, ";");

        /** @var Document $document */
        foreach ($documents as $document) {
            $tags = [];
            $links = $documentService->getLinks($document);

            foreach ($document->getAnnotations() as $annotation) {
                if ($tag = $annotation->getTag()) {
                    $tags[] = $tag->getName();
                }
            }

            $tags = array_unique($tags);

            $data = [
                $document->getId(),
                $document->getTitle(),
                $document->getCreator(),
                $document->getContributor(),
                $document->getCoverage(),
                $document->getDate(),
                $document->getDescription(),
                $document->getSubject(),
                $document->getType(),
                $document->getFormat(),
                $document->getIdentifier(),
                $document->getLanguage(),
                $document->getPublisher(),
                $document->getRelation(),
                $document->getRights(),
                $document->getSource(),
                implode(', ', $tags),
                implode(', ', array_map(function(Document $document) use ($urlGenerator) {
                    return $urlGenerator->generate('user_document', ['id' => $document->getId(), UrlGeneratorInterface::ABSOLUTE_URL]);
                }, $links['internal'])),
                implode(', ', $links['external']),
            ];

            fputcsv($csvFile, $data, ";");

            if ($request->query->get('include_files')) {
                $archive->addFromString(
                    sprintf('%s.md', $this->slugger->slug($document->getTitle() ?: $document->getId())),
                    $document->getContent()
                );
            }
        }

        fclose($csvFile);
        $archive->addFile($csvFilepath, $csvFilename);
        $archive->close();
        unlink($csvFilepath);

        return $this->exportService->serveFile($archiveFilePath, 'application/zip');
    }
}
