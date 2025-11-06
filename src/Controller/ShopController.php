<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ShopController extends AbstractController
{
    #[IsGranted('ROLE_USER')]
    #[Route('/shop', name: 'shop')]
    public function shop(): Response
    {
        return $this->render('shop/index.html.twig');
    }
}
