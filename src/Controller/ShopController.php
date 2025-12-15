<?php

namespace App\Controller;

use App\Repository\ItemRepository;
use App\Repository\ProductRepository; // ← add this if needed
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ShopController extends AbstractController
{
    #[IsGranted('ROLE_USER')]
    #[Route('/shop', name: 'shop')]
    public function shop(ItemRepository $itemRepository): Response
    {
        $products = $itemRepository->findAll();

        return $this->render('shop/index.html.twig', [
            'products' => $products
        ]);
    }

    #[Route('/product/{id}', name: 'product_show')]
    public function show(ItemRepository $itemRepository, int $id): Response
    {
        $product = $itemRepository->find($id);

        if (!$product) {
            throw $this->createNotFoundException("Product not found");
        }

        return $this->render('shop/product.html.twig', [
            'product' => $product,
        ]);
    }
}
