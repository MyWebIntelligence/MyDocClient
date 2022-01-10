<?php

namespace App\Controller\User;

use App\Controller\Traits\Authorization;
use App\Entity\Permission;
use App\Entity\Project;
use App\Entity\User;
use App\Repository\PermissionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("IS_AUTHENTICATED")
 */
class ShareController extends AbstractController
{

    use Authorization;

    /**
     * @Route("/user/project/{id}/share",
     *     name="user_project_invite",
     *     methods={"POST"},
     *     requirements={"id": "\d+"})
     */
    public function share(
        Project                     $project,
        Request                     $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface      $entityManager,
        UserRepository              $userRepository,
        PermissionRepository        $permissionRepository,
        MailerInterface             $mailer): Response
    {
        if ($project->getOwner() === $this->getUser()) {
            if ($email = $request->request->get('email')) {
                // If user doesn't exist, create it
                if (!$user = $userRepository->findOneBy(['email' => $email])) {
                    $user = new User();
                    $user->setEmail($email);

                    $tempPassword = $hasher->hashPassword($user, uniqid('temp', true));
                    $user->setPassword($tempPassword);

                    $user->setIsVerified(false);
                    $user->setRoles([]);
                    $entityManager->persist($user);
                    $entityManager->flush();
                }

                // Check if permission not already set for this user
                if (!$permissionRepository->findOneBy(['user' => $user, 'project' => $project])) {
                    $permission = new Permission();
                    $permission->setProject($project);
                    $permission->setUser($user);
                    $permission->setRole($request->request->get('permission'));

                    $entityManager->persist($permission);
                    $entityManager->flush();

                    $email = new TemplatedEmail();
                    $email->from(new Address('contact@daten.fr', 'My Doc Intelligence'))
                        ->to($user->getEmail())
                        ->subject('Invitation à collaborer - My Doc Intelligence')
                        ->htmlTemplate('user/share/email.html.twig');
                    $context = $email->getContext();
                    $context['host'] = $this->getUser();
                    $context['project'] = $project;
                    $email->context($context);
                    $mailer->send($email);

                    return new JsonResponse(['res' => true, 'message' => '']);
                }

                return new JsonResponse([
                    'res' => false,
                    'message' => sprintf("L'utilisateur %s a déjà été invité", $email)
                ]);
            }
        }

        return new JsonResponse(['res' => false, 'message' => 'Accès non autorisé']);
    }


    /**
     * @Route("/user/project/{id}/share/update",
     *     name="user_project_invite_update",
     *     methods={"POST"},
     *     requirements={"id": "\d+"})
     */
    public function updateRole(
        Project                $project,
        Request                $request,
        EntityManagerInterface $entityManager,
        UserRepository         $userRepository,
        PermissionRepository   $permissionRepository): Response
    {
        $isOwner = $project->getOwner() === $this->getUser();
        $user = $userRepository->findByEmail($request->request->get('email'));

        if ($isOwner && $user) {
            $permission = $permissionRepository->findOneBy([
                'user' => $user,
                'project' => $project,
            ]);

            if ($permission) {
                $permission->setRole($request->request->get('role'));
                $entityManager->persist($permission);
                $entityManager->flush();

                return new JsonResponse(['res' => true, 'message' => 'Permission sauvegardée']);
            }
        }

        return new JsonResponse(['res' => false, 'message' => 'Accès non autorisé']);
    }

    /**
     * @Route("/user/project/{id}/share/delete",
     *     name="user_project_invite_delete",
     *     methods={"POST"},
     *     requirements={"id": "\d+"})
     */
    public function deletePermission(
        Project                $project,
        Request                $request,
        EntityManagerInterface $entityManager,
        UserRepository         $userRepository,
        PermissionRepository   $permissionRepository): Response
    {
        $isOwner = $project->getOwner() === $this->getUser();
        $user = $userRepository->findByEmail($request->request->get('email'));

        if ($isOwner && $user) {
            $permission = $permissionRepository->findOneBy([
                'user' => $user,
                'project' => $project,
            ]);

            if ($permission) {
                $entityManager->remove($permission);
                $entityManager->flush();
            }

            return new JsonResponse(['res' => true, 'message' => 'Permission supprimée']);
        }

        return new JsonResponse(['res' => false, 'message' => 'Accès non autorisé']);
    }
}
