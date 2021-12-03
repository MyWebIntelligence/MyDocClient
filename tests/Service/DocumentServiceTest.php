<?php

namespace Service;

use App\Service\DocumentService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DocumentServiceTest extends KernelTestCase
{

    private DocumentService $documentService;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->documentService = self::getContainer()->get(DocumentService::class);
    }

}