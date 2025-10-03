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
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class UserController extends AbstractController
{
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;
    private UserPasswordHasherInterface $passwordHasher;
    private ValidatorInterface $validator;

    public function __construct(UserRepository $userRepository, EntityManagerInterface $entityManager, SerializerInterface $serializer, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator)
    {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->passwordHasher = $passwordHasher;
        $this->validator = $validator;
    }

    /**
     * Création d'un nouvel utilisateur
     */
    #[Route('/api/user', name: 'createUser', methods: ['POST'])]
    public function postUser(Request $request): JsonResponse
    {
        // Désérialisation des données dans un nouvel objet User
        $user = $this->serializer->deserialize($request->getContent(), User::class, 'json');

        //On vérifie les erreurs de validation
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            $responseData = ['errors' => $errorMessages];
            $jsonErrors = $this->serializer->serialize($responseData, 'json');
            return new JsonResponse($jsonErrors, Response::HTTP_BAD_REQUEST, [], true);
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
            $responseData = ['errors' => ['user' => 'Cet utilisateur n\'existe pas.']];
            $jsonErrors = $this->serializer->serialize($responseData, 'json');
            return new JsonResponse($jsonErrors, Response::HTTP_NOT_FOUND, [], true);
        }

        // Désérialisation des données dans l'objet existant
        $this->serializer->deserialize($request->getContent(), User::class, 'json', ['object_to_populate' => $user]);

        //On vérifie les erreurs de validation
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            $responseData = ['errors' => $errorMessages];
            $jsonErrors = $this->serializer->serialize($responseData, 'json');
            return new JsonResponse($jsonErrors, Response::HTTP_BAD_REQUEST, [], true);
        }

        // Mise à jour du mot de passe si fourni
        $data = json_decode($request->getContent(), true);
        if (isset($data['password']) && !empty($data['password'])) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));
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
            $responseData = ['errors' => ['user' => 'Cet utilisateur n\'existe pas.']];
            $jsonErrors = $this->serializer->serialize($responseData, 'json');
            return new JsonResponse($jsonErrors, Response::HTTP_NOT_FOUND, [], true);
        }

        // Suppression de l'utilisateur
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
