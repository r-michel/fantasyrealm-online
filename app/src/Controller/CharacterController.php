<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CharacterController extends AbstractController
{
    #[Route('/characters', name: 'app_character_index')]
    public function index(): Response
    {
        return $this->render('character/index.html.twig', [
            'controller_name' => 'CharacterController',
        ]);
    }

    #[Route('/characters/new', name: 'app_character_new')]
    public function new(): Response
    {
        return $this->render('character/new.html.twig', [
            'controller_name' => 'CharacterController',
        ]);
    }
}
