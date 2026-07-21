<?php

namespace App\Controller;

use App\Entity\Character;
use App\Entity\Comment;
use App\Entity\User;
use App\Factory\Activity\CharacterActivityFactory;
use App\Form\CharacterType;
use App\Form\CommentType;
use App\Repository\CharacterRepository;
use App\Repository\CommentRepository;
use App\Service\ActivityLogger;
use App\Service\CharacterPublicIdGenerator;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CharacterController extends AbstractController
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly CharacterActivityFactory $characterActivityFactory,
    ) {
    }

    #[Route('/characters', name: 'app_character_index', methods: ['GET'])]
    public function index(
        Request $request,
        CharacterRepository $characterRepository,
    ): Response {
        $gender = trim($request->query->getString('gender'));
        $creatorPseudo = trim(
            $request->query->getString('creator'),
        );

        $createdAfter = $this->createDateFromQuery(
            $request->query->getString('created_after'),
        );

        $createdBefore = $this->createDateFromQuery(
            $request->query->getString('created_before'),
        );

        $characters = $characterRepository->findPublicCharacters(
            gender: $gender !== '' ? $gender : null,
            createdAfter: $createdAfter,
            createdBefore: $createdBefore,
            creatorPseudo: $creatorPseudo !== ''
                ? $creatorPseudo
                : null,
        );

        return $this->render('character/index.html.twig', [
            'characters' => $characters,
            'filters' => [
                'gender' => $gender,
                'createdAfter' => $request->query->getString(
                    'created_after',
                ),
                'createdBefore' => $request->query->getString(
                    'created_before',
                ),
                'creator' => $creatorPseudo,
            ],
        ]);
    }

    #[Route(
        '/characters/{publicId}',
        name: 'app_character_show',
        requirements: [
            'publicId' => '[a-z0-9-]+',
        ],
        methods: ['GET'],
    )]
    public function show(
        #[MapEntity(mapping: ['publicId' => 'publicId'])]
        Character $character,
        CommentRepository $commentRepository,
    ): Response {
        if (!$character->isShared() || !$character->isAuthorized()) {
            throw $this->createNotFoundException(
                'Ce personnage n’est pas disponible publiquement.',
            );
        }

        $publishedComments = $commentRepository
            ->findPublishedByCharacter($character);

        $currentUserComment = null;
        $commentForm = null;

        $user = $this->getUser();

        if ($user instanceof User) {
            $currentUserComment = $commentRepository
                ->findOneByOwnerAndCharacter($user, $character);

            if (
                $character->getOwner() !== $user
                && $currentUserComment === null
            ) {
                $comment = new Comment();

                $commentForm = $this->createForm(
                    CommentType::class,
                    $comment,
                    [
                        'action' => $this->generateUrl(
                            'app_character_comment_create',
                            [
                                'publicId' => $character->getPublicId(),
                            ],
                        ),
                        'method' => 'POST',
                    ],
                );
            }
        }

        return $this->render('character/show.html.twig', [
            'character' => $character,
            'publishedComments' => $publishedComments,
            'currentUserComment' => $currentUserComment,
            'commentForm' => $commentForm?->createView(),
        ]);
    }

    #[Route(
        '/character/new',
        name: 'app_character_new',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_USER')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        CharacterPublicIdGenerator $publicIdGenerator,
    ): Response {
        $user = $this->getActivityActor();

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
                    $this->decodeGeneratedImage($generatedImage),
                );
            }

            if ($character->getPublicId() === null) {
                $character->setPublicId(
                    $publicIdGenerator->generate(
                        $character->getName(),
                    ),
                );
            }

            $entityManager->persist($character);
            $entityManager->flush();

            $this->activityLogger->save(
                $this->characterActivityFactory->created(
                    actor: $user,
                    character: $character,
                    details: [
                        'name' => $character->getName(),
                        'appearance' => $this->getAppearanceSnapshot(
                            $character,
                        ),
                        'equipment' => $this->getEquipmentSnapshot(
                            $character,
                        ),
                    ],
                ),
            );

            $this->addFlash(
                'success',
                'Votre personnage a été envoyé à notre équipe pour validation.',
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
        $actor = $this->getActivityActor();

        if ($character->getOwner() !== $actor) {
            throw $this->createAccessDeniedException();
        }

        $originalName = $character->getName();
        $originalAppearance = $this->getAppearanceSnapshot($character);
        $originalEquipment = $this->getEquipmentSnapshot($character);

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

            $appearanceChanges = $this->getChanges(
                $originalAppearance,
                $this->getAppearanceSnapshot($character),
            );

            $equipmentChanges = $this->getEquipmentChanges(
                $originalEquipment,
                $this->getEquipmentSnapshot($character),
            );

            if ($nameHasChanged) {
                $character->setAuthorized(false);
            }

            $character->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

            if ($nameHasChanged) {
                $this->activityLogger->save(
                    $this->characterActivityFactory->updated(
                        actor: $actor,
                        character: $character,
                        changes: [
                            'name' => [
                                'from' => $originalName,
                                'to' => $character->getName(),
                            ],
                        ],
                    ),
                );
            }

            if ($appearanceChanges !== []) {
                $this->activityLogger->save(
                    $this->characterActivityFactory->appearanceUpdated(
                        actor: $actor,
                        character: $character,
                        changes: $appearanceChanges,
                    ),
                );
            }

            if ($equipmentChanges !== []) {
                $this->activityLogger->save(
                    $this->characterActivityFactory->equipmentUpdated(
                        actor: $actor,
                        character: $character,
                        changes: $equipmentChanges,
                    ),
                );
            }

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
        $actor = $this->getActivityActor();

        if ($character->getOwner() !== $actor) {
            throw $this->createAccessDeniedException(
                'Vous ne pouvez pas modifier le partage de ce personnage.',
            );
        }

        $token = $request->request->getString('_token');

        if (!$this->isCsrfTokenValid(
            'share'.$character->getId(),
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

        $this->activityLogger->save(
            $this->characterActivityFactory->shared(
                actor: $actor,
                character: $character,
                details: [
                    'shared' => $character->isShared(),
                ],
            ),
        );

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
        methods: ['POST'],
    )]
    #[IsGranted('ROLE_USER')]
    public function duplicate(
        Character $character,
        Request $request,
        CharacterRepository $characterRepository,
        EntityManagerInterface $entityManager,
        CharacterPublicIdGenerator $publicIdGenerator,
    ): RedirectResponse {
        $user = $this->getActivityActor();

        if ($character->getOwner() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid(
            'duplicate-character-'.$character->getId(),
            $request->request->getString('_token'),
        )) {
            throw $this->createAccessDeniedException(
                'Le jeton CSRF est invalide.',
            );
        }

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

        $duplicate->setPublicId(
            $publicIdGenerator->generate(
                $duplicate->getName(),
            ),
        );

        $entityManager->persist($duplicate);
        $entityManager->flush();

        $this->activityLogger->save(
            $this->characterActivityFactory->created(
                actor: $user,
                character: $duplicate,
                details: [
                    'duplicatedFrom' => [
                        'publicId' => $character->getPublicId(),
                        'name' => $character->getName(),
                    ],
                    'name' => $duplicate->getName(),
                    'appearance' => $this->getAppearanceSnapshot(
                        $duplicate,
                    ),
                    'equipment' => $this->getEquipmentSnapshot(
                        $duplicate,
                    ),
                ],
            ),
        );

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
        $actor = $this->getActivityActor();

        if ($character->getOwner() !== $actor) {
            throw $this->createAccessDeniedException(
                'Vous ne pouvez pas supprimer ce personnage.',
            );
        }

        $token = $request->request->getString('_token');

        if (!$this->isCsrfTokenValid(
            'delete'.$character->getId(),
            $token,
        )) {
            $this->addFlash(
                'error',
                'Le jeton de sécurité est invalide. Veuillez réessayer.',
            );

            return $this->redirectToRoute('app_account');
        }

        $characterName = $character->getName();

        $activity = $this->characterActivityFactory->deleted(
            actor: $actor,
            character: $character,
            details: [
                'name' => $characterName,
                'appearance' => $this->getAppearanceSnapshot(
                    $character,
                ),
                'equipment' => $this->getEquipmentSnapshot(
                    $character,
                ),
            ],
        );

        $entityManager->remove($character);
        $entityManager->flush();

        $this->activityLogger->save($activity);

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

    #[Route(
        '/characters/{publicId}/comment',
        name: 'app_character_comment_create',
        requirements: [
            'publicId' => '[a-z0-9-]+',
        ],
        methods: ['POST'],
    )]
    #[IsGranted('ROLE_USER')]
    public function createComment(
        #[MapEntity(mapping: ['publicId' => 'publicId'])]
        Character $character,
        Request $request,
        CommentRepository $commentRepository,
        EntityManagerInterface $entityManager,
    ): RedirectResponse {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (!$character->isShared() || !$character->isAuthorized()) {
            throw $this->createNotFoundException();
        }

        if ($character->getOwner() === $user) {
            $this->addFlash(
                'error',
                'Vous ne pouvez pas laisser un avis sur votre propre personnage.',
            );

            return $this->redirectToRoute('app_character_show', [
                'publicId' => $character->getPublicId(),
            ]);
        }

        $existingComment = $commentRepository
            ->findOneByOwnerAndCharacter($user, $character);

        if ($existingComment !== null) {
            $this->addFlash(
                'error',
                'Vous avez déjà déposé un avis pour ce personnage.',
            );

            return $this->redirectToRoute('app_character_show', [
                'publicId' => $character->getPublicId(),
            ]);
        }

        $rate = $request->request->getInt('rate');

        if ($rate < 1 || $rate > 5) {
            $this->addFlash(
                'error',
                'Veuillez sélectionner une note comprise entre 1 et 5 étoiles.',
            );

            return $this->redirectToRoute('app_character_show', [
                'publicId' => $character->getPublicId(),
            ]);
        }

        $comment = new Comment();

        $comment
            ->setRate($rate)
            ->setOwner($user)
            ->setOnCharacter($character)
            ->setPublished(false);

        $form = $this->createForm(CommentType::class, $comment, [
            'action' => $this->generateUrl(
                'app_character_comment_create',
                [
                    'publicId' => $character->getPublicId(),
                ],
            ),
            'method' => 'POST',
        ]);

        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash(
                'error',
                'L’avis n’a pas pu être envoyé. Vérifiez les informations saisies.',
            );

            return $this->redirectToRoute('app_character_show', [
                'publicId' => $character->getPublicId(),
            ]);
        }

        try {
            $entityManager->persist($comment);
            $entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            $this->addFlash(
                'error',
                'Vous avez déjà déposé un avis pour ce personnage.',
            );

            return $this->redirectToRoute('app_character_show', [
                'publicId' => $character->getPublicId(),
            ]);
        }

        $this->addFlash(
            'success',
            'Votre avis a bien été envoyé et sera visible après validation.',
        );

        return $this->redirectToRoute('app_character_show', [
            'publicId' => $character->getPublicId(),
        ]);
    }

    /**
     * @return array<string, string|null>
     */
    private function getAppearanceSnapshot(
        Character $character,
    ): array {
        return [
            'gender' => $character->getGender(),
            'skinColor' => $character->getSkinColor(),
            'hairColor' => $character->getHairColor(),
            'eyeColor' => $character->getEyeColor(),
            'eyeShape' => $character->getEyeShape(),
            'noseShape' => $character->getNoseShape(),
            'mouthShape' => $character->getMouthShape(),
        ];
    }

    /**
     * @return list<array{
     *     id: string,
     *     name: string,
     *     category: string|null
     * }>
     */
    private function getEquipmentSnapshot(
        Character $character,
    ): array {
        $equipmentSnapshot = [];

        foreach ($character->getEquipment() as $equipment) {
            $equipmentSnapshot[] = [
                'id' => (string) $equipment->getId(),
                'name' => $equipment->getName(),
                'category' => $equipment
                    ->getCategory()
                    ?->getCode(),
            ];
        }

        usort(
            $equipmentSnapshot,
            static fn (array $first, array $second): int =>
                $first['id'] <=> $second['id'],
        );

        return $equipmentSnapshot;
    }

    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     *
     * @return array<string, array{from: mixed, to: mixed}>
     */
    private function getChanges(
        array $before,
        array $after,
    ): array {
        $changes = [];

        foreach ($after as $field => $value) {
            $previousValue = $before[$field] ?? null;

            if ($previousValue === $value) {
                continue;
            }

            $changes[$field] = [
                'from' => $previousValue,
                'to' => $value,
            ];
        }

        return $changes;
    }

    /**
     * @param list<array{
     *     id: string,
     *     name: string,
     *     category: string|null
     * }> $before
     * @param list<array{
     *     id: string,
     *     name: string,
     *     category: string|null
     * }> $after
     *
     * @return array<string, list<array{
     *     id: string,
     *     name: string,
     *     category: string|null
     * }>>
     */
    private function getEquipmentChanges(
        array $before,
        array $after,
    ): array {
        $beforeById = [];

        foreach ($before as $equipment) {
            $beforeById[$equipment['id']] = $equipment;
        }

        $afterById = [];

        foreach ($after as $equipment) {
            $afterById[$equipment['id']] = $equipment;
        }

        $added = array_values(
            array_diff_key($afterById, $beforeById),
        );

        $removed = array_values(
            array_diff_key($beforeById, $afterById),
        );

        $changes = [];

        if ($added !== []) {
            $changes['added'] = $added;
        }

        if ($removed !== []) {
            $changes['removed'] = $removed;
        }

        return $changes;
    }

    private function decodeGeneratedImage(string $dataUrl): string
    {
        if (!preg_match(
            '#^data:image/png;base64,(.+)$#',
            $dataUrl,
            $matches,
        )) {
            throw new \InvalidArgumentException(
                'Le format de l’image générée est invalide.',
            );
        }

        $binary = base64_decode($matches[1], true);

        if ($binary === false) {
            throw new \InvalidArgumentException(
                'Impossible de décoder l’image générée.',
            );
        }

        if (strlen($binary) > 2 * 1024 * 1024) {
            throw new \InvalidArgumentException(
                'L’image générée est trop volumineuse.',
            );
        }

        $imageInfo = getimagesizefromstring($binary);

        if ($imageInfo === false || $imageInfo['mime'] !== 'image/png') {
            throw new \InvalidArgumentException(
                'L’image générée doit être un fichier PNG valide.',
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
        $baseName = $originalName.' - copie';
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

    private function createDateFromQuery(
        string $value,
    ): ?\DateTimeImmutable {
        if ($value === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $value,
        );

        return $date !== false ? $date : null;
    }

    private function getActivityActor(): User
    {
        $actor = $this->getUser();

        if (!$actor instanceof User) {
            throw $this->createAccessDeniedException(
                'Aucun utilisateur authentifié.',
            );
        }

        return $actor;
    }
}
