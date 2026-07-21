<?php

namespace App\Service;

use App\Document\ActivityLog;
use App\Model\Activity;
use Doctrine\ODM\MongoDB\DocumentManager;

final readonly class ActivityLogger
{
    public function __construct(
        private DocumentManager $documentManager,
    ) {
    }

    public function save(Activity $activity): ActivityLog
    {
        $document = ActivityLog::fromActivity($activity);

        $this->documentManager->persist($document);
        $this->documentManager->flush();

        return $document;
    }
}
