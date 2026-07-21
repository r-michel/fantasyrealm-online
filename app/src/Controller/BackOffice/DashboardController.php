<?php

namespace App\Controller\BackOffice;

use App\Entity\Character;
use App\Entity\Comment;
use App\Repository\CharacterRepository;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;


#[Route('/back-office', name: 'app_back_office_dashboard_')]
#[IsGranted('ROLE_EMPLOYEE')]
final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly string $mailerFromEmail,
        private readonly string $mailerFromName,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        CharacterRepository $characterRepository,
        CommentRepository $commentRepository,
    ): Response {
        $pendingCharacters = $characterRepository->findBy(
            ['authorized' => false],
            ['createdAt' => 'ASC'],
        );

        $pendingComments = $commentRepository->findBy(
            ['published' => false],
            ['createdAt' => 'ASC'],
        );

        return $this->render('back_office/dashboard/index.html.twig', [
            'pendingCharacters' => $pendingCharacters,
            'pendingComments' => $pendingComments,
            'pendingCount' => count($pendingCharacters) + count($pendingComments),
        ]);
    }

    #[Route(
        '/characters/{publicId}/approve',
        name: 'character_approve',
        methods: ['POST'],
    )]
    public function approveCharacter(
        #[MapEntity(mapping: ['publicId' => 'publicId'])]
        Character $character,
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid(
            'approve-character-'.$character->getPublicId(),
            $request->request->getString('_token'),
        )) {
            throw $this->createAccessDeniedException(
                'Le jeton CSRF est invalide.',
            );
        }

        if ($character->isAuthorized()) {
            $this->addFlash(
                'warning',
                'Ce personnage a déjà été approuvé.',
            );

            return $this->redirectToRoute('app_back_office_dashboard_index');
        }

        $character->setAuthorized(true);

        $entityManager->flush();

        $email = (new TemplatedEmail())
            ->from(new Address(
                $this->mailerFromEmail,
                $this->mailerFromName
            ))
            ->to($character->getOwner()->getEmail())
            ->subject('Le nom de votre personnage a été approuvé')
            ->htmlTemplate('emails/character/approved.html.twig')
            ->context([
                'character' => $character,
                'owner' => $character->getOwner(),
            ]);

        try {
            $mailer->send($email);

            $this->addFlash(
                'success',
                sprintf(
                    'Le personnage « %s » a été approuvé.',
                    $character->getName(),
                ),
            );
        } catch (TransportExceptionInterface) {
            $this->addFlash(
                'warning',
                sprintf(
                    'Le personnage « %s » a été approuvé, mais le mail n’a pas pu être envoyé.',
                    $character->getName(),
                ),
            );
        }

        return $this->redirectToRoute('app_back_office_dashboard_index');
    }

    #[Route(
        '/characters/{publicId}/reject',
        name: 'character_reject',
        methods: ['POST'],
    )]
    public function rejectCharacter(
        #[MapEntity(mapping: ['publicId' => 'publicId'])]
        Character $character,
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid(
            'reject-character-'.$character->getPublicId(),
            $request->request->getString('_token'),
        )) {
            throw $this->createAccessDeniedException(
                'Le jeton CSRF est invalide.',
            );
        }

        $reason = trim($request->request->getString('reason'));

        if ($reason === '') {
            $this->addFlash(
                'error',
                'Un motif est obligatoire pour refuser un personnage.',
            );

            return $this->redirectToRoute('app_back_office_dashboard_index');
        }

        $characterName = $character->getName();
        $owner = $character->getOwner();
        $ownerEmail = $owner->getEmail();

        $entityManager->remove($character);
        $entityManager->flush();

        $email = (new TemplatedEmail())
            ->from(new Address(
                $this->mailerFromEmail,
                $this->mailerFromName
            ))
            ->to($ownerEmail)
            ->subject('Le nom de votre personnage a été refusé')
            ->htmlTemplate('emails/character/rejected.html.twig')
            ->context([
                'owner' => $owner,
                'characterName' => $characterName,
                'reason' => $reason,
            ]);

        try {
            $mailer->send($email);

            $this->addFlash(
                'success',
                sprintf(
                    'Le personnage « %s » a été refusé et supprimé.',
                    $characterName,
                ),
            );
        } catch (TransportExceptionInterface) {
            $this->addFlash(
                'warning',
                sprintf(
                    'Le personnage « %s » a été supprimé, mais le mail n’a pas pu être envoyé.',
                    $characterName,
                ),
            );
        }

        return $this->redirectToRoute('app_back_office_dashboard_index');
    }

    #[Route(
        '/comments/{id}/approve',
        name: 'comment_approve',
        methods: ['POST'],
    )]
    public function approveComment(
        Comment $comment,
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid(
            'approve-comment-'.$comment->getId(),
            $request->request->getString('_token'),
        )) {
            throw $this->createAccessDeniedException(
                'Le jeton CSRF est invalide.',
            );
        }

        if ($comment->isPublished()) {
            $this->addFlash(
                'warning',
                'Cet avis a déjà été approuvé.',
            );

            return $this->redirectToRoute('app_back_office_dashboard_index');
        }

        $comment->setPublished(true);

        $entityManager->flush();

        $email = (new TemplatedEmail())
            ->from(new Address(
                $this->mailerFromEmail,
                $this->mailerFromName
            ))
            ->to($comment->getOwner()->getEmail())
            ->subject('Votre avis a été approuvé')
            ->htmlTemplate('emails/comment/approved.html.twig')
            ->context([
                'comment' => $comment,
                'owner' => $comment->getOwner(),
                'character' => $comment->getOnCharacter(),
            ]);

        try {
            $mailer->send($email);

            $this->addFlash(
                'success',
                'L’avis a été approuvé.',
            );
        } catch (TransportExceptionInterface) {
            $this->addFlash(
                'warning',
                'L’avis a été approuvé, mais le mail n’a pas pu être envoyé.',
            );
        }

        return $this->redirectToRoute('app_back_office_dashboard_index');
    }

    #[Route(
        '/comments/{id}/reject',
        name: 'comment_reject',
        methods: ['POST'],
    )]
    public function rejectComment(
        Comment $comment,
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid(
            'reject-comment-'.$comment->getId(),
            $request->request->getString('_token'),
        )) {
            throw $this->createAccessDeniedException(
                'Le jeton CSRF est invalide.',
            );
        }

        $owner = $comment->getOwner();
        $ownerEmail = $owner->getEmail();
        $characterName = $comment->getOnCharacter()->getName();

        $entityManager->remove($comment);
        $entityManager->flush();

        $email = (new TemplatedEmail())
            ->from(new Address(
                $this->mailerFromEmail,
                $this->mailerFromName
            ))
            ->to($ownerEmail)
            ->subject('Votre avis a été refusé')
            ->htmlTemplate('emails/comment/rejected.html.twig')
            ->context([
                'owner' => $owner,
                'characterName' => $characterName,
            ]);

        try {
            $mailer->send($email);

            $this->addFlash(
                'success',
                'L’avis a été refusé et supprimé.',
            );
        } catch (TransportExceptionInterface) {
            $this->addFlash(
                'warning',
                'L’avis a été supprimé, mais le mail n’a pas pu être envoyé.',
            );
        }

        return $this->redirectToRoute('app_back_office_dashboard_index');
    }
}
