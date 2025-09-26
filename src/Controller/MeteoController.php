<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class MeteoController extends AbstractController
{
    private HttpClientInterface $httpClient;
    private TagAwareCacheInterface $cache;

    public function __construct(HttpClientInterface $httpClient, TagAwareCacheInterface $cache)
    {
        $this->httpClient = $httpClient;
        $this->cache = $cache;
    }

    /**
     * Récupère la météo de l'utilisateur connecté 
     * https://api.openweathermap.org/data/2.5/weather?q={city name}&appid={API key}
     */
    #[Route('/api/meteo', name: 'app_meteo', methods: ['GET'])]
    #[IsGranted('ROLE_USER', message: 'Accès refusé, vous devez être connecté.')]
    public function getLocalMeteo(): JsonResponse
    {
        // Récupération de l'utilisateur connecté
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non authentifié.'], Response::HTTP_UNAUTHORIZED);
        }
        // Récupération de la localisation et traitement
        $cityOfUser = $user->getVille();
        $cityName = trim(strtolower($cityOfUser));
        if (empty($cityName)) {
            return new JsonResponse(['error' => 'Données invalides. La ville est requise.'], Response::HTTP_BAD_REQUEST);
        }

        // Clé de cache basée sur la ville
        $cacheKey = 'meteo_' . $cityName;

        // Récupération des données de la météo avec mise en cache
        $meteoData = $this->cache->get($cacheKey, function (ItemInterface $item) use ($cityName) {

            // Suivi de mise en cache
            // echo ("L'élément n'existe pas dans le cache ou a expiré. Requête à l'API.");
            // Durée de vie du cache 60 secondes
            $item->expiresAfter(60);
            // Tag pour invalidation future
            $item->tag(['meteo']);

            // Requête à l'API OpenWeather
            $apiResponse = $this->httpClient->request(
                'GET',
                'https://api.openweathermap.org/data/2.5/weather',
                [
                    'query' => [
                        'q' => $cityName,
                        'appid' => $this->getParameter('openweather_api_key'),
                        'units' => 'metric',
                        'lang' => 'fr'
                    ]
                ]
            );

            // Gestion des erreurs de l'API
            if ($apiResponse->getStatusCode() == 404) {
                return ['error' => 'Ville non trouvée.'];
            }
            if ($apiResponse->getStatusCode() !== 200) {
                return ['error' => 'Erreur lors de la récupération des données météo.'];
            }

            // Traitement de la réponse de l'API
            $result = $apiResponse->toArray();
            return [
                'city' => $result['name'],
                'country' => $result['sys']['country'],
                'description' => $result['weather'][0]['description'],
                'temperature' => $result['main']['temp'],
            ];
        });

        if (empty($meteoData)) {
            return new JsonResponse(['error' => 'Impossible de récupérer les données météo.'], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($meteoData, Response::HTTP_OK, []);
    }

    /**
     * Récupère la météo pour une localisation donnée 
     * https://api.openweathermap.org/data/2.5/weather?q={city name}&appid={API key}
     */
    #[Route('/api/meteo/{localisation}', name: 'app_meteo_localisation', methods: ['GET'])]
    #[IsGranted('ROLE_USER', message: 'Accès refusé, vous devez être connecté.')]
    public function getCityMeteo(string $localisation): JsonResponse
    {
        // Récupération de la localisation et traitement
        $cityName = trim(strtolower($localisation));
        if (empty($cityName)) {
            return new JsonResponse(['error' => 'Données invalides. La ville est requise.'], Response::HTTP_BAD_REQUEST);
        }

        // Clé de cache basée sur la ville
        $cacheKey = 'meteo_' . $cityName;

        // Récupération des données de la météo avec mise en cache
        $meteoData = $this->cache->get($cacheKey, function (ItemInterface $item) use ($cityName) {

            // Suivi de mise en cache
            // echo ("L'élément n'existe pas dans le cache ou a expiré. Requête à l'API.");
            // Durée de vie du cache 60 secondes
            $item->expiresAfter(60);
            // Tag pour invalidation future
            $item->tag(['meteo']);

            // Requête à l'API OpenWeather
            $apiResponse = $this->httpClient->request(
                'GET',
                'https://api.openweathermap.org/data/2.5/weather',
                [
                    'query' => [
                        'q' => $cityName,
                        'appid' => $this->getParameter('openweather_api_key'),
                        'units' => 'metric',
                        'lang' => 'fr'
                    ]
                ]
            );

            // Gestion des erreurs de l'API
            if ($apiResponse->getStatusCode() == 404) {
                return ['error' => 'Ville non trouvée.'];
            }
            if ($apiResponse->getStatusCode() !== 200) {
                return ['error' => 'Erreur lors de la récupération des données météo.'];
            }

            // Traitement de la réponse de l'API
            $result = $apiResponse->toArray();
            return [
                'city' => $result['name'],
                'country' => $result['sys']['country'],
                'description' => $result['weather'][0]['description'],
                'temperature' => $result['main']['temp'],
            ];
        });

        if (empty($meteoData)) {
            return new JsonResponse(['error' => 'Impossible de récupérer les données météo.'], Response::HTTP_BAD_REQUEST);
        }


        return new JsonResponse($meteoData, Response::HTTP_OK, []);
    }
}
