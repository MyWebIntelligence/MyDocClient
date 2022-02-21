<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\Project;
use App\Repository\DocumentRepository;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use ZipArchive;

class DownloadController extends AbstractController
{

    private SluggerInterface $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
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
     * @throws JsonException
     */
    private function getDocuments(Project $project, Request $request, DocumentRepository $documentRepository)
    {
        $searchData = ['q' => ''];

        if ($searchParams = $request->cookies->get('searchParams')) {
            $searchData = json_decode($searchParams, true, 512, JSON_THROW_ON_ERROR);
        }

        $queryBuilder = $documentRepository->getSearchDocumentsQueryBuilder($project, $searchData['q']);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @Route("/telecharger-gexf/{id}", name="download_gexf")
     * @throws JsonException
     */
    public function gexf(Project $project, Request $request, DocumentRepository $documentRepository): Response
    {
        $documents = $this->getDocuments($project, $request, $documentRepository);

        return new Response();
    }

    /**
     * @Route("/telecharger-csv/{id}", name="download_csv")
     * @throws JsonException
     */
    public function csv(Project $project, Request $request, DocumentRepository $documentRepository): Response
    {
        $exportBaseName = sprintf("mydoc-export-csv-%s", date("YmdHis"));
        $archiveFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $exportBaseName . '.zip';
        $archive = new ZipArchive();
        $archive->open($archiveFilePath, ZipArchive::CREATE | ZIPARCHIVE::OVERWRITE);

        $documents = $this->getDocuments($project, $request, $documentRepository);
        $headers = array_values(Document::getMetadataDict());
        $headers[] = 'Tags';

        $csvFilename = sprintf("%s.csv", $exportBaseName);
        $csvFilepath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $csvFilename;
        $csvFile = fopen($csvFilepath, 'wb+');

        fputcsv($csvFile, $headers, ";");

        /** @var Document $document */
        foreach ($documents as $document) {
            $tags = [];

            foreach ($document->getAnnotations() as $annotation) {
                if ($tag = $annotation->getTag()) {
                    $tags[] = $tag->getName();
                }
            }

            $tags = array_unique($tags);

            $data = [
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
            ];

            fputcsv($csvFile, $data, ";");

            if ($request->query->get('include_files')) {
                $archive->addFromString(
                    sprintf(
                        '%s.md',
                        $this->slugger->slug($document->getTitle() ?: $document->getId())
                    ),
                    $document->getContent()
                );
            }
        }

        fclose($csvFile);
        $archive->addFile($csvFilepath, $csvFilename);
        $archive->close();

        unlink($csvFilepath);

        $response = new BinaryFileResponse($archiveFilePath);
        $response->headers->set('Content-Type', 'text/csv');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($archiveFilePath));

        return $response;
    }
}
