<?php

namespace App\Service;

use App\Entity\Document;
use App\Entity\Project;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use ZipArchive;

class DocumentService
{

    private ManagerRegistry $doctrine;
    private ValidatorInterface $validator;
    private Constraints\File $fileConstraint;
    private TextProcessor $textProcessor;

    public function __construct(ManagerRegistry $doctrine, ValidatorInterface $validator, TextProcessor $textProcessor)
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
    }

    public function getFileContraint(): Constraints\File
    {
        return $this->fileConstraint;
    }

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
                    $this->import($project, $file, $file->getClientOriginalName());
                    $succeeded[] = $file->getClientOriginalName();
                }
            }
        }

        $this->doctrine->getManager()->flush();

        return [$succeeded, $errors];
    }

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
                    $violations = $this->validator->validate($file, $this->getFileContraint());

                    if ($violations->count() === 0) {
                        $this->import($project, $file, $file->getBasename());
                        $succeeded[] = $file->getBasename();
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

        foreach ($document->getLinks() as $url) {
            $host = parse_url($url, PHP_URL_HOST);

            if ($host === $request->getHost()) {
                $links['internal'][] = $url;
            } else {
                $links['external'][] = $url;
            }
        }

        return $links;
    }

}