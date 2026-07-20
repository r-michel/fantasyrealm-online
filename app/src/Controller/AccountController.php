<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CharacterRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class AccountController extends AbstractController
{
    #[Route('/account', name: 'app_account')]
    public function index(CharacterRepository $characterRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $characters = $characterRepository->findBy(
            ['owner' => $user],
            ['createdAt' => 'DESC'],
        );

        return $this->render('account/index.html.twig', [
            'characters' => $characters,
        ]);
    }
}
