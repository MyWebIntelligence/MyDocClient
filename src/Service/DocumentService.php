<?php

namespace App\Service;

use App\Controller\DocumentController;
use App\Entity\Document;
use App\Entity\Project;
use App\Repository\DocumentRepository;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use ZipArchive;

class DocumentService
{

    private ManagerRegistry $doctrine;
    private ValidatorInterface $validator;
    private Constraints\File $fileConstraint;
    private TextProcessor $textProcessor;
    private DocumentRepository $documentRepository;
    private PaginatorInterface $paginator;
    private TagUtil $tagUtil;

    public function __construct(
        ManagerRegistry $doctrine,
        ValidatorInterface $validator,
        TextProcessor $textProcessor,
        DocumentRepository $documentRepository,
        PaginatorInterface $paginator,
        TagUtil $tagUtil)
    {
        $this->doctrine = $doctrine;
        $this->validator = $validator;
        $this->fileConstraint = new Constraints\File([
            'maxSize' => ini_get('upload_max_filesize'),
            'maxSizeMessage' => '{{ name }} : dépasse la taille maximum {{ limit }}',
            'mimeTypes' => [
                'text/plain',
                'text/markdown',
                'application/zip',
            ],
            'mimeTypesMessage' => '{{ name }} : type non supporté {{ type }}',
        ]);
        $this->textProcessor = $textProcessor;
        $this->documentRepository = $documentRepository;
        $this->paginator = $paginator;
        $this->tagUtil = $tagUtil;
    }

    public function getFileConstraint(): Constraints\File
    {
        return $this->fileConstraint;
    }

    /**
     * @throws ParseException
     */
    public function importDocuments(Project $project, FormInterface $form): array
    {
        $succeeded = [];
        $errors = [];

        if (!$form->isValid()) {
            foreach ($form->get('files')->getErrors() as $error) {
                /** @var ConstraintViolation $cause */
                $cause = $error->getCause();
                /** @var UploadedFile $invalidValue */
                $invalidValue = $cause->getInvalidValue();
                $errors[$invalidValue->getClientOriginalName()] = $error->getMessage();
            }
        }

        /** @var UploadedFile $file */
        foreach ($form->get('files')->getData() as $file) {
            if (!array_key_exists($file->getClientOriginalName(), $errors)) {
                if ($file->getMimeType() === 'application/zip') {
                    $this->importArchive($project, $file, $succeeded, $errors);
                } else {
                    try {
                        $this->import($project, $file, $file->getClientOriginalName());
                        $succeeded[] = $file->getClientOriginalName();
                    } catch (ParseException $exception) {
                        $errors[$file->getClientOriginalName()] = DocumentController::INVALID_YAML_MSG;
                    }
                }
            }
        }

        $this->doctrine->getManager()->flush();

        return [$succeeded, $errors];
    }

    /**
     * @throws ParseException
     */
    private function importArchive(Project $project, UploadedFile $archive, array &$succeeded, array &$errors): void
    {
        $zip = new ZipArchive();

        if ($zip->open($archive->getPathname()) === true) {
            $extractPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('', true);
            $zip->extractTo($extractPath);
            $zip->close();

            foreach (glob($extractPath . DIRECTORY_SEPARATOR . "*") as $path) {
                if (is_file($path)) {
                    $file = new File($path);
                    $violations = $this->validator->validate($file, $this->getFileConstraint());

                    if ($violations->count() === 0) {
                        try {
                            $this->import($project, $file, $file->getBasename());
                            $succeeded[] = $file->getBasename();
                        } catch (\Exception $exception) {
                            $errors[$file->getBasename()] = DocumentController::INVALID_YAML_MSG;
                        }
                    } else {
                        /** @var ConstraintViolation $violation */
                        foreach ($violations as $violation) {
                            $errors[$file->getBasename()] = $violation->getMessage();
                        }
                    }
                }
            }
        } else {
            $errors[$archive->getClientOriginalName()] = "L'archive n'a pas pu être ouverte";
        }
    }

    /**
     * @throws ParseException
     */
    private function import(Project $project, File $file, $name): void
    {
        $document = new Document();
        $document->setCreatedAt(new DateTimeImmutable());
        $document->setTitle($name);
        $document->setContent($this->textProcessor->toUtf8($file->getContent()));
        $document->updateMetadata();
        $document->setProject($project);
        $project->addDocument($document);

        $entityManager = $this->doctrine->getManager();
        $entityManager->persist($document);
    }

    /**
     * @throws ParseException
     */
    public function save(Document $document): void
    {
        $manager = $this->doctrine->getManager();
        $document->updateMetadata();
        $manager->persist($document);
        $manager->flush();
    }

    public function getLinks(Document $document, Request $request): array
    {
        $links = ['internal' => [], 'external' => []];

        foreach ($document->getExternalLinks() as $url) {
            $links['external'][] = $url;
        }

        foreach ($document->getSourceOf() as $link) {
            $links['internal'][] = $link->getTarget();
        }

        foreach ($document->getTargetOf() as $link) {
            $links['internal'][] = $link->getSource();
        }

        $links['internal'] = array_unique($links['internal']);

        return $links;
    }

    public function getDocumentsPaginated(Project $project, Request $request, Document $document = null): PaginationInterface
    {
        $queryBuilder = $this->documentRepository->createQueryBuilder('d')
            ->where('d.project = :project')
            ->setParameter('project', $project);

        if ($document !== null) {
            $queryBuilder->andWhere('d != :document')
                ->setParameter('document', $document);
        }

        return $this->paginator->paginate(
            $queryBuilder,
            $request->query->get('page', 1),
            25
        );
    }
}