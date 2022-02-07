<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/profile")
 * @IsGranted("IS_AUTHENTICATED")
 */
class ProfileController extends AbstractController
{
    /**
     * @Route("", name="user_profile")
     */
    public function index(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        if ($user = $this->getUser()) {
            $form = $this->createForm(UserType::class, $user);
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $user->setIsVerified(false);
                    $plainPassword = $form->get('changePassword')->get('plainPassword');

                    if (!empty($plainPassword->getData()) && $form->get('changePassword')->isValid()) {
                        $user->setPassword(
                            $passwordHasher->hashPassword(
                                $user,
                                $plainPassword->getData()
                            )
                        );
                        $this->addFlash('info', "Le mot de passe a été modifié.");
                    }

                    $entityManager->persist($user);
                    $entityManager->flush();
                    $this->addFlash('info', "Les informations ont été sauvegardées.");
                } else {
                    $entityManager->refresh($user);
                }
            }

            return $this->render('user/profile/index.html.twig', [
                'form' => $form->createView(),
            ]);
        }

        $this->addFlash('danger', "La page est inaccessible.");

        return $this->redirectToRoute('home');
    }
}
