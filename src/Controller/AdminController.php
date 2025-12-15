<?php

namespace App\Controller;

use App\Entity\Item;
use Doctrine\ORM\EntityManagerInterface; 
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Form\ItemType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'admin_dashboard')]
public function dashboard(EntityManagerInterface $em): Response
{
    $products = $em->getRepository(Item::class)->findAll();


    return $this->render('admin/dashboard.html.twig', [
        'products' => $products,
    ]);
}

    
    #[Route('/admin/products', name: 'admin_products')]
    public function manageProducts(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $item = new Item();
        $form = $this->createForm(ItemType::class, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('uploads_directory'), // defined in services.yaml
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Error uploading image.');
                }

                $item->setImage($newFilename);
            }

            $em->persist($item);
            $em->flush();

            $this->addFlash('success', 'Product added successfully!');
            return $this->redirectToRoute('admin_products');
        }

        $products = $em->getRepository(Item::class)->findAll();

        return $this->render('admin/manage_products.html.twig', [
            'form' => $form->createView(),
            'products' => $products,
        ]);
    }

    #[Route('/admin/product/{id}/edit', name: 'admin_product_edit')]
    public function editProduct(Item $item, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ItemType::class, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('uploads_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Error uploading image.');
                }

                $item->setImage($newFilename);
            }

            $em->flush();
            $this->addFlash('success', 'Product updated successfully!');
            return $this->redirectToRoute('admin_products');
        }

        return $this->render('item/edit.html.twig', [
            'form' => $form->createView(),
            'product' => $item,
        ]);
    }

    
    #[Route('/admin/product/{id}/delete', name: 'admin_product_delete', methods: ['POST'])]
    public function deleteProduct(Request $request, Item $item, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$item->getId(), $request->request->get('_token'))) {
            $em->remove($item);
            $em->flush();
            $this->addFlash('success', 'Product deleted successfully!');
        }

        return $this->redirectToRoute('admin_products');
    }


}

