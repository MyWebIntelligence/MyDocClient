<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Form\UserType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    /**
     * @Route("/user/profile", name="user_profile")
     * @IsGranted("IS_AUTHENTICATED")
     */
    public function index(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        /**
         * @var User $user
         */
        if ($user = $this->getUser()) {
            $form = $this->createForm(UserType::class, $user);
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                $plainPassword = $form->get('changePassword')->get('plainPassword');

                if (!empty($plainPassword->getData()) && $form->get('changePassword')->isValid()) {
                    $user->setPassword(
                        $passwordHasher->hashPassword(
                            $user,
                            $plainPassword->getData()
                        )
                    );
                }
            }

            return $this->render('user/profile/index.html.twig', [
                'form' => $form->createView(),
            ]);
        }
    }
}
