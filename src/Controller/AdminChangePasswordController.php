<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\ActivityLogger;

#[IsGranted('ROLE_ADMIN')]
class AdminChangePasswordController extends AbstractController
{
    private ActivityLogger $logger;

    public function __construct(ActivityLogger $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/admin/change-password', name: 'admin_change_password')]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): Response {

        $admin = $this->getUser();

        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('newPassword');

            if (!$newPassword) {
                $this->addFlash('error', 'Password cannot be empty.');
                return $this->redirectToRoute('admin_change_password');
            }

            $hashed = $passwordHasher->hashPassword($admin, $newPassword);
            $admin->setPassword($hashed);

            $em->flush();

            $this->logger->log("Admin changed password", "Admin ID: " . $admin->getId());

            $this->addFlash('success', 'Password updated successfully.');
            return $this->redirectToRoute('admin_profile');
        }

        return $this->render('admin/change_password.html.twig');
    }
}
