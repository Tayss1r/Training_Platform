<?php

namespace App\Command;

use App\Entity\Session;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-session-data',
    description: 'Updates session data to ensure consistency',
)]
class UpdateSessionDataCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command updates session data to ensure consistency between start and end dates.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Updating Session Data');

        $sessionRepository = $this->entityManager->getRepository(Session::class);
        $sessions = $sessionRepository->findAll();

        $updatedCount = 0;

        foreach ($sessions as $session) {
            $startDate = $session->getStartDate();
            $endDate = $session->getEndDate();

            // Ensure end date is not before start date
            if ($endDate < $startDate) {
                $session->setEndDate($startDate);
                $updatedCount++;
                $io->text(sprintf('Updated session #%d: End date was before start date', $session->getId()));
            }

            // Add any other data consistency checks here
        }

        if ($updatedCount > 0) {
            $this->entityManager->flush();
            $io->success(sprintf('Successfully updated %d sessions', $updatedCount));
        } else {
            $io->info('No sessions needed updating');
        }

        return Command::SUCCESS;
    }
}
