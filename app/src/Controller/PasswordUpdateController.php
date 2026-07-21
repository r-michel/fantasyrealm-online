<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class PasswordUpdateController extends AbstractController
{
    #[Route(
        '/account/update-password',
        name: 'app_update_password',
        methods: ['GET', 'POST'],
    )]
    public function updatePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createFormBuilder()
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'Nouveau mot de passe',
                    'attr' => [
                        'autocomplete' => 'new-password',
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'attr' => [
                        'autocomplete' => 'new-password',
                    ],
                ],
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'mapped' => false,
                'constraints' => [
                    new NotBlank(
                        message: 'Veuillez saisir un nouveau mot de passe.',
                    ),
                    new Length(
                        min: 12,
                        minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
                    ),
                ],
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form
                ->get('plainPassword')
                ->getData();

            $user
                ->setPassword(
                    $passwordHasher->hashPassword(
                        $user,
                        $plainPassword,
                    ),
                )
                ->setMustUpdatePassword(false);

            $entityManager->flush();

            $this->addFlash(
                'success',
                'Votre mot de passe a bien été modifié.',
            );

            if ($this->isGranted('ROLE_EMPLOYEE')) {
                return $this->redirectToRoute(
                    'app_back_office_dashboard_index',
                );
            }

            return $this->redirectToRoute('app_account');
        }

        return $this->render(
            'reset_password/reset.html.twig',
            [
                'resetForm' => $form,
            ],
        );
    }
}
