<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

final class UserController extends AbstractController
{
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserRepository $userRepository, EntityManagerInterface $entityManager, SerializerInterface $serializer, UserPasswordHasherInterface $passwordHasher)
    {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->passwordHasher = $passwordHasher;
    }

    /**
     * Création d'un nouvel utilisateur
     */
    #[Route('/api/user', name: 'createUser', methods: ['POST'])]
    public function postUser(Request $request): JsonResponse
    {
        // Récupération des données et validation des champs
        $data = json_decode($request->getContent(), true);
        if (!isset($data['email'], $data['password'], $data['ville'])) {
            return new JsonResponse(['error' => 'Données invalides. Email, mot de passe et ville requis.'], Response::HTTP_BAD_REQUEST);
        }
        if ($this->userRepository->findOneBy(['email' => $data['email']])) {
            return new JsonResponse(['error' => 'Cet email est déjà utilisé.'], Response::HTTP_CONFLICT);
        }

        // Désérialisation des données dans un nouvel objet User
        $user = $this->serializer->deserialize($request->getContent(), User::class, 'json');
        if (!$user) {
            return new JsonResponse(['error' => 'Données invalides.'], Response::HTTP_BAD_REQUEST);
        }

        // Hashage du mot de passe et assignation du rôle par défaut
        $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPassword()));
        $user->setRoles(['ROLE_USER']);

        // Persistance des modifications
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Sérialisation et retour
        $jsonUser = $this->serializer->serialize($user, 'json', ['groups' => 'user:read']);
        return new JsonResponse($jsonUser, Response::HTTP_CREATED, [], true);
    }

    /**
     * Mettre à jour un utilisateur
     */
    #[Route('/api/user/{id}', name: 'updateUser', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Accès refusé, vous devez être administrateur.')]
    public function updateUser(int $id, Request $request): JsonResponse
    {
        // Récupération de l'utilisateur à mettre à jour
        $user = $this->userRepository->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        // Récupération des données
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return new JsonResponse(['error' => 'Données invalides.'], Response::HTTP_BAD_REQUEST);
        }

        // Désérialisation des données dans l'objet existant
        $this->serializer->deserialize($request->getContent(), User::class, 'json', ['object_to_populate' => $user]);

        // Mise à jour du mot de passe si fourni
        if (isset($data['password']) && !empty($data['password'])) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));
        }
        // Vérification unicité de l'email si modifié
        $checkUser = $this->userRepository->findOneBy(['email' => $user->getEmail()]);
        if ($checkUser && $checkUser->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Cet email est déjà utilisé.'], Response::HTTP_CONFLICT);
        }

        // Persistance des modifications
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Sérialisation et retour
        $jsonUser = $this->serializer->serialize($user, 'json', ['groups' => 'user:write']);
        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }

    /**
     * Supprimer un utilisateur
     */
    #[Route('/api/user/{id}', name: 'deleteUser', methods: ['DELETE'])]
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
