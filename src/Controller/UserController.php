<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class UserController extends AbstractController
{
    /**
     * Création d'un nouvel utilisateur
     */
    #[Route('/api/user', name: 'app_user_create', methods: ['POST'])]
    public function postUser(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository): JsonResponse
    {
        // Récupération des données
        $data = json_decode($request->getContent(), true);
        if (empty($data['email']) || empty($data['password']) || empty($data['ville']) || empty($data['pays'])) {
            return new JsonResponse(['error' => 'Données invalides. Email, mot de passe, ville et pays requis.'], Response::HTTP_BAD_REQUEST);
        }

        // Création de l'utilisateur
        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword(password_hash($data['password'], PASSWORD_BCRYPT));
        $user->setVille($data['ville']);
        $user->setPays($data['pays']);
        $user->setRoles(['ROLE_USER']);

        // Sauvegarde de l'utilisateur
        $entityManager->persist($user);
        $entityManager->flush();

        // Préparation des données de réponse
        $responseData = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'ville' => $user->getVille(),
            'pays' => $user->getPays()
        ];

        // Préparation de la réponse
        $response = [
            'message' => 'Utilisateur créé avec succès',
            'user' => $responseData
        ];

        return new JsonResponse($response, Response::HTTP_CREATED);
    }

    /**
     * Mettre à jour un utilisateur
     */
    #[Route('/api/user/{id}', name: 'app_user_update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Accès refusé, vous devez être administrateur.')]
    public function updateUser(int $id, Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository): JsonResponse
    {
        // Récupération de l'utilisateur à mettre à jour
        $user = $userRepository->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        // Récupération des données & mise à jour de l'utilisateur
        $data = json_decode($request->getContent(), true);
        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        if (isset($data['password'])) {
            $user->setPassword(password_hash($data['password'], PASSWORD_BCRYPT));
        }
        if (isset($data['ville'])) {
            $user->setVille($data['ville']);
        }
        if (isset($data['pays'])) {
            $user->setPays($data['pays']);
        }
        if (isset($data['roles']) && is_array($data['roles'])) {
            $user->setRoles($data['roles']);
        }

        // Sauvegarde des modifications
        $entityManager->flush();

        // Préparation des données de réponse
        $responseData = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'ville' => $user->getVille(),
            'pays' => $user->getPays(),
            'roles' => $user->getRoles()
        ];

        // Préparation de la réponse
        $response = [
            'message' => 'Utilisateur mis à jour avec succès',
            'user' => $responseData
        ];

        return new JsonResponse($response, Response::HTTP_OK);
    }

    /**
     * Supprimer un utilisateur
     */
    #[Route('/api/user/{id}', name: 'app_user_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Accès refusé, vous devez être administrateur.')]
    public function deleteUser(int $id, EntityManagerInterface $entityManager, UserRepository $userRepository): JsonResponse
    {
        // Récupération de l'utilisateur à supprimer
        $user = $userRepository->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        // Préparation des données de l'utilisateur supprimé
        $responseData = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'ville' => $user->getVille(),
            'pays' => $user->getPays(),
            'roles' => $user->getRoles()
        ];

        // Suppression de l'utilisateur
        $entityManager->remove($user);
        $entityManager->flush();

        // Préparation de la réponse
        $response = [
            'message' => 'Utilisateur supprimé avec succès',
            'user' => $responseData
        ];

        return new JsonResponse($response, Response::HTTP_OK);
    }
}
