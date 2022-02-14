<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DownloadController extends AbstractController
{
    /**
     * @Route("/download", name="download")
     */
    public function index(): Response
    {
        return $this->render('download/index.html.twig', [
            'controller_name' => 'DownloadController',
        ]);
    }
}
