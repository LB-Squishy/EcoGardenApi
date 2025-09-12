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

final class ConseilController extends AbstractController
{
    /**
     * Récupère les conseils du mois en cours
     */
    #[Route('/api/conseils', name: 'app_conseil_current', methods: ['GET'])]
    public function getConseilsCurrentMonth(ConseilRepository $conseilRepository): JsonResponse
    {
        // Récupération du mois courant
        $currentMonth = (int) date('n');

        // Récupération des conseils pour le mois courant
        $conseils = $conseilRepository->findByMonth($currentMonth);
        if (empty($conseils)) {
            return new JsonResponse(['message' => 'Aucun conseil pour le mois en cours'], Response::HTTP_NOT_FOUND);
        }

        // Préparation des données des conseils
        $conseilsData = [];
        foreach ($conseils as $conseil) {
            $conseilsData[] = [
                'id' => $conseil->getId(),
                'description' => $conseil->getDescription(),
                'mois' => $conseil->getMois()->map(fn(ConseilMois $cm) => $cm->getMois())->toArray(),
            ];
        }

        // Préparation de la réponse
        $response =
            [
                'message' => 'Conseils du mois en cours: ' . $currentMonth,
                'conseils' => $conseilsData
            ];

        return new JsonResponse($response, Response::HTTP_OK, []);
    }

    /**
     * Récupère les conseils d'un mois en particulier
     */
    #[Route('/api/conseils/{mois}', name: 'app_conseil_month', methods: ['GET'])]
    public function getConseilsByMonth(int $mois, ConseilRepository $conseilRepository): JsonResponse
    {
        // Validation du mois
        if ($mois < 1 || $mois > 12) {
            return new JsonResponse(['error' => 'Mois invalide: ' . $mois . ' Saisir un mois entre 1 et 12'], Response::HTTP_BAD_REQUEST);
        }

        // Récupération des conseils pour le mois spécifié
        $conseils = $conseilRepository->findByMonth($mois);
        if (empty($conseils)) {
            return new JsonResponse(['message' => 'Aucun conseil pour le mois demandé: ' . $mois], Response::HTTP_NOT_FOUND);
        }

        // Préparation des données des conseils
        $conseilsData = [];
        foreach ($conseils as $conseil) {
            $conseilsData[] = [
                'id' => $conseil->getId(),
                'description' => $conseil->getDescription(),
                'mois' => $conseil->getMois()->map(fn(ConseilMois $cm) => $cm->getMois())->toArray(),
            ];
        }

        // Préparation de la réponse
        $response =
            [
                'message' => 'Conseils du mois demandé: ' . $mois,
                'conseils' => $conseilsData
            ];

        return new JsonResponse($response, Response::HTTP_OK, []);
    }

    /**
     * Ajouter un conseil
     */
    #[Route('/api/conseil', name: 'app_conseil_add', methods: ['POST'])]
    public function postConseil(Request $request, EntityManagerInterface $entityManager): JsonResponse
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
        $entityManager->persist($conseil);
        $entityManager->flush();

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
    #[Route('/api/conseil/{id}', name: 'app_conseil_edit', methods: ['PUT'])]
    public function putConseil(int $id, Request $request, EntityManagerInterface $entityManager, ConseilRepository $conseilRepository): JsonResponse
    {
        // Récupération du conseil à mettre à jour
        $conseil = $conseilRepository->find($id);
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
                $entityManager->remove($conseilMois);
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
        $entityManager->persist($conseil);
        $entityManager->flush();

        // Préparation de la réponse
        $response =
            [
                'message' => 'Conseil mis à jour avec succès',
                'conseil' => $ResponseData
            ];

        return new JsonResponse($response, Response::HTTP_OK, []);
    }

    /**
     * Supprimer un conseil
     */
    #[Route('/api/conseil/{id}', name: 'app_conseil_delete', methods: ['DELETE'])]
    public function deleteConseil(int $id, EntityManagerInterface $entityManager, ConseilRepository $conseilRepository): JsonResponse
    {
        // Récupération du conseil à supprimer
        $conseil = $conseilRepository->find($id);
        if (!$conseil) {
            return new JsonResponse(['error' => 'Conseil non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Préparation des données du conseil supprimé
        $ResponseData =
            [
                'id' => $conseil->getId(),
                'description' => $conseil->getDescription(),
                'mois' => $conseil->getMois()->map(fn(ConseilMois $cm) => $cm->getMois())->toArray(),
            ];

        // Suppression du conseil
        $entityManager->remove($conseil);
        $entityManager->flush();

        // Préparation de la réponse
        $response =
            [
                'message' => 'Conseil supprimé avec succès',
                'conseil' =>  $ResponseData
            ];

        return new JsonResponse($response, Response::HTTP_OK, []);
    }
}
