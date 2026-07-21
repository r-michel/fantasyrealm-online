<?php

namespace App\Controller\BackOffice;

use App\Entity\User;
use App\Factory\Activity\UserActivityFactory;
use App\Repository\UserRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/back-office/users', name: 'app_back_office_user_')]
#[IsGranted('ROLE_EMPLOYEE')]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly UserActivityFactory $userActivityFactory,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        UserRepository $userRepository,
    ): Response {
        return $this->render('back_office/user/index.html.twig', [
            'users' => $userRepository->findRegularUsers(),
        ]);
    }

    #[Route(
        '/{id}/toggle-suspension',
        name: 'toggle_suspension',
        methods: ['POST'],
    )]
    public function toggleSuspension(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid(
            'toggle-suspension-user-'.$user->getId(),
            $request->request->getString('_token'),
        )) {
            throw $this->createAccessDeniedException();
        }

        if (
            in_array('ROLE_EMPLOYEE', $user->getRoles(), true)
            || in_array('ROLE_ADMIN', $user->getRoles(), true)
        ) {
            throw $this->createAccessDeniedException(
                'Ce compte ne peut pas être géré depuis cet écran.',
            );
        }

        $user->setSuspended(!$user->isSuspended());

        $entityManager->flush();

        $actor = $this->getActivityActor();

        $activity = $user->isSuspended()
            ? $this->userActivityFactory->suspended(
                actor: $actor,
                target: $user,
            )
            : $this->userActivityFactory->unsuspended(
                actor: $actor,
                target: $user,
            );

        $this->activityLogger->save($activity);

        $this->addFlash(
            'success',
            $user->isSuspended()
                ? sprintf(
                    'Le compte de %s a été suspendu.',
                    $user->getUsername(),
                )
                : sprintf(
                    'Le compte de %s a été réactivé.',
                    $user->getUsername(),
                ),
        );

        return $this->redirectToRoute(
            'app_back_office_user_index',
        );
    }

    #[Route(
        '/{id}/delete',
        name: 'delete',
        methods: ['POST'],
    )]
    public function delete(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid(
            'delete-user-'.$user->getId(),
            $request->request->getString('_token'),
        )) {
            throw $this->createAccessDeniedException();
        }

        if (
            in_array('ROLE_EMPLOYEE', $user->getRoles(), true)
            || in_array('ROLE_ADMIN', $user->getRoles(), true)
        ) {
            throw $this->createAccessDeniedException(
                'Ce compte ne peut pas être supprimé depuis cet écran.',
            );
        }

        $username = $user->getUsername();

        $activity = $this->userActivityFactory->deleted(
            actor: $this->getActivityActor(),
            target: $user,
        );

        $entityManager->remove($user);
        $entityManager->flush();

        $this->activityLogger->save($activity);

        $this->addFlash(
            'success',
            sprintf(
                'Le compte de %s ainsi que ses données ont été supprimés.',
                $username,
            ),
        );

        return $this->redirectToRoute(
            'app_back_office_user_index',
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
