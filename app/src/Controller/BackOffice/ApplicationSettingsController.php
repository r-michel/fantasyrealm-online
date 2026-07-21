<?php

namespace App\Controller\BackOffice;

use App\Form\ApplicationSettingsType;
use App\Service\ApplicationSettingsProvider;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/back-office/settings', name: 'back_office_settings_')]
#[IsGranted('ROLE_ADMIN')]
class ApplicationSettingsController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        ApplicationSettingsProvider $settingsProvider,
        DocumentManager $documentManager
    ): Response {
        $settings = $settingsProvider->getSettings();

        $form = $this->createForm(
            ApplicationSettingsType::class,
            $settings
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $settings->setUpdatedAt(new \DateTimeImmutable());

            $documentManager->persist($settings);
            $documentManager->flush();

            $this->addFlash(
                'success',
                'Les paramètres de l’application ont été enregistrés.'
            );

            return $this->redirectToRoute(
                'back_office_settings_index'
            );
        }

        return $this->render(
            'back_office/settings/index.html.twig',
            [
                'settingsForm' => $form,
                'settings' => $settings,
            ]
        );
    }
}
