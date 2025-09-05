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
use Symfony\Component\Serializer\SerializerInterface;

final class ConseilController extends AbstractController
{
    /**
     * Récupère les conseils du mois en cours
     */
    #[Route('/api/conseils', name: 'app_conseil_current', methods: ['GET'])]
    public function getConseilsCurrentMonth(ConseilRepository $conseilRepository, SerializerInterface $serializer): JsonResponse
    {
        $currentMonth = (int) date('n');
        $conseils = $conseilRepository->findByMonth($currentMonth);
        if (empty($conseils)) {
            return new JsonResponse(['message' => 'Aucun conseil pour le mois en cours'], Response::HTTP_NOT_FOUND);
        }
        $jsonConseils = $serializer->serialize($conseils, 'json', ['groups' => 'getConseilsByMonth:read']);

        return new JsonResponse($jsonConseils, Response::HTTP_OK, [], true);
    }

    /**
     * Récupère les conseils d'un mois en particulier'
     */
    #[Route('/api/conseils/{mois}', name: 'app_conseil_month', methods: ['GET'])]
    public function getConseilsByMonth(int $mois, ConseilRepository $conseilRepository, SerializerInterface $serializer): JsonResponse
    {
        if ($mois < 1 || $mois > 12) {
            return new JsonResponse(['error' => 'Mois invalide: ' . $mois . ' Saisir un mois entre 1 et 12'], Response::HTTP_BAD_REQUEST);
        }
        $conseils = $conseilRepository->findByMonth($mois);
        if (empty($conseils)) {
            return new JsonResponse(['message' => 'Aucun conseil pour le mois demandé: ' . $mois], Response::HTTP_NOT_FOUND);
        }
        $jsonConseils = $serializer->serialize($conseils, 'json', ['groups' => 'getConseilsByMonth:read']);

        return new JsonResponse($jsonConseils, Response::HTTP_OK, [], true);
    }

    /**
     * Ajouter un conseil'
     */
    #[Route('/api/conseil/ajouter', name: 'app_conseil_add', methods: ['POST'])]
    public function postConseil(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['description']) || !isset($data['mois']) || !is_array($data['mois'])) {
            return new JsonResponse(['error' => 'Données invalides. Description et mois (array) requis.'], Response::HTTP_BAD_REQUEST);
        }

        $conseil = new Conseil();
        $conseil->setDescription($data['description']);

        foreach ($data['mois'] as $mois) {
            if ($mois < 1 || $mois > 12) {
                return new JsonResponse(['error' => 'Mois invalide: ' . $mois . ' Saisir un mois entre 1 et 12'], Response::HTTP_BAD_REQUEST);
            }
            $conseilMois = new ConseilMois();
            $conseilMois->setMois($mois);
            $conseil->addMois($conseilMois);
        }

        $entityManager->persist($conseil);
        $entityManager->flush();

        return new JsonResponse(['id' => $conseil->getId(), 'message' => 'Conseil ajouté avec succès'], Response::HTTP_CREATED, []);
    }

    /**
     * Mettre à jour un conseil'
     */
    #[Route('/api/conseil/{id}/editer', name: 'app_conseil_edit', methods: ['PUT'])]
    public function putConseil(int $id, Request $request, EntityManagerInterface $entityManager, ConseilRepository $conseilRepository): JsonResponse
    {
        $conseil = $conseilRepository->find($id);
        if (!$conseil) {
            return new JsonResponse(['error' => 'Conseil non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (empty($data)) {
            return new JsonResponse(['error' => 'Données absentes'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['description'])) {
            if (empty($data['description'])) {
                return new JsonResponse(['error' => 'La description ne peut pas être vide'], Response::HTTP_BAD_REQUEST);
            }
            $conseil->setDescription($data['description']);
        }

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

        $entityManager->flush();

        return new JsonResponse(['id' => $conseil->getId(), 'message' => 'Conseil mis à jour avec succès'], Response::HTTP_OK, []);
    }

    #[Route('/api/conseil/{id}/supprimer', name: 'app_conseil_delete', methods: ['DELETE'])]
    public function deleteConseil(int $id, EntityManagerInterface $entityManager, ConseilRepository $conseilRepository): JsonResponse
    {
        $conseil = $conseilRepository->find($id);

        if (!$conseil) {
            return new JsonResponse(['error' => 'Conseil non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($conseil);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Conseil supprimé avec succès'], Response::HTTP_OK, []);
    }
}
