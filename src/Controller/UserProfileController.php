<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\ActivityLogger;

#[IsGranted("ROLE_USER")]
class UserProfileController extends AbstractController
{
    private ActivityLogger $logger;

    public function __construct(ActivityLogger $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/profile', name: 'user_profile')]
    public function profile(): Response
    {
        return $this->render('user/profile.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/profile/change-password', name: 'user_change_password', methods: ['POST'])]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em
    ): Response {

        $user = $this->getUser();

        $current = $request->request->get('current_password');
        $new     = $request->request->get('new_password');
        $confirm = $request->request->get('confirm_password');

        // Validate current password
        if (!$hasher->isPasswordValid($user, $current)) {
            $this->addFlash('error', 'Current password is incorrect.');
            return $this->redirectToRoute('user_profile');
        }

        // Validate confirmation
        if ($new !== $confirm) {
            $this->addFlash('error', 'New passwords do not match.');
            return $this->redirectToRoute('user_profile');
        }

        // Save password
        $hashed = $hasher->hashPassword($user, $new);
        $user->setPassword($hashed);
        $em->flush();

        $this->logger->log("User changed password", "User ID: " . $user->getId());

        $this->addFlash('success', 'Password changed successfully.');
        return $this->redirectToRoute('user_profile');
    }
}
