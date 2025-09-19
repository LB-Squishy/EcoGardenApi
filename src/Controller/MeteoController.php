<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class MeteoController extends AbstractController
{
    /**
     * Récupère la météo de l'utilisateur connecté
     */
    #[Route('/meteo', name: 'app_meteo', methods: ['GET'])]
    #[IsGranted('ROLE_USER', message: 'Accès refusé, vous devez être connecté.')]
    public function getLocalMeteo(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/MeteoController.php',
        ]);
    }

    /**
     * Récupère la météo pour une ville donnée
     */
    #[Route('/meteo/{ville}', name: 'app_meteo_ville', methods: ['GET'])]
    #[IsGranted('ROLE_USER', message: 'Accès refusé, vous devez être connecté.')]
    public function getCityMeteo(string $ville): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/MeteoController.php',
        ]);
    }
}
