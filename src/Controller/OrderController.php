<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\ActivityLogger;

class OrderController extends AbstractController
{
    private ActivityLogger $logger;

    public function __construct(ActivityLogger $logger)
    {
        $this->logger = $logger;
    }

    #[IsGranted("ROLE_USER")]
    #[Route('/my-orders', name: 'orders_list')]
    public function myOrders(OrderRepository $repo): Response
    {
        $orders = $repo->findBy(['user' => $this->getUser()], ['id' => 'DESC']);

        return $this->render('orders/index.html.twig', [
            'orders' => $orders,
        ]);
    }

   #[Route('/my-orders/cancel/{id}', name: 'order_cancel')]
    public function cancelOrder(
        int $id,
        OrderRepository $orderRepo,
        EntityManagerInterface $em
    ): Response {

        $order = $orderRepo->find($id);

        if (!$order || $order->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException("Order not found.");
        }

        // Remove order items first (to satisfy FK)
        foreach ($order->getOrderItems() as $item) {
            $em->remove($item);
        }

        $em->remove($order);
        $em->flush();

        $this->logger->log("User cancelled order", "Order ID: $id");

        $this->addFlash('success', 'Order cancelled successfully.');

        return $this->redirectToRoute('orders_list');
    }
}
