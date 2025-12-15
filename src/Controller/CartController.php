<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\CartItem;
use App\Repository\ItemRepository;
use App\Repository\CartItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Service\ActivityLogger;

#[IsGranted('ROLE_USER')]
#[Route('/cart')]
class CartController extends AbstractController
{
    private ActivityLogger $logger;

    public function __construct(ActivityLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * VIEW CART (USING DATABASE CART ITEMS)
     */
    #[Route('', name: 'cart')]
    public function view(CartItemRepository $cartRepo): Response
    {
        $user = $this->getUser();
        $cartItems = $cartRepo->findBy(['user' => $user]);

        return $this->render('cart/index.html.twig', [
            'cart' => $cartItems,
        ]);
    }

    /**
     * ADD ITEM TO CART
     */
    #[Route('/add/{id}', name: 'cart_add', methods: ['POST'])]
    public function add(
        int $id,
        Request $request,
        ItemRepository $itemRepo,
        CartItemRepository $cartRepo,
        EntityManagerInterface $em
    ): Response {

        // log after we know id param
        $this->logger->log("User added item to cart", "Product ID: $id");

        $user = $this->getUser();
        $product = $itemRepo->find($id);

        if (!$product) {
            throw $this->createNotFoundException("Product not found.");
        }

        $quantity = max(1, (int) $request->request->get('quantity', 1));

        // Check if item already exists
        $existing = $cartRepo->findOneBy([
            'user' => $user,
            'product' => $product,
        ]);

        if ($existing) {
            $existing->setQuantity($existing->getQuantity() + $quantity);
            $em->flush();
        } else {
            $cartItem = new CartItem();
            $cartItem->setUser($user);
            $cartItem->setProduct($product);
            $cartItem->setQuantity($quantity);

            $em->persist($cartItem);
            $em->flush();
        }

        return $this->redirectToRoute('cart');
    }

    /**
     * EDIT ITEM IN CART
     */
    #[Route('/item/{id}', name: 'cart_item_edit', methods: ['GET'])]
    public function editItem(int $id, CartItemRepository $cartRepo): Response
    {
        $cartItem = $cartRepo->find($id);

        if (!$cartItem || $cartItem->getUser() !== $this->getUser()) {
            return $this->redirectToRoute('cart');
        }

        return $this->render('cart/edit_item.html.twig', [
            'item' => $cartItem,
            'product' => $cartItem->getProduct(),
        ]);
    }

    /**
     * UPDATE ITEM QUANTITY
     */
    #[Route('/item/{id}/update', name: 'cart_item_update', methods: ['POST'])]
    public function updateItem(
        int $id,
        Request $request,
        CartItemRepository $cartRepo,
        EntityManagerInterface $em
    ): Response {

        $this->logger->log("User updated cart quantity", "Cart Item ID: $id");

        $cartItem = $cartRepo->find($id);

        if (!$cartItem || $cartItem->getUser() !== $this->getUser()) {
            return $this->redirectToRoute('cart');
        }

        $quantity = max(1, (int) $request->request->get('quantity'));
        $cartItem->setQuantity($quantity);

        $em->flush();

        return $this->redirectToRoute('cart');
    }

    /**
     * REMOVE ITEM FROM CART
     */
    #[Route('/remove/{id}', name: 'cart_remove')]
    public function remove(
        int $id,
        CartItemRepository $cartRepo,
        EntityManagerInterface $em
    ): Response {

        $this->logger->log("User removed item from cart", "Cart Item ID: $id");

        $cartItem = $cartRepo->find($id);

        if ($cartItem && $cartItem->getUser() === $this->getUser()) {
            $em->remove($cartItem);
            $em->flush();
        }

        return $this->redirectToRoute('cart');
    }

    #[Route('/checkout', name: 'cart_checkout', methods: ['GET', 'POST'])]
    public function checkout(Request $request, CartItemRepository $cartRepo): Response
    {
        $user = $this->getUser();

        // ONLY retrieve selected items if POST
        if ($request->isMethod('POST')) {
            $selectedIds = $request->request->all('selected_items');

            if (empty($selectedIds)) {
                $this->addFlash('error', 'Please select at least one item.');
                return $this->redirectToRoute('cart');
            }

            // Save selected items
            $request->getSession()->set('selected_items', $selectedIds);
        }

        // Always read selected IDs from session
        $selectedIds = $request->getSession()->get('selected_items', []);

        if (empty($selectedIds)) {
            return $this->redirectToRoute('cart');
        }

        // Fetch selected items
        $cartItems = $cartRepo->findBy([
            'user' => $user,
            'id'   => $selectedIds
        ]);

        return $this->render('checkout/checkout.html.twig', [
            'cart' => $cartItems,
        ]);
    }

    #[Route('/checkout/place', name: 'cart_place_order', methods: ['POST'])]
    public function placeOrder(
        Request $request,
        CartItemRepository $cartRepo,
        EntityManagerInterface $em
    ): Response {

        $user = $this->getUser();

        // Retrieve session data
        $session = $request->getSession();
        $info = $session->get('checkout_data', []);

        $selectedIds = $info['selectedIds'] ?? [];

        if (empty($selectedIds)) {
            return $this->redirectToRoute('cart');
        }

        // Fetch selected items
        $cartItems = $cartRepo->findBy([
            'user' => $user,
            'id'   => $selectedIds
        ]);

        if (empty($cartItems)) {
            return $this->redirectToRoute('cart');
        }

        // Compute total
        $total = 0;
        foreach ($cartItems as $cartItem) {
            $total += $cartItem->getProduct()->getPrice() * $cartItem->getQuantity();
        }

        // Create Order
        $order = new Order();
        $order->setUser($user);
        $order->setFullName($info['fullName'] ?? '');
        $order->setAddress($info['address'] ?? '');
        $order->setPhone($info['phone'] ?? '');
        $order->setPaymentMethod($info['paymentMethod'] ?? '');
        $order->setTotalAmount($total);
        $order->setCreatedAt(new \DateTimeImmutable());

        $em->persist($order);

        // Save Order Items
        foreach ($cartItems as $cartItem) {
            $orderItem = new OrderItem();
            $orderItem->setOrders($order);
            $orderItem->setProductId($cartItem->getProduct()->getId());
            $orderItem->setName($cartItem->getProduct()->getItemName());
            $orderItem->setQuantity($cartItem->getQuantity());
            $orderItem->setSubtotal(
                $cartItem->getQuantity() * $cartItem->getProduct()->getPrice()
            );

            $em->persist($orderItem);

            // Remove item from cart
            $em->remove($cartItem);
        }

        $em->flush();

        // Now logging after order was persisted (so we have an order id)
        $this->logger->log("User placed an order", "Order ID: " . $order->getId());

        // Clear session data
        $session->remove('checkout_data');

        return $this->redirectToRoute('orders_list');
    }

    #[Route('/checkout/confirm', name: 'cart_confirm_order', methods: ['POST'])]
    public function confirmOrder(Request $request, CartItemRepository $cartRepo): Response
    {
        $user = $this->getUser();

        // IDs of selected items
        $selectedIds = $request->request->all('selected_items');

        if (empty($selectedIds)) {
            $this->addFlash('error', 'No items selected.');
            return $this->redirectToRoute('cart');
        }

        // Fetch selected cart items
        $cartItems = $cartRepo->findBy([
            'user' => $user,
            'id'   => $selectedIds
        ]);

        // Save ALL checkout data in session
        $checkoutData = [
            'fullName'       => $request->request->get('fullName'),
            'address'        => $request->request->get('address'),
            'phone'          => $request->request->get('phone'),
            'paymentMethod'  => $request->request->get('paymentMethod'),
            'selectedIds'    => $selectedIds
        ];

        $request->getSession()->set('checkout_data', $checkoutData);

        return $this->render('checkout/confirm.html.twig', [
            'cart' => $cartItems,
            'data' => $checkoutData
        ]);
    }
}
