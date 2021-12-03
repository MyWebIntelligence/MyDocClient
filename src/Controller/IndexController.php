<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class IndexController extends AbstractController
{

    /**
     * @Route("/", name="home")
     */
    public function index(Security $security): Response
    {
        /** @var User $user */
        if ($security->isGranted('IS_AUTHENTICATED_FULLY') && $user = $security->getUser()) {
            return $this->redirectToRoute('user_projects');
        }

        return $this->render('index/index.html.twig');
    }

}