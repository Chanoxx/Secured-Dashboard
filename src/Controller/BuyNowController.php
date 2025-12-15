<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\ItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ActivityLogger;

class BuyNowController extends AbstractController
{
    private ActivityLogger $logger;

    public function __construct(ActivityLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * STEP 1 — Set Buy Now Product in Session
     */
    #[Route('/buy-now', name: 'buy_now', methods: ['POST'])]
    public function buyNow(Request $request, ItemRepository $itemRepo, SessionInterface $session)
    {
        $id = (int) $request->request->get('id');
        $qty = max(1, (int) $request->request->get('qty'));

        $this->logger->log("User clicked Buy Now", "Product ID: $id");

        $product = $itemRepo->find($id);

        if (!$product) {
            return $this->redirectToRoute('shop');
        }

        // Save Buy Now info in session
        $session->set('buy_now', [
            'id'    => $id,
            'name'  => $product->getItemName(),
            'price' => $product->getPrice(),
            'image' => $product->getImage(),
            'qty'   => $qty
        ]);

        return $this->redirectToRoute('buy_now_checkout');
    }

    /**
     * STEP 2 — Checkout Page
     */
    #[Route('/buy-now/checkout', name: 'buy_now_checkout', methods: ['GET'])]
    public function checkout(SessionInterface $session)
    {
        $item = $session->get('buy_now');

        if (!$item) {
            return $this->redirectToRoute('shop');
        }

        return $this->render('buy_now/checkout.html.twig', ['item' => $item]);
    }

    /**
     * STEP 3 — Confirm Page
     */
    #[Route('/buy-now/confirm', name: 'buy_now_confirm', methods: ['POST'])]
    public function confirm(Request $request, SessionInterface $session): Response
    {
        $this->logger->log("User filled Buy Now details");

        // Retrieve the buy-now item stored earlier
        $item = $session->get('buy_now');

        if (!$item) {
            return $this->redirectToRoute('shop');
        }

        // Collect customer info
        $data = [
            'fullName'       => $request->request->get('fullName'),
            'address'        => $request->request->get('address'),
            'phone'          => $request->request->get('phone'),
            'paymentMethod'  => $request->request->get('paymentMethod'),
        ];

        // Save both item + details in session
        $session->set('buy_now_details', $data);

        return $this->render('buy_now/confirm.html.twig', [
            'item' => $item,
            'data' => $data,
        ]);
    }

    /**
     * STEP 4 — Place Order
     */
    #[Route('/buy-now/place', name: 'buy_now_place', methods: ['POST'])]
    public function placeOrder(SessionInterface $session, EntityManagerInterface $em): Response
    {
        $item = $session->get('buy_now');
        $details = $session->get('buy_now_details');

        if (!$item || !$details) {
            return $this->redirectToRoute('shop');
        }

        $user = $this->getUser();
        $subtotal = $item['price'] * $item['qty'];

        // Create order
        $order = new Order();
        $order->setUser($user);
        $order->setFullName($details['fullName']);
        $order->setAddress($details['address']);
        $order->setPhone($details['phone']);
        $order->setPaymentMethod($details['paymentMethod']);
        $order->setTotalAmount($subtotal);
        $order->setCreatedAt(new \DateTimeImmutable());

        $em->persist($order);

        // Add item
        $orderItem = new OrderItem();
        $orderItem->setOrders($order);
        $orderItem->setProductId($item['id']);
        $orderItem->setName($item['name']);
        $orderItem->setQuantity($item['qty']);
        $orderItem->setSubtotal($subtotal);

        $em->persist($orderItem);

        $em->flush();

        // log after flush so order id exists
        $this->logger->log("User placed buy-now order", "Order ID: " . $order->getId());

        // Clear Buy Now data
        $session->remove('buy_now');
        $session->remove('buy_now_details');

        return $this->redirectToRoute('orders_list');
    }
}
