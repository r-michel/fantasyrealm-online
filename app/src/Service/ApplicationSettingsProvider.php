<?php

namespace App\Service;

use App\Document\ApplicationSettings;
use Doctrine\ODM\MongoDB\DocumentManager;

class ApplicationSettingsProvider
{
    private ?ApplicationSettings $settings = null;

    public function __construct(
        private readonly DocumentManager $documentManager
    ) {
    }

    public function getSettings(): ApplicationSettings
    {
        if ($this->settings instanceof ApplicationSettings) {
            return $this->settings;
        }

        $settings = $this->documentManager
            ->getRepository(ApplicationSettings::class)
            ->findOneBy([]);

        $this->settings = $settings ?? new ApplicationSettings();

        return $this->settings;
    }
}
