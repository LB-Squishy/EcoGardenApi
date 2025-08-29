<?php

namespace App\DataFixtures;

use App\Entity\Conseil;
use App\Entity\ConseilMois;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // $conseil1 = new Conseil();
        // $conseil1
        //     ->setDescription('Arrosez vos plantes le matin pour minimiser l\'évaporation de l\'eau et maximiser l\'absorption par les racines.')
        //     ->setMois([1, 2, 3])
        //     ->setCreatedAt(new \DateTimeImmutable('2023-03-01'))
        //     ->setUpdatedAt(new \DateTimeImmutable('2023-07-03'));
        // $manager->persist($conseil1);

        // $conseil2 = new Conseil();
        // $conseil2
        //     ->setDescription('Utilisez un paillis pour conserver l\'humidité du sol et réduire la croissance des mauvaises herbes.')
        //     ->setMois([4, 5, 6])
        //     ->setCreatedAt(new \DateTimeImmutable('2024-10-06'))
        //     ->setUpdatedAt(new \DateTimeImmutable('2024-10-06'));
        // $manager->persist($conseil2);

        // $conseil3 = new Conseil();
        // $conseil3
        //     ->setDescription('Fertilisez vos plantes avec des engrais naturels pour favoriser leur croissance.')
        //     ->setMois([7, 8, 9])
        //     ->setCreatedAt(new \DateTimeImmutable('2025-07-26'))
        //     ->setUpdatedAt(new \DateTimeImmutable('2025-07-26'));
        // $manager->persist($conseil3);

        // $conseil4 = new Conseil();
        // $conseil4
        //     ->setDescription('Récoltez vos légumes au bon moment pour profiter de leur saveur optimale.')
        //     ->setMois([10, 11, 12])
        //     ->setCreatedAt(new \DateTimeImmutable('2025-01-11'))
        //     ->setUpdatedAt(new \DateTimeImmutable('2025-12-20'));
        // $manager->persist($conseil4);
        // $conseil5 = new Conseil();
        // $conseil5
        //     ->setDescription('Planifiez la rotation de vos cultures pour maintenir la santé du sol.')
        //     ->setMois([1, 4, 8, 12])
        //     ->setCreatedAt(new \DateTimeImmutable('2025-01-11'))
        //     ->setUpdatedAt(new \DateTimeImmutable('2025-12-20'));
        // $manager->persist($conseil5);

        // $manager->flush();
    }
}
