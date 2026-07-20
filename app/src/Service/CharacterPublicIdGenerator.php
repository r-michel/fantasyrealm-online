<?php

namespace App\Service;

use Symfony\Component\String\Slugger\SluggerInterface;

final class CharacterPublicIdGenerator
{
    public function __construct(
        private readonly SluggerInterface $slugger,
    ) {
    }

    public function generate(string $name): string
    {
        $slug = $this->slugger
            ->slug($name)
            ->lower()
            ->toString();

        return sprintf(
            '%s-%s',
            $slug,
            bin2hex(random_bytes(4)),
        );
    }
}
