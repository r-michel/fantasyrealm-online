<?php

namespace App\Controller\BackOffice;

use App\Entity\Equipment;
use App\Entity\User;
use App\Factory\Activity\EquipmentActivityFactory;
use App\Form\EquipmentType;
use App\Repository\EquipmentRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/back-office/catalog', name: 'app_back_office_catalog_')]
#[IsGranted('ROLE_EMPLOYEE')]
final class CatalogController extends AbstractController
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly EquipmentActivityFactory $equipmentActivityFactory,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EquipmentRepository $equipmentRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $equipment = new Equipment();

        $form = $this->createForm(
            EquipmentType::class,
            $equipment,
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile instanceof UploadedFile) {
                $imageContent = file_get_contents(
                    $imageFile->getPathname(),
                );

                if ($imageContent === false) {
                    $this->addFlash(
                        'error',
                        'Impossible de lire l’image envoyée.',
                    );

                    return $this->redirectToRoute(
                        'app_back_office_catalog_index',
                    );
                }

                $equipment->setImage($imageContent);
            }

            $equipment
                ->setActive(true)
                ->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($equipment);
            $entityManager->flush();

            $this->activityLogger->save(
                $this->equipmentActivityFactory->created(
                    actor: $this->getActivityActor(),
                    equipment: $equipment,
                ),
            );

            $this->addFlash(
                'success',
                sprintf(
                    'L’équipement « %s » a bien été ajouté.',
                    $equipment->getName(),
                ),
            );

            return $this->redirectToRoute(
                'app_back_office_catalog_index',
            );
        }

        return $this->render(
            'back_office/catalog/index.html.twig',
            [
                'equipmentForm' => $form,
                'equipmentList' => $equipmentRepository->findBy(
                    [],
                    ['createdAt' => 'DESC'],
                ),
            ],
        );
    }

    #[Route(
        '/{id}/toggle',
        name: 'toggle',
        methods: ['POST'],
    )]
    public function toggle(
        Equipment $equipment,
        Request $request,
        EntityManagerInterface $entityManager,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid(
            'toggle-equipment-'.$equipment->getId(),
            $request->request->getString('_token'),
        )) {
            throw $this->createAccessDeniedException();
        }

        $equipment
            ->setActive(!$equipment->isActive())
            ->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->flush();

        $actor = $this->getActivityActor();

        $activity = $equipment->isActive()
            ? $this->equipmentActivityFactory->activated(
                actor: $actor,
                equipment: $equipment,
            )
            : $this->equipmentActivityFactory->deactivated(
                actor: $actor,
                equipment: $equipment,
            );

        $this->activityLogger->save($activity);

        $this->addFlash(
            'success',
            $equipment->isActive()
                ? 'L’équipement a été activé.'
                : 'L’équipement a été désactivé.',
        );

        return $this->redirectToRoute(
            'app_back_office_catalog_index',
        );
    }

    #[Route(
        '/{id}/delete',
        name: 'delete',
        methods: ['POST'],
    )]
    public function delete(
        Equipment $equipment,
        Request $request,
        EntityManagerInterface $entityManager,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid(
            'delete-equipment-'.$equipment->getId(),
            $request->request->getString('_token'),
        )) {
            throw $this->createAccessDeniedException();
        }

        if (!$equipment->getOnCharacter()->isEmpty()) {
            $this->addFlash(
                'error',
                'Cet équipement est utilisé par au moins un personnage. Désactivez-le plutôt que de le supprimer.',
            );

            return $this->redirectToRoute(
                'app_back_office_catalog_index',
            );
        }

        $activity = $this->equipmentActivityFactory->deleted(
            actor: $this->getActivityActor(),
            equipment: $equipment,
        );

        $entityManager->remove($equipment);
        $entityManager->flush();

        $this->activityLogger->save($activity);

        $this->addFlash(
            'success',
            'L’équipement a été supprimé.',
        );

        return $this->redirectToRoute(
            'app_back_office_catalog_index',
        );
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
