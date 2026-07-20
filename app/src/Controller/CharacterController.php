<?php

namespace App\Controller;

use App\Entity\Character;
use App\Entity\User;
use App\Form\CharacterType;
use App\Repository\CharacterRepository;
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
            if ($this->hasDuplicateEquipmentCategories($character)) {
                $this->addFlash(
                    'danger',
                    'Vous ne pouvez sélectionner qu’un équipement par catégorie.',
                );

                return $this->render('character/new.html.twig', [
                    'characterForm' => $form,
                ]);
            }

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

    #[Route(
        '/character/{id}/edit',
        name: 'app_character_edit',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_USER')]
    public function edit(
        Character $character,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        if ($character->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $originalName = $character->getName();

        $form = $this->createForm(
            CharacterType::class,
            $character,
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->hasDuplicateEquipmentCategories($character)) {
                $this->addFlash(
                    'danger',
                    'Vous ne pouvez sélectionner qu’un équipement par catégorie.',
                );

                return $this->render('character/edit.html.twig', [
                    'character' => $character,
                    'characterForm' => $form,
                ]);
            }

            $generatedImage = $form
                ->get('generatedImage')
                ->getData();

            if (is_string($generatedImage) && $generatedImage !== '') {
                $character->setImage(
                    $this->decodeGeneratedImage($generatedImage),
                );
            }

            $nameHasChanged = $originalName !== $character->getName();

            if ($nameHasChanged) {
                $character->setAuthorized(false);
            }

            $character->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

            $message = $nameHasChanged
                ? sprintf(
                    'Le personnage « %s » a été modifié et son nouveau nom doit être validé.',
                    $character->getName(),
                )
                : sprintf(
                    'Le personnage « %s » a été modifié.',
                    $character->getName(),
                );

            $this->addFlash('success', $message);

            return $this->redirectToRoute('app_account');
        }

        return $this->render('character/edit.html.twig', [
            'character' => $character,
            'characterForm' => $form,
        ]);
    }

    #[Route(
        '/character/{id}/share',
        name: 'app_character_share',
        methods: ['POST'],
    )]
    public function share(
        Character $character,
        Request $request,
        EntityManagerInterface $entityManager,
    ): RedirectResponse {
        if ($character->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException(
                'Vous ne pouvez pas modifier le partage de ce personnage.',
            );
        }

        $token = $request->request->getString('_token');

        if (!$this->isCsrfTokenValid(
            'share' . $character->getId(),
            $token,
        )) {
            $this->addFlash(
                'error',
                'Le jeton de sécurité est invalide. Veuillez réessayer.',
            );

            return $this->redirectToRoute('app_account');
        }

        $character->setShared(!$character->isShared());

        $entityManager->flush();

        $message = $character->isShared()
            ? sprintf(
                'Le personnage « %s » est maintenant partagé.',
                $character->getName(),
            )
            : sprintf(
                'Le personnage « %s » n’est plus partagé.',
                $character->getName(),
            );

        $this->addFlash('success', $message);

        return $this->redirectToRoute('app_account');
    }

    #[Route(
        '/character/{id}/duplicate',
        name: 'app_character_duplicate',
        methods: ['POST']
    )]
    #[IsGranted('ROLE_USER')]
    public function duplicate(
        Character $character,
        Request $request,
        CharacterRepository $characterRepository,
        EntityManagerInterface $entityManager,
    ): RedirectResponse {
        if ($character->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid(
            'duplicate-character-' . $character->getId(),
            $request->request->getString('_token'),
        )) {
            throw $this->createAccessDeniedException(
                'Le jeton CSRF est invalide.',
            );
        }

        /** @var User $user */
        $user = $this->getUser();

        $duplicate = new Character();

        $duplicate
            ->setName(
                $this->generateUniqueDuplicateName(
                    $character->getName(),
                    $characterRepository,
                ),
            )
            ->setGender($character->getGender())
            ->setSkinColor($character->getSkinColor())
            ->setHairColor($character->getHairColor())
            ->setEyeColor($character->getEyeColor())
            ->setEyeShape($character->getEyeShape())
            ->setNoseShape($character->getNoseShape())
            ->setMouthShape($character->getMouthShape())
            ->setOwner($user)
            ->setShared(false)
            ->setAuthorized(false)
            ->setCreatedAt(new \DateTimeImmutable());

        $image = $character->getImage();

        if (is_resource($image)) {
            rewind($image);
            $image = stream_get_contents($image);
        }

        if (is_string($image) && $image !== '') {
            $duplicate->setImage($image);
        }

        foreach ($character->getEquipment() as $equipment) {
            $duplicate->addEquipment($equipment);
        }

        $entityManager->persist($duplicate);
        $entityManager->flush();

        $this->addFlash(
            'success',
            sprintf(
                'Le personnage « %s » a été dupliqué.',
                $duplicate->getName(),
            ),
        );

        return $this->redirectToRoute('app_account');
    }

    #[Route(
        '/character/{id}/delete',
        name: 'app_character_delete',
        methods: ['POST'],
    )]
    public function delete(
        Character $character,
        Request $request,
        EntityManagerInterface $entityManager,
    ): RedirectResponse {
        if ($character->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException(
                'Vous ne pouvez pas supprimer ce personnage.',
            );
        }

        $token = $request->request->getString('_token');

        if (!$this->isCsrfTokenValid(
            'delete' . $character->getId(),
            $token,
        )) {
            $this->addFlash(
                'error',
                'Le jeton de sécurité est invalide. Veuillez réessayer.',
            );

            return $this->redirectToRoute('app_account');
        }

        $characterName = $character->getName();

        $entityManager->remove($character);
        $entityManager->flush();

        $this->addFlash(
            'success',
            sprintf(
                'Le personnage « %s » a bien été supprimé.',
                $characterName,
            ),
        );

        return $this->redirectToRoute('app_account');
    }

    #[Route(
        '/character/{id}/image',
        name: 'app_character_image',
        methods: ['GET'],
    )]
    public function image(Character $character): Response
    {
        $image = $character->getImage();

        if (is_resource($image)) {
            $image = stream_get_contents($image);
        }

        if (!is_string($image) || $image === '') {
            throw $this->createNotFoundException(
                'Ce personnage ne possède pas d’image.',
            );
        }

        return new Response($image, Response::HTTP_OK, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, max-age=3600',
        ]);
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

    private function hasDuplicateEquipmentCategories(
        Character $character,
    ): bool {
        $categories = [];

        foreach ($character->getEquipment() as $equipment) {
            $categoryCode = $equipment
                ->getCategory()
                ?->getCode();

            if ($categoryCode === null) {
                continue;
            }

            if (isset($categories[$categoryCode])) {
                return true;
            }

            $categories[$categoryCode] = true;
        }

        return false;
    }

    private function generateUniqueDuplicateName(
        string $originalName,
        CharacterRepository $characterRepository,
    ): string {
        $baseName = $originalName . ' - copie';
        $candidate = $baseName;
        $number = 2;

        while ($characterRepository->findOneBy([
            'name' => $candidate,
        ]) !== null) {
            $candidate = sprintf(
                '%s %d',
                $baseName,
                $number,
            );

            ++$number;
        }

        return $candidate;
    }
}
