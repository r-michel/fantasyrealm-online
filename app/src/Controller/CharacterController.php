<?php

namespace App\Controller;

use App\Entity\Character;
use App\Entity\User;
use App\Form\CharacterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CharacterController extends AbstractController
{
    #[Route('/characters', name: 'app_character_index')]
    public function index(): Response
    {
        return $this->render('character/index.html.twig', [
            'controller_name' => 'CharacterController',
        ]);
    }

    #[Route('/character/new', name: 'app_character_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $character = new Character();
        $character
            ->setOwner($user)
            ->setShared(false)
            ->setAuthorized(false)
            ->setCreatedAt(new \DateTimeImmutable());

        $form = $this->createForm(CharacterType::class, $character);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $generatedImage = $form
                ->get('generatedImage')
                ->getData();

            if (is_string($generatedImage) && $generatedImage !== '') {
                $character->setImage(
                    $this->decodeGeneratedImage($generatedImage)
                );
            }

            $entityManager->persist($character);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Votre personnage a été envoyé à notre équipe pour validation.'
            );

            return $this->redirectToRoute('app_account');
        }

        return $this->render('character/new.html.twig', [
            'characterForm' => $form,
        ]);
    }

    #[Route('/character/{id}/edit', name: 'app_character_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Character $character): Response
    {
        if ($character->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        // TODO : afficher et traiter le formulaire de modification.

        return $this->render('character/edit.html.twig', [
            'character' => $character,
        ]);
    }

    #[Route(
        '/character/{id}/toggle-share',
        name: 'app_character_toggle_share',
        methods: ['POST']
    )]
    #[IsGranted('ROLE_USER')]
    public function toggleShare(Character $character): RedirectResponse
    {
        if ($character->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        // TODO : inverser l’état de partage du personnage.

        return $this->redirectToRoute('app_account');
    }

    #[Route(
        '/character/{id}/duplicate',
        name: 'app_character_duplicate',
        methods: ['POST']
    )]
    #[IsGranted('ROLE_USER')]
    public function duplicate(Character $character): RedirectResponse
    {
        if ($character->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        // TODO : dupliquer le personnage et ses relations utiles.

        return $this->redirectToRoute('app_account');
    }

    #[Route('/character/{id}', name: 'app_character_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Character $character): RedirectResponse
    {
        if ($character->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        // TODO : supprimer le personnage après vérification du token CSRF.

        return $this->redirectToRoute('app_account');
    }

    private function decodeGeneratedImage(string $dataUrl): string
    {
        if (!preg_match(
            '#^data:image/png;base64,(.+)$#',
            $dataUrl,
            $matches
        )) {
            throw new \InvalidArgumentException(
                'Le format de l’image générée est invalide.'
            );
        }

        $binary = base64_decode($matches[1], true);

        if ($binary === false) {
            throw new \InvalidArgumentException(
                'Impossible de décoder l’image générée.'
            );
        }

        if (strlen($binary) > 2 * 1024 * 1024) {
            throw new \InvalidArgumentException(
                'L’image générée est trop volumineuse.'
            );
        }

        $imageInfo = getimagesizefromstring($binary);

        if ($imageInfo === false || $imageInfo['mime'] !== 'image/png') {
            throw new \InvalidArgumentException(
                'L’image générée doit être un fichier PNG valide.'
            );
        }

        return $binary;
    }
    }
