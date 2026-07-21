<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class MustUpdatePasswordSubscriber implements EventSubscriberInterface
{
    private const ALLOWED_ROUTES = [
        'app_update_password',
        'app_logout',
    ];

    public function __construct(
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', 10],
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->getString('_route');

        if (in_array($route, self::ALLOWED_ROUTES, true)) {
            return;
        }

        $user = $this->security->getUser();

        if (
            !$user instanceof User
            || !$user->isMustUpdatePassword()
        ) {
            return;
        }

        $event->setController(
            fn (): RedirectResponse => new RedirectResponse(
                $this->urlGenerator->generate('app_update_password'),
            ),
        );
    }
}
