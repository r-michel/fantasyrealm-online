<?php

namespace App\Controller\BackOffice;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/back-office', name: 'app_back_office_dashboard_')]
#[IsGranted('ROLE_EMPLOYEE')]
final class DashboardController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('back_office/dashboard/index.html.twig', [
            'pendingCount' => 3,
        ]);
    }
}
