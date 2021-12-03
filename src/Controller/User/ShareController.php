<?php

namespace App\Controller\User;

use App\Entity\Permission;
use App\Entity\Project;
use App\Entity\User;
use App\Repository\PermissionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class ShareController extends AbstractController
{
    /**
     * @Route("/user/project/{id}/share", name="user_project_invite", methods={"POST"}, requirements={"id": "\d+"})
     */
    public function share(
        Project $project,
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        PermissionRepository $permissionRepository): Response
    {
        if ($request->isMethod('post') && ($email = $request->request->get('email'))) {
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

                /**
                 * @TODO Send invitation e-mail
                 */

                return new JsonResponse(['res' => true, 'message' => '']);
            }

            return new JsonResponse([
                'res' => false,
                'message' => sprintf("L'utilisateur %s a déjà été invité", $email)
            ]);
        }

        return new JsonResponse(['res' => false, 'message' => '']);
    }
}
