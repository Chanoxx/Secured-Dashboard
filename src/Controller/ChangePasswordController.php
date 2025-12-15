<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\ActivityLogger;

#[IsGranted("ROLE_USER")]
class ChangePasswordController extends AbstractController
{
    private ActivityLogger $logger;

    public function __construct(ActivityLogger $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/change-password', name: 'change_password')]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): Response {

        if ($request->isMethod('POST')) {
            $user = $this->getUser();

            $current = $request->request->get('current_password');
            $new     = $request->request->get('new_password');
            $confirm = $request->request->get('confirm_password');

            // Check current password
            if (!$passwordHasher->isPasswordValid($user, $current)) {
                $this->addFlash('error', 'Current password is incorrect.');
                return $this->redirectToRoute('change_password');
            }

            // Check match
            if ($new !== $confirm) {
                $this->addFlash('error', 'New passwords do not match.');
                return $this->redirectToRoute('change_password');
            }

            // Hash and save new password
            $hashed = $passwordHasher->hashPassword($user, $new);
            $user->setPassword($hashed);

            $em->flush();

            $this->logger->log("User changed password", "User ID: " . $user->getId());

            $this->addFlash('success', 'Password updated successfully!');
            return $this->redirectToRoute('shop');
        }

        return $this->render('user/change_password.html.twig');
    }
}
