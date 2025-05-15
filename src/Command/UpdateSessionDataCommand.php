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
    description: 'Updates session data to ensure consistency with the new entity structure',
)]
class UpdateSessionDataCommand extends Command
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this->setDescription('Updates session data to ensure consistency with the new entity structure');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Updating Session Data');

        // Get all sessions
        $sessions = $this->entityManager->getRepository(Session::class)->findAll();
        $io->info(sprintf('Found %d sessions to process', count($sessions)));

        $updatedCount = 0;
        $errorCount = 0;

        foreach ($sessions as $session) {
            try {
                // Check if the session has valid start and end dates
                if ($session->getStartDate() === null) {
                    $io->warning(sprintf('Session #%d has no start date, setting to today', $session->getId()));
                    $session->setStartDate(new \DateTime());
                }

                if ($session->getEndDate() === null) {
                    $io->warning(sprintf('Session #%d has no end date, setting to start date', $session->getId()));
                    $session->setEndDate(clone $session->getStartDate());
                }

                // Check if the session has a valid time
                if ($session->getTime() === null) {
                    $io->warning(sprintf('Session #%d has no time, setting to 09:00', $session->getId()));
                    $time = new \DateTime();
                    $time->setTime(9, 0, 0);
                    $session->setTime($time);
                }

                // Ensure multi-day sessions have consistent data
                if ($session->getStartDate() != $session->getEndDate()) {
                    $io->info(sprintf(
                        'Session #%d is a multi-day session (%s to %s)',
                        $session->getId(),
                        $session->getStartDate()->format('Y-m-d'),
                        $session->getEndDate()->format('Y-m-d')
                    ));
                }

                // Ensure capacity is set
                if ($session->getCapacity() === null) {
                    $io->warning(sprintf('Session #%d has no capacity, setting to 20', $session->getId()));
                    $session->setCapacity(20);
                }

                // Ensure location is set
                if ($session->getLocation() === null || $session->getLocation() === '') {
                    $io->warning(sprintf('Session #%d has no location, setting to "Classroom"', $session->getId()));
                    $session->setLocation('Classroom');
                }

                // Ensure type is set
                if ($session->getType() === null || $session->getType() === '') {
                    $io->warning(sprintf('Session #%d has no type, setting to "regular"', $session->getId()));
                    $session->setType('regular');
                }

                // Ensure description is set
                if ($session->getDescription() === null || $session->getDescription() === '') {
                    $io->warning(sprintf('Session #%d has no description, setting to default', $session->getId()));
                    $session->setDescription('Session for ' . $session->getCourse()->getTitle());
                }

                // Ensure createdAt is set
                if ($session->getCreatedAt() === null) {
                    $io->warning(sprintf('Session #%d has no createdAt, setting to now', $session->getId()));
                    $session->setCreatedAt(new \DateTime());
                }

                $updatedCount++;
            } catch (\Exception $e) {
                $io->error(sprintf('Error updating session #%d: %s', $session->getId(), $e->getMessage()));
                $errorCount++;
            }
        }

        // Flush changes to database
        $this->entityManager->flush();

        $io->success(sprintf('Updated %d sessions successfully. Encountered %d errors.', $updatedCount, $errorCount));

        return Command::SUCCESS;
    }
}
