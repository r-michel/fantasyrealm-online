<?php

namespace App\Document;

use App\Enum\ActivityActorType;
use App\Enum\ActivityTargetType;
use App\Enum\ActivityType;
use App\Model\Activity;
use App\Repository\ActivityLogRepository;
use DateTimeImmutable;
use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;

#[ODM\Document(
    collection: 'activity_logs',
    repositoryClass: ActivityLogRepository::class,
)]
#[ODM\Index(keys: ['createdAt' => 'desc'])]
#[ODM\Index(keys: ['type' => 'asc', 'createdAt' => 'desc'])]
#[ODM\Index(keys: ['actorId' => 'asc', 'createdAt' => 'desc'])]
#[ODM\Index(
    keys: [
        'targetType' => 'asc',
        'targetId' => 'asc',
        'createdAt' => 'desc',
    ],
)]
class ActivityLog
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field(enumType: ActivityType::class)]
    private ActivityType $type;

    #[ODM\Field(enumType: ActivityActorType::class)]
    private ActivityActorType $actorType;

    #[ODM\Field(nullable: true)]
    private ?string $actorId = null;

    #[ODM\Field(nullable: true)]
    private ?string $actorUsername = null;

    #[ODM\Field(
        enumType: ActivityTargetType::class,
        nullable: true,
    )]
    private ?ActivityTargetType $targetType = null;

    #[ODM\Field(nullable: true)]
    private ?string $targetId = null;

    #[ODM\Field(nullable: true)]
    private ?string $targetName = null;

    #[ODM\Field]
    private string $message;

    /**
     * @var array<string, mixed>
     */
    #[ODM\Field(type: 'hash')]
    private array $details = [];

    #[ODM\Field(type: 'date_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct(
        ActivityType $type,
        ActivityActorType $actorType,
        string $message,
    ) {
        $this->type = $type;
        $this->actorType = $actorType;
        $this->message = $message;
        $this->createdAt = new DateTimeImmutable();
    }

    public static function fromActivity(Activity $activity): self
    {
        $document = new self(
            type: $activity->getType(),
            actorType: $activity->getActorType(),
            message: $activity->getMessage(),
        );

        $document->actorId = $activity->getActorId();
        $document->actorUsername = $activity->getActorUsername();
        $document->targetType = $activity->getTargetType();
        $document->targetId = $activity->getTargetId();
        $document->targetName = $activity->getTargetName();
        $document->details = $activity->getDetails();

        return $document;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getType(): ActivityType
    {
        return $this->type;
    }

    public function getActorType(): ActivityActorType
    {
        return $this->actorType;
    }

    public function getActorId(): ?string
    {
        return $this->actorId;
    }

    public function getActorUsername(): ?string
    {
        return $this->actorUsername;
    }

    public function getTargetType(): ?ActivityTargetType
    {
        return $this->targetType;
    }

    public function getTargetId(): ?string
    {
        return $this->targetId;
    }

    public function getTargetName(): ?string
    {
        return $this->targetName;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
