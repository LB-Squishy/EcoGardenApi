<?php

namespace App\Controller;

use App\Entity\Conseil;
use App\Entity\ConseilMois;
use App\Repository\ConseilRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ConseilController extends AbstractController
{
    private ConseilRepository $conseilRepository;
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;

    public function __construct(ConseilRepository $conseilRepository, EntityManagerInterface $entityManager, SerializerInterface $serializer, ValidatorInterface $validator)
    {
        $this->conseilRepository = $conseilRepository;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
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
            $responseData = ['errors' => ['conseil' => 'Aucun conseil trouvé pour le mois en cours.']];
            $jsonErrors = $this->serializer->serialize($responseData, 'json');
            return new JsonResponse($jsonErrors, Response::HTTP_NOT_FOUND, [], true);
        }

        // Sérialisation et retour
        $context = SerializationContext::create()->setGroups(['conseil:read']);
        $jsonConseils = $this->serializer->serialize($conseils, 'json', $context);
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
        $conseilMois = new ConseilMois();
        $conseilMois->setMois($mois);
        $errors = $this->validator->validate($conseilMois);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            $responseData = ['errors' => $errorMessages];
            $jsonErrors = $this->serializer->serialize($responseData, 'json');
            return new JsonResponse($jsonErrors, Response::HTTP_BAD_REQUEST, [], true);
        }

        // Récupération des conseils pour le mois spécifié
        $conseils = $this->conseilRepository->findByMonth($mois);
        if (empty($conseils)) {
            $responseData = ['errors' => ['conseil' => 'Aucun conseil trouvé pour le mois demandé: ' . $mois]];
            $jsonErrors = $this->serializer->serialize($responseData, 'json');
            return new JsonResponse($jsonErrors, Response::HTTP_NOT_FOUND, [], true);
        }

        // Sérialisation et retour
        $context = SerializationContext::create()->setGroups(['conseil:read']);
        $jsonConseils = $this->serializer->serialize($conseils, 'json', $context);
        return new JsonResponse($jsonConseils, Response::HTTP_OK, [], true);
    }

    /**
     * Ajouter un conseil
     */
    #[Route('/api/conseil', name: 'createConseil', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Accès refusé, vous devez être administrateur.')]
    public function postConseil(Request $request): JsonResponse
    {
        // Récupération des données et validation des mois
        $data = json_decode($request->getContent(), true);
        if (!isset($data['mois']) || !is_array($data['mois']) || empty($data['mois'])) {
            $responseData = ['errors' => ['mois' => 'Le tableau des mois est requis au format [1,2,3]']];
            $jsonErrors = $this->serializer->serialize($responseData, 'json');
            return new JsonResponse($jsonErrors, Response::HTTP_BAD_REQUEST, [], true);
        }

        //Création du conseil
        $conseil = new Conseil();
        if (isset($data['description'])) {
            $conseil->setDescription($data['description']);
        }

        // Ajout des mois
        foreach ($data['mois'] as $mois) {
            $conseilMois = new ConseilMois();
            $conseilMois->setMois($mois);
            // Validation des mois
            $errors = $this->validator->validate($conseilMois);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[$error->getPropertyPath()] = $error->getMessage();
                }
                $responseData = ['errors' => $errorMessages];
                $jsonErrors = $this->serializer->serialize($responseData, 'json');
                return new JsonResponse($jsonErrors, Response::HTTP_BAD_REQUEST, [], true);
            }
            $conseil->addMois($conseilMois);
        }

        // Validation de l'entité Conseil
        $errors = $this->validator->validate($conseil);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            $responseData = ['errors' => $errorMessages];
            $jsonErrors = $this->serializer->serialize($responseData, 'json');
            return new JsonResponse($jsonErrors, Response::HTTP_BAD_REQUEST, [], true);
        }

        // Persistance du conseil et des mois associés
        $this->entityManager->persist($conseil);
        $this->entityManager->flush();

        // Sérialisation et retour
        $context = SerializationContext::create()->setGroups(['conseil:read']);
        $jsonConseil = $this->serializer->serialize($conseil, 'json', $context);
        return new JsonResponse($jsonConseil, Response::HTTP_CREATED, [], true);
    }

    /**
     * Mettre à jour un conseil
     */
    #[Route('/api/conseil/{id}', name: 'updateConseil', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Accès refusé, vous devez être administrateur.')]
    public function putConseil(int $id, Request $request): JsonResponse
    {
        // Récupération du conseil à mettre à jour
        $currentConseil = $this->conseilRepository->find($id);
        if (!$currentConseil) {
            $responseData = ['errors' => ['conseil' => 'Aucun conseil trouvé']];
            $jsonErrors = $this->serializer->serialize($responseData, 'json');
            return new JsonResponse($jsonErrors, Response::HTTP_NOT_FOUND, [], true);
        }

        // Récupération des données et validation des mois
        $data = json_decode($request->getContent(), true);
        if (!isset($data['mois']) || !is_array($data['mois']) || empty($data['mois'])) {
            $responseData = ['errors' => ['mois' => 'Le tableau des mois est requis au format [1,2,3]']];
            $jsonErrors = $this->serializer->serialize($responseData, 'json');
            return new JsonResponse($jsonErrors, Response::HTTP_BAD_REQUEST, [], true);
        }

        // Mise à jour du conseil
        if (isset($data['description'])) {
            $currentConseil->setDescription($data['description']);
        }

        // Gestion des mois : suppression des anciens
        foreach ($currentConseil->getMois() as $conseilMois) {
            $currentConseil->removeMois($conseilMois);
            $this->entityManager->remove($conseilMois);
        }

        // Ajout des nouveaux mois
        foreach ($data['mois'] as $mois) {
            $conseilMois = new ConseilMois();
            $conseilMois->setMois($mois);
            // Validation des mois
            $errors = $this->validator->validate($conseilMois);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[$error->getPropertyPath()] = $error->getMessage();
                }
                $responseData = ['errors' => $errorMessages];
                $jsonErrors = $this->serializer->serialize($responseData, 'json');
                return new JsonResponse($jsonErrors, Response::HTTP_BAD_REQUEST, [], true);
            }
            $currentConseil->addMois($conseilMois);
        }

        // Validation de l'entité Conseil mise à jour
        $errors = $this->validator->validate($currentConseil);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            $responseData = ['errors' => $errorMessages];
            $jsonErrors = $this->serializer->serialize($responseData, 'json');
            return new JsonResponse($jsonErrors, Response::HTTP_BAD_REQUEST, [], true);
        }

        // Persistance des modifications
        $this->entityManager->persist($currentConseil);
        $this->entityManager->flush();

        // Sérialisation et retour
        $context = SerializationContext::create()->setGroups(['conseil:read']);
        $jsonConseil = $this->serializer->serialize($currentConseil, 'json', $context);
        return new JsonResponse($jsonConseil, Response::HTTP_OK, [], true);
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
            $responseData = ['errors' => ['conseil' => 'Conseil non trouvé.']];
            $jsonErrors = $this->serializer->serialize($responseData, 'json');
            return new JsonResponse($jsonErrors, Response::HTTP_NOT_FOUND, [], true);
        }

        // Suppression du conseil
        $this->entityManager->remove($conseil);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
