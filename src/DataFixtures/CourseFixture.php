<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Course;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CourseFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {

        $categoryWebDev = $manager->getRepository(Category::class)->findOneBy(['name' => 'Web Development']);
        $categoryMobileDev = $manager->getRepository(Category::class)->findOneBy(['name' => 'Mobile Development']);
        $categoryDataScience = $manager->getRepository(Category::class)->findOneBy(['name' => 'Data Science']);
        $categoryDesign = $manager->getRepository(Category::class)->findOneBy(['name' => 'Design']);

        $course1 = new Course();
        $course1->setTitle('Learn Symfony');
        $course1->setDescription('Master the Symfony framework and build powerful web applications.');
        $course1->setImage('symfony.png');
        $course1->setDuration(3);
        $course1->setCategory($categoryWebDev);
        $manager->persist($course1);

        $course2 = new Course();
        $course2->setTitle('Android Development');
        $course2->setDescription('Learn how to build Android apps from scratch.');
        $course2->setImage('android.png');
        $course2->setDuration(4);
        $course2->setCategory($categoryMobileDev);
        $manager->persist($course2);

        $course3 = new Course();
        $course3->setTitle('Data Science for Beginners');
        $course3->setDescription('An introductory course to Data Science.');
        $course3->setImage('datascience.png');
        $course3->setDuration(2);
        $course3->setCategory($categoryDataScience);
        $manager->persist($course3);

        $course4 = new Course();
        $course4->setTitle('UX/UI Design Basics');
        $course4->setDescription('Learn the fundamentals of UX/UI Design.');
        $course4->setImage('design.png');
        $course4->setDuration(3);
        $course4->setCategory($categoryDesign);
        $manager->persist($course4);

        $manager->flush();
    }
}
