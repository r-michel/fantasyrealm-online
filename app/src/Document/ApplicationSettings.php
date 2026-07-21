<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;

#[ODM\Document(collection: 'application_settings')]
class ApplicationSettings
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field(type: 'bool')]
    private bool $announcementEnabled = false;

    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $announcementMessage = null;

    #[ODM\Field(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function isAnnouncementEnabled(): bool
    {
        return $this->announcementEnabled;
    }

    public function setAnnouncementEnabled(bool $announcementEnabled): self
    {
        $this->announcementEnabled = $announcementEnabled;

        return $this;
    }

    public function getAnnouncementMessage(): ?string
    {
        return $this->announcementMessage;
    }

    public function setAnnouncementMessage(?string $announcementMessage): self
    {
        $this->announcementMessage = $announcementMessage !== null
            ? trim($announcementMessage)
            : null;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
