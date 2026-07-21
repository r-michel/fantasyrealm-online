<?php

namespace App\Factory\Activity;

use App\Entity\Comment;
use App\Entity\User;
use App\Enum\ActivityTargetType;
use App\Enum\ActivityType;
use App\Model\Activity;

final class CommentActivityFactory extends AbstractActivityFactory
{
    public function approved(
        User $actor,
        Comment $comment,
    ): Activity {
        $commentId = (string) $comment->getId();

        return $this->createActivity(
            type: ActivityType::COMMENT_APPROVED,
            actor: $actor,
            targetType: ActivityTargetType::COMMENT,
            targetId: $commentId,
            targetName: sprintf(
                'Commentaire #%s',
                $commentId,
            ),
            message: sprintf(
                '%s a approuvé le commentaire #%s.',
                $actor->getUsername(),
                $commentId,
            ),
            details: [
                'status' => 'approved',
            ],
        );
    }

    public function rejected(
        User $actor,
        Comment $comment,
        ?string $reason = null,
    ): Activity {
        $commentId = (string) $comment->getId();

        $details = [
            'status' => 'rejected',
        ];

        if ($reason !== null && $reason !== '') {
            $details['reason'] = $reason;
        }

        return $this->createActivity(
            type: ActivityType::COMMENT_REJECTED,
            actor: $actor,
            targetType: ActivityTargetType::COMMENT,
            targetId: $commentId,
            targetName: sprintf(
                'Commentaire #%s',
                $commentId,
            ),
            message: sprintf(
                '%s a rejeté le commentaire #%s.',
                $actor->getUsername(),
                $commentId,
            ),
            details: $details,
        );
    }
}
