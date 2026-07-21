<?php

namespace App\Controller\BackOffice;

use App\Entity\User;
use App\Factory\Activity\EmployeeActivityFactory;
use App\Form\EmployeeType;
use App\Repository\UserRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/back-office/employees', name: 'app_back_office_employee_')]
#[IsGranted('ROLE_ADMIN')]
final class EmployeeController extends AbstractController
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly EmployeeActivityFactory $employeeActivityFactory,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): Response {
        $employee = new User();

        $form = $this->createForm(
            EmployeeType::class,
            $employee,
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form
                ->get('plainPassword')
                ->getData();

            $employee
                ->setRoles(['ROLE_EMPLOYEE'])
                ->setIsVerified(true)
                ->setSuspended(false)
                ->setMustUpdatePassword(true);

            $employee->setPassword(
                $passwordHasher->hashPassword(
                    $employee,
                    $plainPassword,
                ),
            );

            $entityManager->persist($employee);
            $entityManager->flush();

            $this->activityLogger->save(
                $this->employeeActivityFactory->created(
                    actor: $this->getActivityActor(),
                    employee: $employee,
                ),
            );

            $this->addFlash(
                'success',
                sprintf(
                    'Le compte employé de %s a été créé.',
                    $employee->getUsername(),
                ),
            );

            return $this->redirectToRoute(
                'app_back_office_employee_index',
            );
        }

        return $this->render(
            'back_office/employee/index.html.twig',
            [
                'employeeForm' => $form,
                'employees' => $userRepository->findEmployees(),
            ],
        );
    }

    #[Route(
        '/{id}/toggle-suspension',
        name: 'toggle_suspension',
        methods: ['POST'],
    )]
    public function toggleSuspension(
        User $employee,
        Request $request,
        EntityManagerInterface $entityManager,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid(
            'toggle-suspension-employee-'.$employee->getId(),
            $request->request->getString('_token'),
        )) {
            throw $this->createAccessDeniedException();
        }

        $this->assertEmployee($employee);

        $employee->setSuspended(
            !$employee->isSuspended(),
        );

        $entityManager->flush();

        $actor = $this->getActivityActor();

        $activity = $employee->isSuspended()
            ? $this->employeeActivityFactory->suspended(
                actor: $actor,
                employee: $employee,
            )
            : $this->employeeActivityFactory->unsuspended(
                actor: $actor,
                employee: $employee,
            );

        $this->activityLogger->save($activity);

        $this->addFlash(
            'success',
            $employee->isSuspended()
                ? sprintf(
                    'Le compte employé de %s a été suspendu.',
                    $employee->getUsername(),
                )
                : sprintf(
                    'Le compte employé de %s a été réactivé.',
                    $employee->getUsername(),
                ),
        );

        return $this->redirectToRoute(
            'app_back_office_employee_index',
        );
    }

    #[Route(
        '/{id}/delete',
        name: 'delete',
        methods: ['POST'],
    )]
    public function delete(
        User $employee,
        Request $request,
        EntityManagerInterface $entityManager,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid(
            'delete-employee-'.$employee->getId(),
            $request->request->getString('_token'),
        )) {
            throw $this->createAccessDeniedException();
        }

        $this->assertEmployee($employee);

        $username = $employee->getUsername();

        $activity = $this->employeeActivityFactory->deleted(
            actor: $this->getActivityActor(),
            employee: $employee,
        );

        $entityManager->remove($employee);
        $entityManager->flush();

        $this->activityLogger->save($activity);

        $this->addFlash(
            'success',
            sprintf(
                'Le compte employé de %s a été supprimé.',
                $username,
            ),
        );

        return $this->redirectToRoute(
            'app_back_office_employee_index',
        );
    }

    private function assertEmployee(User $user): void
    {
        if (
            !in_array('ROLE_EMPLOYEE', $user->getRoles(), true)
            || in_array('ROLE_ADMIN', $user->getRoles(), true)
        ) {
            throw $this->createAccessDeniedException(
                'Ce compte ne peut pas être géré depuis cet écran.',
            );
        }
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
