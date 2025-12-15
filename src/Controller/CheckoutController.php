<?php

namespace App\Controller;

use App\Entity\Orders;
use App\Entity\OrderItem;
use App\Repository\ItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/checkout')]
class CheckoutController extends AbstractController
{
    #[Route('', name: 'checkout')]
    public function checkoutPage(SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);

        if (empty($cart)) {
            return $this->redirectToRoute('cart');
        }

        return $this->render('checkout/checkout.html.twig', [
            'cart' => $cart,
        ]);
    }

    #[Route('/process', name: 'checkout_process', methods: ['POST'])]
    public function processCheckout(
        Request $request,
        SessionInterface $session,
        EntityManagerInterface $em
    ): Response {

        $cart = $session->get('cart', []);

        if (empty($cart)) {
            return $this->redirectToRoute('cart');
        }

        // Create Order
        $order = new Order();
        $order->setUser($this->getUser());
        $order->setFullName($request->request->get('fullName'));
        $order->setAddress($request->request->get('address'));
        $order->setPhone($request->request->get('phone'));
        $order->setPaymentMethod($request->request->get('paymentMethod'));
        $order->setCreatedAt(new \DateTimeImmutable());

        $total = 0;

        foreach ($cart as $item) {
            $orderItem = new OrderItem();

            $orderItem->setOrder($order);
            $orderItem->setProductId($item['id']);
            $orderItem->setName($item['name']);
            $orderItem->setPrice($item['price']);
            $orderItem->setQuantity($item['quantity']);
            $orderItem->setSubtotal($item['price'] * $item['quantity']);

            $total += $item['price'] * $item['quantity'];

            $em->persist($orderItem);
        }

        $order->setTotalAmount($total);
        $em->persist($order);
        $em->flush();

        // Clear cart
        $session->remove('cart');

        return $this->redirectToRoute('checkout_success', [
            'id' => $order->getId()
        ]);
    }

    #[Route('/success/{id}', name: 'checkout_success')]
    public function success(Order $order): Response
    {
        return $this->render('checkout/success.html.twig', [
            'order' => $order
        ]);
    }
    #[Route('/orders', name: 'shop_orders')]
public function orders(EntityManagerInterface $em): Response
{
    $orders = $em->getRepository(Order::class)
                 ->findBy(['user' => $this->getUser()], ['id' => 'DESC']);

    return $this->render('shop/orders.html.twig', [
        'orders' => $orders,
    ]);
}

}
