<?php

namespace App\Service;

use App\Entity\ActivityLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class ActivityLogger
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security
    ) {}

    public function log(string $action, string $targetData = null): void
    {
        $user = $this->security->getUser();
        if (!$user) return;

        $log = new ActivityLog();
        $log->setUserId($user->getId());
        $log->setUsername($user->getEmail());
        $log->setRole($user->getRoles()[0]);
        $log->setAction($action);
        $log->setTargetData($targetData);

        $this->em->persist($log);
        $this->em->flush();
    }
}
    