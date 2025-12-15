<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\OrderRepository;
use App\Repository\ItemRepository;
use App\Repository\CartItemRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/users')]
class AdminUserController extends AbstractController
{
    public function __construct(private ActivityLogger $logger) {}

    #[Route('/', name: 'admin_users')]
    public function index(
        UserRepository $userRepo,
        OrderRepository $orderRepo,
        ItemRepository $itemRepo,
        CartItemRepository $cartRepo
    ): Response {

        $this->logger->log("Admin viewed Manage Users");

        return $this->render('admin/users/index.html.twig', [
            'users'        => $userRepo->findAll(),
            'totalUsers'   => $userRepo->count([]),
            'totalOrders'  => $orderRepo->count([]),
            'totalItems'   => $itemRepo->count([]),
            'totalCart'    => $cartRepo->count([]),
        ]);
    }

    #[Route('/create', name: 'admin_user_create')]
public function create(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher, ActivityLogger $logger): Response
{
    $user = new User();

    if ($request->isMethod('POST')) {

        $user->setFirstName($request->request->get('firstName'));
        $user->setLastName($request->request->get('lastName'));
        $user->setEmail($request->request->get('email'));
        $user->setContactNumber($request->request->get('contact'));
        $user->setAddress($request->request->get('address'));
        $user->setRoles([$request->request->get('role')]);

        $hashed = $hasher->hashPassword($user, $request->request->get('password'));
        $user->setPassword($hashed);

        $em->persist($user);
        $em->flush();

        $logger->log("Admin created user", "User: {$user->getEmail()}");

        return $this->redirectToRoute('admin_users');
    }

    return $this->render('admin/users/create.html.twig');
}
#[Route('/edit/{id}', name: 'admin_user_edit')]
public function edit(
    int $id,
    UserRepository $repo,
    Request $request,
    EntityManagerInterface $em,
    ActivityLogger $logger
): Response {

    $user = $repo->find($id);
    if (!$user) return $this->redirectToRoute('admin_users');

    if ($request->isMethod('POST')) {

        $user->setFirstName($request->request->get('firstName'));
        $user->setLastName($request->request->get('lastName'));
        $user->setEmail($request->request->get('email'));
        $user->setContactNumber($request->request->get('contact'));
        $user->setAddress($request->request->get('address'));
        $user->setRoles([$request->request->get('role')]);

        $em->flush();

        $logger->log("Admin updated user", "User: {$user->getEmail()}");

        return $this->redirectToRoute('admin_users');
    }

    return $this->render('admin/users/edit.html.twig', [
        'user' => $user
    ]);
}
#[Route('/delete/{id}', name: 'admin_user_delete')]
public function delete(
    int $id,
    UserRepository $repo,
    EntityManagerInterface $em,
    ActivityLogger $logger
){
    $user = $repo->find($id);
    if (!$user) return $this->redirectToRoute('admin_users');

    // Remove related orders + carts
    foreach ($user->getOrders() as $o) {
        foreach ($o->getOrderItems() as $i) $em->remove($i);
        $em->remove($o);
    }

    foreach ($user->getCartItems() as $cart) {
        $em->remove($cart);
    }

    $logger->log("Admin deleted user", "User: {$user->getEmail()}");

    $em->remove($user);
    $em->flush();

    return $this->redirectToRoute('admin_users');
}

#[Route('/toggle/{id}', name: 'admin_user_toggle')]
public function toggleUser(
    int $id,
    UserRepository $repo,
    EntityManagerInterface $em,
    ActivityLogger $logger
){
    $user = $repo->find($id);

    if (!$user) return $this->redirectToRoute('admin_users');

    $user->setIsActive(!$user->isActive());

    $status = $user->isActive() ? "activated" : "deactivated";
    
    $logger->log("Admin $status user", "User: {$user->getEmail()}");

    $em->flush();

    return $this->redirectToRoute('admin_users');
}

}
