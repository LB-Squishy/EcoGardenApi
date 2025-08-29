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
        $conseilData = [
            ['Profiter de l’hiver pour organiser vos cultures et commander vos graines.', [1, 2, 3]],
            ['Arrosez vos plantes le matin pour minimiser l\'évaporation de l\'eau et maximiser l\'absorption par les racines.', [1, 2, 3]],
            ['Utilisez un paillis pour conserver l\'humidité du sol et réduire la croissance des mauvaises herbes.', [4, 5, 6]],
            ['Fertilisez vos plantes avec des engrais naturels pour favoriser leur croissance.', [7, 8, 9]],
            ['Récoltez vos légumes au bon moment pour profiter de leur saveur optimale.', [10, 11, 12]],
            ['Planifiez la rotation de vos cultures pour maintenir la santé du sol.', [1, 4, 8, 12]],
            ['Surveillez régulièrement vos plantes pour détecter les signes de maladies ou de parasites.', [3, 6, 9, 11]],
            ['Compostez vos déchets de jardin pour créer un sol riche en nutriments.', [5, 7, 10]],
            ['Utilisez des méthodes de jardinage durables pour préserver l\'environnement.', [2, 4, 6, 8, 10, 12]],
            ['Profitez de votre jardin pour vous détendre et vous reconnecter avec la nature.', [1, 3, 5, 7, 9, 11]],
        ];

        foreach ($conseilData as [$texte, $moisArray]) {
            $conseil = new Conseil();

            $createdAt = new \DateTimeImmutable(sprintf('2025-%02d-%02d %02d:%02d:%02d', rand(1, 12), rand(1, 28), rand(0, 23), rand(0, 59), rand(0, 59)));
            $updatedAt = $createdAt->modify('+' . rand(0, 10) . ' days +' . rand(0, 23) . ' hours +' . rand(0, 59) . ' minutes');

            $conseil
                ->setDescription($texte)
                ->setCreatedAt($createdAt)
                ->setUpdatedAt($updatedAt);

            foreach ($moisArray as $moisNumber) {
                $mois = new ConseilMois();
                $mois
                    ->setMois($moisNumber)
                    ->setConseil($conseil);
                $conseil->addMois($mois);
                $manager->persist($mois);
            }

            $manager->persist($conseil);
        }

        $manager->flush();
    }
}
