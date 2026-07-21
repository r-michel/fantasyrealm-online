<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SuspendedUserSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', 20],
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->getString('_route');

        if (
            $route === 'app_login'
            || $route === 'app_logout'
        ) {
            return;
        }

        $user = $this->security->getUser();

        if (
            !$user instanceof User
            || !$user->isSuspended()
        ) {
            return;
        }

        $event->setController(function (): RedirectResponse {
            $this->security->logout(false);

            return new RedirectResponse(
                $this->urlGenerator->generate(
                    'app_login',
                    ['account_suspended' => 1],
                ),
            );
        });
    }
}
