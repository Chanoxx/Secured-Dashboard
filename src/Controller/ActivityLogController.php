<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ActivityLogController extends AbstractController
{
    #[Route('/admin/logs', name: 'admin_activity_logs')]
    public function index(ActivityLogRepository $repo): Response
    {
        $logs = $repo->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/activity_logs.html.twig', [
            'logs' => $logs,
        ]);
    }
}
