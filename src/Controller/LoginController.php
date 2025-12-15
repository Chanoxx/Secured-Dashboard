<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Service\ActivityLogger;

class LoginController extends AbstractController
{
    private ActivityLogger $logger;

    public function __construct(ActivityLogger $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Get any login errors if they exist
        $error = $authenticationUtils->getLastAuthenticationError();
        // Get the last username entered
        $lastUsername = $authenticationUtils->getLastUsername();

        // You might also log successful logins elsewhere (e.g. in the authenticator)
        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // log logout (Symfony will handle token/logout)
        $this->logger->log("User logout");

        // Symfony handles logout automatically via the firewall
        throw new \Exception('Don\'t forget to activate logout in security.yaml');
    }
}
