<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Entity\Session;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class SessionFixture extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $courseSymfony = $manager->getRepository(Course::class)->findOneBy(['title' => 'Learn Symfony']);
        $courseAndroid = $manager->getRepository(Course::class)->findOneBy(['title' => 'Android Development']);

        $session1 = new Session();
        $session1->setCourse($courseSymfony);
        $session1->setDate(new \DateTime('2025-04-27'));
        $session1->setCapacity(2);
        $session1->setStartTime(new \DateTime('09:00:00'));
        $session1->setEndTime(new \DateTime('12:00:00'));
        $manager->persist($session1);

        $session2 = new Session();
        $session2->setCourse($courseSymfony);
        $session2->setDate(new \DateTime('2025-05-02'));
        $session2->setCapacity(10);
        $session2->setStartTime(new \DateTime('14:00:00'));
        $session2->setEndTime(new \DateTime('17:00:00'));
        $manager->persist($session2);

        $session3 = new Session();
        $session3->setCourse($courseAndroid);
        $session3->setDate(new \DateTime('2025-05-05'));
        $session3->setCapacity(10);
        $session3->setStartTime(new \DateTime('10:00:00'));
        $session3->setEndTime(new \DateTime('13:00:00'));
        $manager->persist($session3);

        $session4 = new Session();
        $session4->setCourse($courseAndroid);
        $session4->setDate(new \DateTime('2025-05-10'));
        $session4->setCapacity(20);
        $session4->setStartTime(new \DateTime('15:00:00'));
        $session4->setEndTime(new \DateTime('18:00:00'));
        $manager->persist($session4);

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['g1'];
    }
}
