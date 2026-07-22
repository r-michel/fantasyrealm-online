<?php

namespace App\Controller;

use App\Dto\ContactRequest;
use App\Entity\User;
use App\Form\ContactType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ContactController extends AbstractController
{
    public function __construct(
        private readonly string $mailerFromEmail,
        private readonly string $mailerFromName,
    ) {
    }

    #[Route('/contact', name: 'app_contact')]
    public function index(
        Request $request,
        UserRepository $userRepository,
        MailerInterface $mailer,
        #[Autowire('%env(CONTACT_EMAIL)%')]
        string $contactEmail,
    ): Response {
        $contactRequest = new ContactRequest();

        $user = $this->getUser();

        if ($user instanceof User) {
            $contactRequest->email = $user->getEmail();
            $contactRequest->username = $user->getUsername();
        }

        $form = $this->createForm(ContactType::class, $contactRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $existingUser = $userRepository->findOneBy([
                'username' => $contactRequest->username,
            ]);

            if (!$existingUser) {
                $form->get('username')->addError(new \Symfony\Component\Form\FormError(
                    'Ce pseudo ne correspond à aucun compte existant.'
                ));
            } else {
                $email = (new Email())
                    ->from(new Address(
                        $this->mailerFromEmail,
                        $this->mailerFromName
                    ))
                    ->to($contactEmail)
                    ->replyTo($contactRequest->email)
                    ->subject('[FantasyRealm] ' . $contactRequest->subject)
                    ->text(sprintf(
                        "Pseudo : %s\nEmail : %s\n\nMessage :\n%s",
                        $contactRequest->username,
                        $contactRequest->email,
                        $contactRequest->message
                    ));

                $mailer->send($email);

                $this->addFlash('success', 'Votre message a bien été envoyé.');

                return $this->redirectToRoute('app_contact');
            }
        }

        return $this->render('contact/index.html.twig', [
            'contactForm' => $form,
        ]);
    }
}
