<?php

namespace App\Twig;

use App\Document\ApplicationSettings;
use App\Service\ApplicationSettingsProvider;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ApplicationSettingsExtension extends AbstractExtension
{
    public function __construct(
        private readonly ApplicationSettingsProvider $settingsProvider
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'application_settings',
                [$this, 'getApplicationSettings']
            ),
        ];
    }

    public function getApplicationSettings(): ApplicationSettings
    {
        return $this->settingsProvider->getSettings();
    }
}
