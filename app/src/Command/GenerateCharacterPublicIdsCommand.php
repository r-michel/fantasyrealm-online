<?php

namespace App\Command;

use App\Repository\CharacterRepository;
use App\Service\CharacterPublicIdGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:characters:generate-public-ids',
    description: 'Génère les identifiants publics manquants des personnages.',
)]
final class GenerateCharacterPublicIdsCommand extends Command
{
    public function __construct(
        private readonly CharacterRepository $characterRepository,
        private readonly CharacterPublicIdGenerator $publicIdGenerator,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $characters = $this->characterRepository->findAll();

        $updatedCount = 0;

        foreach ($characters as $character) {
            if ($character->getPublicId() != null) {
                continue;
            }

            $character->setPublicId(
                $this->publicIdGenerator->generate(
                    $character->getName(),
                ),
            );

            ++$updatedCount;
        }

        $this->entityManager->flush();

        $output->writeln(sprintf(
            '%d identifiant(s) public(s) généré(s).',
            $updatedCount,
        ));

        return Command::SUCCESS;
    }
}
