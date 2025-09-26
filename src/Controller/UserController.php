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
use Symfony\Component\Serializer\SerializerInterface;

final class UserController extends AbstractController
{
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;

    public function __construct(UserRepository $userRepository, EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }

    /**
     * Création d'un nouvel utilisateur
     */
    #[Route('/api/user', name: 'app_user_create', methods: ['POST'])]
    public function postUser(Request $request): JsonResponse
    {
        // Récupération des données
        $data = json_decode($request->getContent(), true);
        if (empty($data['email']) || empty($data['password']) || empty($data['ville'])) {
            return new JsonResponse(['error' => 'Données invalides. Email, mot de passe et ville requis.'], Response::HTTP_BAD_REQUEST);
        }

        // Création de l'utilisateur
        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword(password_hash($data['password'], PASSWORD_BCRYPT));
        $user->setVille($data['ville']);
        $user->setRoles(['ROLE_USER']);

        // Sauvegarde de l'utilisateur
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Sérialisation de user avec le groupe 'user:read'
        $jsonUser = $this->serializer->serialize($user, 'json', ['groups' => 'user:read']);

        return new JsonResponse($jsonUser, Response::HTTP_CREATED, [], true);
    }

    /**
     * Mettre à jour un utilisateur
     */
    #[Route('/api/user/{id}', name: 'app_user_update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Accès refusé, vous devez être administrateur.')]
    public function updateUser(int $id, Request $request): JsonResponse
    {
        // Récupération de l'utilisateur à mettre à jour
        $user = $this->userRepository->find($id);
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
        if (isset($data['roles']) && is_array($data['roles'])) {
            $user->setRoles($data['roles']);
        }

        // Sauvegarde des modifications
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Sérialisation de user avec le groupe 'user:read'
        $jsonUser = $this->serializer->serialize($user, 'json', ['groups' => 'user:write']);

        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }

    /**
     * Supprimer un utilisateur
     */
    #[Route('/api/user/{id}', name: 'app_user_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Accès refusé, vous devez être administrateur.')]
    public function deleteUser(int $id): JsonResponse
    {
        // Récupération de l'utilisateur à supprimer
        $user = $this->userRepository->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        // Suppression de l'utilisateur
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
