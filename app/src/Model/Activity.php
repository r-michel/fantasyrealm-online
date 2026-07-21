<?php

namespace App\Model;

use App\Enum\ActivityActorType;
use App\Enum\ActivityTargetType;
use App\Enum\ActivityType;

final class Activity
{
    private ?string $actorId = null;

    private ?string $actorUsername = null;

    private ?ActivityTargetType $targetType = null;

    private ?string $targetId = null;

    private ?string $targetName = null;

    /**
     * @var array<string, mixed>
     */
    private array $details = [];

    public function __construct(
        private readonly ActivityType $type,
        private readonly ActivityActorType $actorType,
        private readonly string $message,
    ) {
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

    public function actor(
        ?string $id,
        ?string $username,
    ): self {
        $this->actorId = $id;
        $this->actorUsername = $username;

        return $this;
    }

    public function target(
        ActivityTargetType $type,
        string $id,
        string $name,
    ): self {
        $this->targetType = $type;
        $this->targetId = $id;
        $this->targetName = $name;

        return $this;
    }

    /**
     * @param array<string, mixed> $details
     */
    public function details(array $details): self
    {
        $this->details = $details;

        return $this;
    }
}
