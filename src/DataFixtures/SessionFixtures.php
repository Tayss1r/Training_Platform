<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Entity\Session;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SessionFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $courses = $manager->getRepository(Course::class)->findAll();

        if (empty($courses)) {
            throw new \Exception('No courses found. Please load CourseFixtures first.');
        }

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

        // Create sessions for each course
        foreach ($courses as $index => $course) {
            // Create 2-4 sessions per course
            $sessionCount = rand(2, 4);

            for ($i = 0; $i < $sessionCount; $i++) {
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
                } else {
                    // Single day session
                    $session->setEndDate(clone $startDate);
                }

                // Set time (between 8:00 and 18:00)
                $hour = rand(8, 18);
                $minute = rand(0, 1) * 30; // Either 0 or 30 minutes
                $time = new \DateTime();
                $time->setTime($hour, $minute, 0);
                $session->setTime($time);

                $manager->persist($session);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CourseFixtures::class,
        ];
    }
}
