<?php

namespace App\Controller;

use App\Entity\Conseil;
use App\Entity\ConseilMois;
use App\Repository\ConseilRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

final class ConseilController extends AbstractController
{
    private ConseilRepository $conseilRepository;
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;

    public function __construct(ConseilRepository $conseilRepository, EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        $this->conseilRepository = $conseilRepository;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }

    /**
     * Récupère les conseils du mois en cours
     */
    #[Route('/api/conseils', name: 'addConseilCurrentMonth', methods: ['GET'])]
    #[IsGranted('ROLE_USER', message: 'Accès refusé, vous devez être connecté.')]
    public function getConseilsCurrentMonth(): JsonResponse
    {
        // Récupération du mois courant
        $currentMonth = (int) date('n');

        // Récupération des conseils pour le mois courant
        $conseils = $this->conseilRepository->findByMonth($currentMonth);
        if (empty($conseils)) {
            return new JsonResponse(['error' => 'Aucun conseil pour le mois en cours'], Response::HTTP_NOT_FOUND);
        }

        // Sérialisation des conseils avec le groupe 'conseil:read'
        $jsonConseils = $this->serializer->serialize($conseils, 'json', ['groups' => 'conseil:read']);

        return new JsonResponse($jsonConseils, Response::HTTP_OK, [], true);
    }

    /**
     * Récupère les conseils d'un mois en particulier
     */
    #[Route('/api/conseils/{mois}', name: 'addConseilByMonth', methods: ['GET'])]
    #[IsGranted('ROLE_USER', message: 'Accès refusé, vous devez être connecté.')]
    public function getConseilsByMonth(int $mois): JsonResponse
    {
        // Validation du mois
        if ($mois < 1 || $mois > 12) {
            return new JsonResponse(['error' => 'Mois invalide: ' . $mois . ' Saisir un mois entre 1 et 12'], Response::HTTP_BAD_REQUEST);
        }

        // Récupération des conseils pour le mois spécifié
        $conseils = $this->conseilRepository->findByMonth($mois);
        if (empty($conseils)) {
            return new JsonResponse(['error' => 'Aucun conseil pour le mois demandé: ' . $mois], Response::HTTP_NOT_FOUND);
        }

        // Sérialisation des conseils avec le groupe 'conseil:read'
        $jsonConseils = $this->serializer->serialize($conseils, 'json', ['groups' => 'conseil:read']);

        return new JsonResponse($jsonConseils, Response::HTTP_OK, [], true);
    }

    /**
     * Ajouter un conseil
     */
    #[Route('/api/conseil', name: 'app_conseil_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Accès refusé, vous devez être administrateur.')]
    public function postConseil(Request $request): JsonResponse
    {
        // Récupération des données
        $data = json_decode($request->getContent(), true);
        if (!isset($data['description']) || !isset($data['mois']) || !is_array($data['mois'])) {
            return new JsonResponse(['error' => 'Données invalides. Description et mois (array) requis.'], Response::HTTP_BAD_REQUEST);
        }

        // Création du conseil
        $conseil = new Conseil();
        $conseil->setDescription($data['description']);

        // Validation et ajout des mois
        foreach ($data['mois'] as $mois) {
            if ($mois < 1 || $mois > 12) {
                return new JsonResponse(['error' => 'Mois invalide: ' . $mois . ' Saisir un mois entre 1 et 12'], Response::HTTP_BAD_REQUEST);
            }
            $conseilMois = new ConseilMois();
            $conseilMois->setMois($mois);
            $conseil->addMois($conseilMois);
        }

        // Persistance du conseil et des mois associés
        $this->entityManager->persist($conseil);
        $this->entityManager->flush();

        // Préparation des données du conseil ajouté
        $ResponseData =
            [
                'id' => $conseil->getId(),
                'description' => $conseil->getDescription(),
                'mois' => $data['mois']
            ];

        // Préparation de la réponse
        $response =
            [
                'message' => 'Conseil ajouté avec succès',
                'conseil' => $ResponseData
            ];

        return new JsonResponse($response, Response::HTTP_CREATED);
    }

    /**
     * Mettre à jour un conseil
     */
    #[Route('/api/conseil/{id}', name: 'editConseil', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Accès refusé, vous devez être administrateur.')]
    public function putConseil(int $id, Request $request): JsonResponse
    {
        // Récupération du conseil à mettre à jour
        $conseil = $this->conseilRepository->find($id);
        if (!$conseil) {
            return new JsonResponse(['error' => 'Conseil non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Récupération des données
        $data = json_decode($request->getContent(), true);
        if (empty($data)) {
            return new JsonResponse(['error' => 'Données absentes'], Response::HTTP_BAD_REQUEST);
        }

        // Mise à jour de la description si fournie
        if (isset($data['description'])) {
            if (empty($data['description'])) {
                return new JsonResponse(['error' => 'La description ne peut pas être vide'], Response::HTTP_BAD_REQUEST);
            }
            $conseil->setDescription($data['description']);
        }

        // Validation et mise à jour des mois si fournis
        if (isset($data['mois']) && is_array($data['mois'])) {
            if (empty($data['mois'])) {
                return new JsonResponse(['error' => 'Le tableau des mois (array) ne peut pas être vide'], Response::HTTP_BAD_REQUEST);
            }
            // Supprimer les mois existants
            foreach ($conseil->getMois() as $conseilMois) {
                $conseil->removeMois($conseilMois);
                $this->entityManager->remove($conseilMois);
            }
            // Ajouter les nouveaux mois
            foreach ($data['mois'] as $mois) {
                if ($mois < 1 || $mois > 12) {
                    return new JsonResponse(['error' => 'Mois invalide: ' . $mois . ' Saisir un mois entre 1 et 12'], Response::HTTP_BAD_REQUEST);
                }
                $conseilMois = new ConseilMois();
                $conseilMois->setMois($mois);
                $conseil->addMois($conseilMois);
            }
        }

        // Préparation des données du conseil mis à jour
        $ResponseData =
            [
                'id' => $conseil->getId(),
                'description' => $conseil->getDescription(),
                'mois' => $data['mois']
            ];

        // Persistance des modifications
        $this->entityManager->persist($conseil);
        $this->entityManager->flush();

        // Préparation de la réponse
        $response =
            [
                'message' => 'Conseil mis à jour avec succès',
                'conseil' => $ResponseData
            ];

        return new JsonResponse($response, Response::HTTP_OK, [], true);
    }

    /**
     * Supprimer un conseil
     */
    #[Route('/api/conseil/{id}', name: 'deleteConseil', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Accès refusé, vous devez être administrateur.')]
    public function deleteConseil(int $id): JsonResponse
    {
        // Récupération du conseil à supprimer
        $conseil = $this->conseilRepository->find($id);
        if (!$conseil) {
            return new JsonResponse(['error' => 'Conseil non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Suppression du conseil
        $this->entityManager->remove($conseil);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT, [], true);
    }
}
