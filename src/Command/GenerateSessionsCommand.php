<?php

namespace App\Command;

use App\Entity\Course;
use App\Entity\Session;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-sessions',
    description: 'Generates sample session data for existing courses',
)]
class GenerateSessionsCommand extends Command
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Generates sample session data for existing courses')
            ->addOption('count', 'c', InputOption::VALUE_REQUIRED, 'Number of sessions to generate per course', 3);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Generating Sample Session Data');

        // Get the number of sessions to generate per course
        $sessionsPerCourse = (int) $input->getOption('count');
        if ($sessionsPerCourse < 1) {
            $sessionsPerCourse = 3;
        }

        // Get all courses
        $courses = $this->entityManager->getRepository(Course::class)->findAll();
        $courseCount = count($courses);
        
        if ($courseCount === 0) {
            $io->error('No courses found in the database. Please add courses first.');
            return Command::FAILURE;
        }
        
        $io->info(sprintf('Found %d courses. Generating %d sessions per course.', $courseCount, $sessionsPerCourse));

        // Sample locations
        $locations = [
            'Main Campus - Room 101',
            'Main Campus - Room 202',
            'Main Campus - Computer Lab',
            'Downtown Center - Conference Room',
            'Downtown Center - Training Room',
            'Virtual Classroom',
            'Online Meeting',
            'Workshop Space'
        ];
        
        // Sample session types
        $types = [
            'regular',
            'intensive',
            'workshop',
            'seminar',
            'online',
            'hybrid'
        ];

        $totalGenerated = 0;

        // Create sessions for each course
        foreach ($courses as $course) {
            $io->section(sprintf('Generating sessions for course: %s', $course->getTitle()));
            
            for ($i = 0; $i < $sessionsPerCourse; $i++) {
                $session = new Session();
                $session->setCourse($course);
                
                // Set capacity between 10 and 30
                $session->setCapacity(rand(10, 30));
                
                // Set random location
                $session->setLocation($locations[array_rand($locations)]);
                
                // Set random type
                $session->setType($types[array_rand($types)]);
                
                // Set description
                $session->setDescription('Session ' . ($i + 1) . ' for ' . $course->getTitle());
                
                // Set created at
                $createdAt = new \DateTime();
                $createdAt->modify('-' . rand(1, 30) . ' days');
                $session->setCreatedAt($createdAt);
                
                // Set start date (between now and 3 months in the future)
                $startDate = new \DateTime();
                $startDate->modify('+' . rand(1, 90) . ' days');
                $session->setStartDate($startDate);
                
                // Decide if this is a multi-day session (20% chance)
                $isMultiDay = (rand(1, 100) <= 20);
                
                if ($isMultiDay) {
                    // Set end date 1-5 days after start date
                    $endDate = clone $startDate;
                    $endDate->modify('+' . rand(1, 5) . ' days');
                    $session->setEndDate($endDate);
                    $io->text(sprintf('  - Created multi-day session (%s to %s)', 
                        $startDate->format('Y-m-d'), 
                        $endDate->format('Y-m-d')
                    ));
                } else {
                    // Single day session
                    $session->setEndDate(clone $startDate);
                    $io->text(sprintf('  - Created single-day session (%s)', $startDate->format('Y-m-d')));
                }
                
                // Set time (between 8:00 and 18:00)
                $hour = rand(8, 18);
                $minute = rand(0, 1) * 30; // Either 0 or 30 minutes
                $time = new \DateTime();
                $time->setTime($hour, $minute, 0);
                $session->setTime($time);
                
                $this->entityManager->persist($session);
                $totalGenerated++;
            }
        }
        
        $this->entityManager->flush();
        
        $io->success(sprintf('Successfully generated %d new sessions for %d courses.', $totalGenerated, $courseCount));

        return Command::SUCCESS;
    }
}
